<?php

namespace Tests\Feature;

use App\Models\Family;
use App\Models\FamilyMember;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class FamilyExchangeTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_a_guest_can_create_a_family_and_find_it_again_in_their_session(): void
    {
        $this->post(route('families.store'), [])->assertSessionHasErrors(['name', 'first_name']);
        $response = $this->post(route('families.store'), ['name' => 'Les Martin', 'first_name' => 'Camille']);
        $family = Family::query()->sole();
        $response->assertRedirectToRoute('families.show', $family);
        $this->assertSame('Camille', $family->members()->sole()->name);
        $this->get(route('families.index'))->assertInertia(fn (Assert $page) => $page
            ->component('families/Index')->has('families', 1)->where('families.0.id', $family->id));

        $this->flushSession();
        $this->get(route('families.index'))->assertInertia(fn (Assert $page) => $page->has('families', 0));
    }

    public function test_invitations_allow_individual_wishes_without_disclosing_other_private_links(): void
    {
        $family = Family::factory()->create();
        $this->post(route('families.members.store', $family), ['name' => ' '])->assertSessionHasErrors('name');
        $this->post(route('families.members.store', $family), ['name' => 'Claude'])->assertRedirectToRoute('families.show', $family);
        $member = $family->members()->sole();
        $other = FamilyMember::factory()->for($family)->create();
        $this->patch(route('members.update', $member), ['wishes' => str_repeat('a', 2001)])->assertSessionHasErrors('wishes');
        $this->patch(route('members.update', $member), ['wishes' => 'Un livre de cuisine'])->assertRedirectToRoute('members.show', $member);

        $this->get(route('members.show', $member))->assertInertia(fn (Assert $page) => $page
            ->component('families/Member')->where('familyName', $family->name)
            ->where('person.wishes', 'Un livre de cuisine')->where('recipient', null)
            ->missing('person.family_id')->missing('members'))->assertDontSee($family->id)->assertDontSee($other->id);
        $this->assertNull($other->refresh()->wishes);
        $this->get(route('families.show', $family))->assertInertia(fn (Assert $page) => $page
            ->component('families/Show')->has('members', 2)->where('drawn', false));
        $this->patch(route('members.update', $member), ['wishes' => ''])->assertSessionHasNoErrors();
        $this->assertNull($member->refresh()->wishes);
        $this->get(route('members.show', $family->id))->assertNotFound();
        $this->get(route('families.show', $member->id))->assertNotFound();
    }

    /** @return array<string, array{int}> */
    public static function additionalParticipants(): array
    {
        return ['deux participants' => [1], 'grande famille' => [6]];
    }

    #[DataProvider('additionalParticipants')]
    public function test_the_draw_stays_within_one_family_and_repeated_requests_preserve_assignments(int $additional): void
    {
        $family = Family::factory()->create();
        $outsider = FamilyMember::factory()->create();
        $people = FamilyMember::factory()->count(1)->for($family)->create();
        $this->post(route('families.draw', $family))->assertSessionHasErrors('draw');
        $this->assertNull($family->refresh()->drawn_at);
        $people = $people->merge(FamilyMember::factory()->count($additional)->for($family)->create());
        $this->post(route('families.draw', $family))->assertRedirectToRoute('families.show', $family);
        $assignments = $family->members()->pluck('recipient_id', 'id');
        $this->assertEqualsCanonicalizing($people->modelKeys(), $assignments->values()->all());
        foreach ($assignments as $giver => $recipient) {
            $this->assertNotSame($giver, $recipient);
        }
        $this->assertNull($outsider->refresh()->recipient_id);
        $this->post(route('families.draw', $family))->assertRedirectToRoute('families.show', $family);
        $this->assertEquals($assignments, $family->members()->pluck('recipient_id', 'id'));
        $this->post(route('families.members.store', $family), ['name' => 'Paul'])->assertSessionHasErrors('name');
        $giver = $people->first()->refresh();
        $recipient = $giver->recipient;
        $this->patch(route('members.update', $recipient), ['wishes' => 'Des chocolats'])->assertSessionHasNoErrors();
        $this->get(route('members.show', $giver))->assertInertia(fn (Assert $page) => $page
            ->where('recipient', ['name' => $recipient->name, 'wishes' => 'Des chocolats']))
            ->assertDontSee($recipient->id);
    }
}
