<?php

namespace App\Http\Controllers;

use App\Actions\Families\BrowserExchanges;
use App\Http\Requests\StoreFamilyRequest;
use App\Http\Requests\UpdateFamilyRequest;
use App\Models\Family;
use App\Models\FamilyExclusion;
use App\Models\FamilyMember;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Head\Facades\Head;

class FamilyController extends Controller
{
    public function index(BrowserExchanges $browser): Response
    {
        $families = Family::query()->whereKey($browser->families())->withCount('members')->get()
            ->sortBy(fn (Family $family): int|false => array_search($family->id, $browser->families(), true));
        $invitations = FamilyMember::query()->whereKey($browser->invitations())->with('family:id,name,drawn_at,exchange_on')->get()
            ->sortBy(fn (FamilyMember $member): int|false => array_search($member->id, $browser->invitations(), true));

        return Inertia::render('families/Index', [
            'families' => $families->values()->map(fn (Family $family): array => [
                'id' => $family->id,
                'name' => $family->name,
                'membersCount' => $family->members_count,
                'drawn' => $family->isDrawn(),
                'exchangeOn' => $family->exchange_on?->toDateString(),
            ]),
            'invitations' => $invitations->values()->map(fn (FamilyMember $member): array => [
                'id' => $member->id,
                'name' => $member->name,
                'familyName' => $member->family->name,
                'drawn' => $member->family->isDrawn(),
                'revealed' => $member->revealed_at !== null,
            ]),
        ]);
    }

    public function store(StoreFamilyRequest $request, BrowserExchanges $browser): RedirectResponse
    {
        [$family, $organizer] = DB::transaction(function () use ($request): array {
            $family = Family::create(['name' => $request->validated('name')]);

            return [$family, $family->members()->create(['name' => $request->validated('first_name')])];
        });
        $browser->rememberFamily($family->id);
        $browser->rememberInvitation($organizer->id);

        return to_route('families.show', $family);
    }

    public function show(Family $family, BrowserExchanges $browser): Response
    {
        $browser->rememberFamily($family->id);
        $mine = $browser->invitations();
        Head::title($family->name);

        return Inertia::render('families/Show', [
            'family' => [
                'id' => $family->id,
                'name' => $family->name,
                'budget' => $family->budget,
                'exchangeOn' => $family->exchange_on?->toDateString(),
                'drawn' => $family->isDrawn(),
            ],
            'members' => $family->members()->orderBy('position')->oldest()->get()->map(fn (FamilyMember $member): array => [
                'id' => $member->id,
                'name' => $member->name,
                'wishCount' => count($member->wishList()),
                'revealed' => $member->revealed_at !== null,
                'isMine' => in_array($member->id, $mine, true),
            ]),
            'exclusions' => $family->exclusions()->oldest('id')->get()->map(fn (FamilyExclusion $exclusion): array => [
                'id' => $exclusion->id,
                'memberIds' => [$exclusion->member_id, $exclusion->excluded_member_id],
            ]),
            'maxMembers' => Family::MaxMembers,
        ]);
    }

    public function update(UpdateFamilyRequest $request, Family $family): RedirectResponse
    {
        $family->update($request->validated());
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Les réglages sont enregistrés.']);

        return to_route('families.show', $family);
    }

    public function destroy(Family $family, BrowserExchanges $browser): RedirectResponse
    {
        $browser->forget($family->id, $family->memberIds());
        $family->delete();
        Inertia::flash('toast', ['type' => 'success', 'message' => 'L’échange « '.$family->name.' » a été supprimé.']);

        return to_route('families.index');
    }
}
