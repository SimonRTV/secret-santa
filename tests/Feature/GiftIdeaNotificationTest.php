<?php

namespace Tests\Feature;

use App\Actions\Families\AnnounceNewGiftIdeas;
use App\Models\Family;
use App\Models\FamilyMember;
use App\Notifications\NewGiftIdeas;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class GiftIdeaNotificationTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_a_participant_can_save_and_remove_their_email_which_only_their_invitation_shows(): void
    {
        $family = Family::factory()->create();
        $anna = FamilyMember::factory()->for($family)->create();

        $this->patch(route('members.email.update', $anna), [])->assertSessionHasErrors('email');
        $this->patch(route('members.email.update', $anna), ['email' => 'pas-une-adresse'])->assertSessionHasErrors('email');
        $this->assertNull($anna->refresh()->email);

        $this->patch(route('members.email.update', $anna), ['email' => ' Anna@Example.com '])
            ->assertRedirectToRoute('members.show', $anna);
        $this->assertSame('anna@example.com', $anna->refresh()->email);
        $this->get(route('members.show', $anna))->assertInertia(fn (Assert $page) => $page->where('person.email', 'anna@example.com'));
        $this->get(route('families.show', $family))->assertInertia(fn (Assert $page) => $page
            ->component('families/Show')->has('members', 1)->missing('members.0.email'));

        $this->patch(route('members.email.update', $anna), ['email' => ''])->assertSessionHasNoErrors();
        $this->assertNull($anna->refresh()->email);
    }

    public function test_new_gift_ideas_are_announced_once_per_editing_session_to_the_giver_only(): void
    {
        Notification::fake();
        $this->freezeSecond();
        [$anna, $giver, $other] = $this->drawnExchange();

        $this->patch(route('members.update', $anna), ['wishes' => 'Un livre'])->assertRedirectToRoute('members.show', $anna);
        $this->patch(route('members.update', $anna), ['wishes' => "Un livre\nDes chaussettes"]);

        Notification::assertSentToTimes($giver, NewGiftIdeas::class, 1);
        Notification::assertNotSentTo([$anna, $other], NewGiftIdeas::class);
        Notification::assertSentTo($giver, fn (NewGiftIdeas $notification): bool => $notification->recipientId === $anna->id
            && now()->addMinutes(AnnounceNewGiftIdeas::DelayInMinutes)->equalTo($notification->delay));

        $this->travel(AnnounceNewGiftIdeas::DelayInMinutes + 1)->minutes();
        $this->patch(route('members.update', $anna), ['wishes' => "Un livre\nDes chaussettes\nDu thé"]);
        Notification::assertSentToTimes($giver, NewGiftIdeas::class, 2);
    }

    public function test_nothing_is_announced_without_a_new_idea_a_draw_or_a_giver_email(): void
    {
        Notification::fake();
        $family = Family::factory()->create();
        [$anna, $marc] = FamilyMember::factory()->count(2)->for($family)->create(['email' => 'someone@example.com', 'wishes' => "Un livre\nDu thé"])->all();

        $this->patch(route('members.update', $anna), ['wishes' => "Un livre\nDu thé\nUn jeu"]);
        Notification::assertNothingSent();

        $marc->forceFill(['recipient_id' => $anna->id])->save();
        $this->patch(route('members.update', $anna), ['wishes' => "Du thé\nUn jeu"]);
        $this->patch(route('members.update', $anna), ['wishes' => "Un jeu\nDu thé"]);
        Notification::assertNothingSent();

        $marc->update(['email' => null]);
        $this->patch(route('members.update', $anna), ['wishes' => "Un jeu\nDu thé\nUn puzzle"]);
        Notification::assertNothingSent();
    }

    public function test_the_announcement_keeps_the_draw_secret_and_is_dropped_when_it_no_longer_applies(): void
    {
        [$anna, $giver] = $this->drawnExchange();
        $notification = new NewGiftIdeas($anna->id);

        $mail = $notification->toMail($giver);
        $rendered = $mail->subject.' '.$mail->render();
        $this->assertStringNotContainsString($anna->name, $rendered);
        $this->assertStringNotContainsString($anna->id, $rendered);
        $this->assertSame(route('members.show', $giver), $mail->actionUrl);
        $this->assertSame([$giver->email], (array) $giver->routeNotificationFor('mail'));

        $this->assertTrue($notification->shouldSend($giver, 'mail'));
        $giver->update(['email' => null]);
        $this->assertFalse($notification->shouldSend($giver, 'mail'));
        $giver->update(['email' => 'giver@example.com']);
        $this->delete(route('families.draw.destroy', $giver->family));
        $this->assertFalse($notification->shouldSend($giver->refresh(), 'mail'));
    }

    /**
     * An exchange where the giver, who left an e-mail address, drew Anna.
     *
     * @return array{FamilyMember, FamilyMember, FamilyMember}
     */
    private function drawnExchange(): array
    {
        $family = Family::factory()->create(['drawn_at' => now()]);
        $anna = FamilyMember::factory()->for($family)->create(['name' => 'Annabelle-Rose']);
        $giver = FamilyMember::factory()->for($family)->create(['name' => 'Marc', 'email' => 'giver@example.com']);
        $other = FamilyMember::factory()->for($family)->create(['name' => 'Léa', 'email' => 'other@example.com']);
        $giver->forceFill(['recipient_id' => $anna->id])->save();
        $other->forceFill(['recipient_id' => $giver->id])->save();

        return [$anna, $giver, $other];
    }
}
