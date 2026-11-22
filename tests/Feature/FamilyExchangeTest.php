<?php

namespace Tests\Feature;

use App\Actions\Families\BrowserExchanges;
use App\Models\Family;
use App\Models\FamilyExclusion;
use App\Models\FamilyMember;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia as Assert;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class FamilyExchangeTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_a_guest_can_create_an_exchange_and_find_it_again_with_their_browser_cookie(): void
    {
        $this->post(route('families.store'), [])->assertSessionHasErrors(['name', 'first_name']);
        $response = $this->post(route('families.store'), ['name' => 'Les Martin', 'first_name' => 'Camille']);
        $family = Family::query()->sole();
        $organizer = $family->members()->sole();
        $response->assertRedirectToRoute('families.show', $family);
        $this->assertSame('Camille', $organizer->name);

        $this->withRememberedExchanges($response)->get(route('families.index'))->assertInertia(fn (Assert $page) => $page
            ->component('families/Index')
            ->has('families', 1)->where('families.0.id', $family->id)->where('families.0.membersCount', 1)
            ->has('invitations', 1)->where('invitations.0.id', $organizer->id)->where('invitations.0.familyName', 'Les Martin'));

        $this->defaultCookies = [];
        $this->get(route('families.index'))->assertInertia(fn (Assert $page) => $page->has('families', 0)->has('invitations', 0));
        $this->withCookie(BrowserExchanges::CookieName, '{"families":["not-a-uuid",42]}')
            ->get(route('families.index'))->assertOk()->assertInertia(fn (Assert $page) => $page->has('families', 0));
    }

    public function test_opening_an_organiser_link_or_an_invitation_remembers_it_in_this_browser(): void
    {
        $family = Family::factory()->create();
        $member = FamilyMember::factory()->for($family)->create();

        $response = $this->get(route('families.show', $family))->assertOk();
        $response = $this->withRememberedExchanges($response)->get(route('members.show', $member))->assertOk();
        $this->withRememberedExchanges($response)->get(route('families.show', $family))->assertInertia(fn (Assert $page) => $page
            ->component('families/Show')->where('members.0.isMine', true));
        $this->withRememberedExchanges($response)->get(route('families.index'))->assertInertia(fn (Assert $page) => $page
            ->has('families', 1)->has('invitations', 1));
    }

    public function test_the_browser_cookie_keeps_only_the_most_recent_exchanges_to_stay_small(): void
    {
        $families = Family::factory()->count(22)->create();
        $response = null;
        foreach ($families as $family) {
            $response = ($response ? $this->withRememberedExchanges($response) : $this)->get(route('families.show', $family));
        }

        $cookie = $response->getCookie(BrowserExchanges::CookieName, decrypt: false);
        $this->assertLessThan(4096, strlen($cookie->getName().'='.$cookie->getValue()));
        $this->withRememberedExchanges($response)->get(route('families.index'))->assertInertia(fn (Assert $page) => $page
            ->has('families', 20)
            ->where('families.0.id', $families->last()->id)
            ->where('families', fn ($remembered) => ! collect($remembered)->pluck('id')->contains($families->first()->id)));
    }

    public function test_the_organiser_can_edit_the_exchange_details_and_delete_it(): void
    {
        $family = Family::factory()->create();
        $member = FamilyMember::factory()->for($family)->create();

        $this->patch(route('families.update', $family), ['name' => '', 'exchange_on' => '24/12/2026'])
            ->assertSessionHasErrors(['name', 'exchange_on']);
        $this->patch(route('families.update', $family), ['name' => 'Noël chez Mamie', 'budget' => '30 CHF', 'exchange_on' => '2026-12-24'])
            ->assertRedirectToRoute('families.show', $family);
        $this->get(route('families.show', $family))->assertInertia(fn (Assert $page) => $page
            ->where('family.name', 'Noël chez Mamie')->where('family.budget', '30 CHF')->where('family.exchangeOn', '2026-12-24'));
        $this->get(route('members.show', $member))->assertInertia(fn (Assert $page) => $page
            ->where('family.budget', '30 CHF')->where('family.exchangeOn', '2026-12-24'));

        $this->delete(route('families.destroy', $family))->assertRedirectToRoute('families.index');
        $this->assertModelMissing($family);
        $this->assertModelMissing($member);
        $this->get(route('members.show', $member))->assertNotFound();
    }

    public function test_participants_can_be_added_renamed_and_removed_with_unique_names(): void
    {
        $family = Family::factory()->create();
        $camille = FamilyMember::factory()->for($family)->create(['name' => 'Camille']);
        $outsider = FamilyMember::factory()->create(['name' => 'Léa']);

        $this->post(route('families.members.store', $family), ['name' => ' '])->assertSessionHasErrors('name');
        $this->post(route('families.members.store', $family), ['name' => 'camille'])->assertSessionHasErrors('name');
        $this->post(route('families.members.store', $family), ['name' => '  Léa '])->assertRedirectToRoute('families.show', $family);
        $lea = $family->members()->where('name', 'Léa')->sole();
        $this->post(route('families.members.store', $family), ['name' => 'Paul']);
        $this->post(route('families.members.store', $family), ['name' => 'Anna']);
        $this->get(route('families.show', $family))->assertInertia(fn (Assert $page) => $page
            ->where('members', fn ($members) => collect($members)->pluck('name')->all() === ['Camille', 'Léa', 'Paul', 'Anna']));

        $this->patch(route('families.members.update', [$family, $lea]), ['name' => 'CAMILLE'])->assertSessionHasErrors('name');
        $this->patch(route('families.members.update', [$family, $lea]), ['name' => 'Léa'])->assertSessionHasNoErrors();
        $this->patch(route('families.members.update', [$family, $lea]), ['name' => 'Léa M.'])->assertRedirectToRoute('families.show', $family);
        $this->assertSame('Léa M.', $lea->refresh()->name);

        $this->patch(route('families.members.update', [$family, $outsider]), ['name' => 'Intrus'])->assertNotFound();
        $this->delete(route('families.members.destroy', [$family, $outsider]))->assertNotFound();
        $this->delete(route('families.members.destroy', [$family, $lea]))->assertRedirectToRoute('families.show', $family);
        $this->assertModelMissing($lea);
        $this->assertModelExists($camille);
        $this->assertSame('Léa', $outsider->refresh()->name);
    }

    public function test_an_exchange_accepts_a_bounded_number_of_participants(): void
    {
        $family = Family::factory()->create();
        FamilyMember::factory()->count(Family::MaxMembers)->for($family)->sequence(fn ($sequence) => ['name' => 'Personne '.$sequence->index])->create();

        $this->post(route('families.members.store', $family), ['name' => 'Une de trop'])->assertSessionHasErrors('name');
        $this->assertSame(Family::MaxMembers, $family->members()->count());
    }

    public function test_exclusions_are_stored_once_per_pair_and_stay_within_the_family(): void
    {
        $family = Family::factory()->create();
        [$anna, $marc] = FamilyMember::factory()->count(2)->for($family)->create()->all();
        $outsider = FamilyMember::factory()->create();

        $this->post(route('families.exclusions.store', $family), ['member_id' => $anna->id, 'excluded_member_id' => $anna->id])
            ->assertSessionHasErrors('excluded_member_id');
        $this->post(route('families.exclusions.store', $family), ['member_id' => $anna->id, 'excluded_member_id' => $outsider->id])
            ->assertSessionHasErrors('excluded_member_id');
        $this->post(route('families.exclusions.store', $family), ['member_id' => $anna->id, 'excluded_member_id' => $marc->id])
            ->assertRedirectToRoute('families.show', $family);
        $this->post(route('families.exclusions.store', $family), ['member_id' => $marc->id, 'excluded_member_id' => $anna->id])
            ->assertSessionHasNoErrors();
        $exclusion = $family->exclusions()->sole();
        $this->assertEqualsCanonicalizing([$anna->id, $marc->id], [$exclusion->member_id, $exclusion->excluded_member_id]);

        $this->get(route('families.show', $family))->assertInertia(fn (Assert $page) => $page->has('exclusions', 1));
        $otherExclusion = FamilyExclusion::factory()->create();
        $this->delete(route('families.exclusions.destroy', [$family, $otherExclusion]))->assertNotFound();
        $this->delete(route('families.exclusions.destroy', [$family, $exclusion]))->assertRedirectToRoute('families.show', $family);
        $this->assertModelMissing($exclusion);
        $this->assertModelExists($otherExclusion);

        FamilyExclusion::factory()->between($anna, $marc)->create();
        $this->delete(route('families.members.destroy', [$family, $marc]));
        $this->assertSame(0, $family->exclusions()->count());
    }

    public function test_the_draw_needs_three_people_and_gives_everyone_someone_else_from_their_family(): void
    {
        $family = Family::factory()->create();
        $outsider = FamilyMember::factory()->create();
        $people = FamilyMember::factory()->count(2)->for($family)->create();
        $this->post(route('families.draw', $family))->assertSessionHasErrors('draw');
        $this->assertNull($family->refresh()->drawn_at);

        $people = $people->merge(FamilyMember::factory()->count(5)->for($family)->create());
        $this->post(route('families.draw', $family))->assertRedirectToRoute('families.show', $family);
        $assignments = $family->members()->pluck('recipient_id', 'id');
        $this->assertEqualsCanonicalizing($people->modelKeys(), $assignments->values()->all());
        foreach ($assignments as $giver => $recipient) {
            $this->assertNotSame($giver, $recipient);
            $this->assertNotSame($giver, $assignments[$recipient], 'Two people should not simply swap gifts.');
        }
        $this->assertNull($outsider->refresh()->recipient_id);

        $this->post(route('families.draw', $family))->assertRedirectToRoute('families.show', $family);
        $this->assertEquals($assignments, $family->members()->pluck('recipient_id', 'id'));
    }

    public function test_the_draw_never_pairs_excluded_people(): void
    {
        $family = Family::factory()->create();
        [$anna, $marc, $lea, $paul] = FamilyMember::factory()->count(4)->for($family)->create()->all();
        FamilyExclusion::factory()->between($anna, $marc)->create();
        FamilyExclusion::factory()->between($lea, $paul)->create();
        $this->withoutMiddleware(ThrottleRequests::class);

        for ($attempt = 0; $attempt < 15; $attempt++) {
            $this->post(route('families.draw', $family))->assertSessionHasNoErrors();
            $assignments = $family->members()->pluck('recipient_id', 'id');
            $this->assertNotSame($marc->id, $assignments[$anna->id]);
            $this->assertNotSame($anna->id, $assignments[$marc->id]);
            $this->assertNotSame($paul->id, $assignments[$lea->id]);
            $this->assertNotSame($lea->id, $assignments[$paul->id]);
            $this->delete(route('families.draw.destroy', $family))->assertRedirectToRoute('families.show', $family);
        }
    }

    public function test_the_draw_explains_when_exclusions_leave_no_possible_assignment(): void
    {
        $family = Family::factory()->create();
        [$anna, $marc, $lea] = FamilyMember::factory()->count(3)->for($family)->create()->all();
        FamilyExclusion::factory()->between($anna, $marc)->create();
        FamilyExclusion::factory()->between($anna, $lea)->create();

        $this->post(route('families.draw', $family))->assertSessionHasErrors('draw');
        $this->assertNull($family->refresh()->drawn_at);
        $this->assertSame(0, $family->members()->whereNotNull('recipient_id')->count());
    }

    public function test_the_participant_list_is_locked_after_the_draw_until_it_is_cancelled(): void
    {
        $family = Family::factory()->create();
        [$anna, $marc] = FamilyMember::factory()->count(3)->for($family)->create()->all();
        $this->post(route('families.draw', $family));
        $anna->forceFill(['revealed_at' => now()])->save();

        $this->post(route('families.members.store', $family), ['name' => 'Paul'])->assertSessionHasErrors('name');
        $this->delete(route('families.members.destroy', [$family, $marc]))->assertSessionHasErrors('member');
        $this->post(route('families.exclusions.store', $family), ['member_id' => $anna->id, 'excluded_member_id' => $marc->id])
            ->assertSessionHasErrors('exclusion');
        $this->patch(route('families.members.update', [$family, $marc]), ['name' => 'Marc D.'])->assertSessionHasNoErrors();
        $this->assertSame(3, $family->members()->count());

        $this->delete(route('families.draw.destroy', $family))->assertRedirectToRoute('families.show', $family);
        $this->assertNull($family->refresh()->drawn_at);
        $this->assertSame(0, $family->members()->whereNotNull('recipient_id')->orWhereNotNull('revealed_at')->count());
        $this->post(route('families.members.store', $family), ['name' => 'Paul'])->assertSessionHasNoErrors();
    }

    public function test_invitations_keep_wishes_private_and_reveal_the_recipient_only_once_opened(): void
    {
        $family = Family::factory()->create(['name' => 'Les Martin']);
        [$anna, $marc, $lea] = FamilyMember::factory()->count(3)->for($family)->create()->all();

        $this->patch(route('members.update', $anna), ['wishes' => str_repeat('a', 2001)])->assertSessionHasErrors('wishes');
        $this->patch(route('members.update', $anna), ['wishes' => "  Un livre de cuisine \n\n\r\nDes chaussettes  "])
            ->assertRedirectToRoute('members.show', $anna);
        $this->assertSame("Un livre de cuisine\nDes chaussettes", $anna->refresh()->wishes);
        $this->patch(route('members.update', $marc), ['wishes' => "\n \n"])->assertSessionHasNoErrors();
        $this->assertNull($marc->refresh()->wishes);

        $this->post(route('members.reveal', $marc))->assertRedirectToRoute('members.show', $marc);
        $this->assertNull($marc->refresh()->revealed_at);
        $this->get(route('members.show', $anna))->assertInertia(fn (Assert $page) => $page
            ->component('families/Member')->where('family.name', 'Les Martin')->where('family.drawn', false)
            ->where('person.wishes', "Un livre de cuisine\nDes chaussettes")->where('recipient', null)
            ->missing('person.family_id')->missing('members'))
            ->assertDontSee($family->id)->assertDontSee($marc->id)->assertDontSee($lea->id);
        $this->get(route('families.show', $family))->assertInertia(fn (Assert $page) => $page
            ->where('members', fn ($members) => collect($members)->pluck('wishCount', 'id')->sortKeys()->all()
                === collect([$anna->id => 2, $marc->id => 0, $lea->id => 0])->sortKeys()->all()));

        $this->post(route('families.draw', $family));
        $giver = $family->members()->where('recipient_id', $anna->id)->sole();
        $this->get(route('members.show', $giver))->assertInertia(fn (Assert $page) => $page
            ->where('family.drawn', true)->where('recipient', null));

        $this->post(route('members.reveal', $giver))->assertRedirectToRoute('members.show', $giver);
        $revealedAt = $giver->refresh()->revealed_at;
        $this->assertNotNull($revealedAt);
        $this->travel(5)->minutes();
        $this->post(route('members.reveal', $giver));
        $this->assertEquals($revealedAt, $giver->refresh()->revealed_at);

        $this->get(route('members.show', $giver))->assertInertia(fn (Assert $page) => $page
            ->where('recipient', ['name' => $anna->name, 'wishes' => ['Un livre de cuisine', 'Des chaussettes']]));
        $this->get(route('families.show', $family))->assertInertia(fn (Assert $page) => $page
            ->where('members', fn ($members) => collect($members)->where('revealed', true)->pluck('id')->all() === [$giver->id]));
    }

    public function test_a_household_sharing_one_address_can_set_up_and_draw_without_being_rate_limited(): void
    {
        $this->post(route('families.store'), ['name' => 'Les Martin', 'first_name' => 'Camille'])->assertRedirect();
        $family = Family::query()->sole();
        foreach (['Marc', 'Léa', 'Paul', 'Jo', 'Zoé', 'Tom', 'Inès', 'Hugo', 'Lina'] as $name) {
            $this->post(route('families.members.store', $family), ['name' => $name])->assertRedirectToRoute('families.show', $family);
        }
        [$camille, $marc] = $family->members()->orderBy('position')->take(2)->get()->all();
        $this->post(route('families.exclusions.store', $family), ['member_id' => $camille->id, 'excluded_member_id' => $marc->id])
            ->assertRedirectToRoute('families.show', $family);
        $this->post(route('families.draw', $family))->assertRedirectToRoute('families.show', $family);

        for ($save = 0; $save < 40; $save++) {
            $this->patch(route('members.update', $camille), ['wishes' => "Idée {$save}"])->assertRedirectToRoute('members.show', $camille);
        }
        $this->post(route('members.reveal', $camille))->assertRedirectToRoute('members.show', $camille);
        $this->assertNotNull($family->refresh()->drawn_at);
    }

    public function test_unknown_or_mismatched_links_are_not_found(): void
    {
        $family = Family::factory()->create();
        $member = FamilyMember::factory()->for($family)->create();

        $this->get(route('members.show', $family->id))->assertNotFound();
        $this->get(route('families.show', $member->id))->assertNotFound();
        $this->get('/invitations/inexistant')->assertNotFound();
    }

    /**
     * Carry the remembered-exchanges cookie set by a response into the next request.
     *
     * @param  TestResponse<Response>  $response
     */
    private function withRememberedExchanges(TestResponse $response): static
    {
        $cookie = $response->getCookie(BrowserExchanges::CookieName);
        $this->assertNotNull($cookie);

        return $this->withCookie(BrowserExchanges::CookieName, (string) $cookie->getValue());
    }
}
