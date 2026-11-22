<?php

namespace App\Http\Controllers;

use App\Actions\Families\BrowserExchanges;
use App\Http\Requests\UpdateWishesRequest;
use App\Models\FamilyMember;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Head\Facades\Head;

class InvitationController extends Controller
{
    public function show(FamilyMember $member, BrowserExchanges $browser): Response
    {
        $browser->rememberInvitation($member->id);
        $family = $member->family;
        $revealed = $family->isDrawn() && $member->revealed_at !== null;
        Head::title('Invitation de '.$member->name);

        return Inertia::render('families/Member', [
            'family' => [
                'name' => $family->name,
                'budget' => $family->budget,
                'exchangeOn' => $family->exchange_on?->toDateString(),
                'drawn' => $family->isDrawn(),
            ],
            'person' => $member->only('id', 'name', 'wishes'),
            'recipient' => $revealed && $member->recipient
                ? ['name' => $member->recipient->name, 'wishes' => $member->recipient->wishList()]
                : null,
        ]);
    }

    public function update(UpdateWishesRequest $request, FamilyMember $member): RedirectResponse
    {
        $member->update(['wishes' => $request->wishes()]);

        return to_route('members.show', $member);
    }

    /**
     * Open the envelope: from now on the invitation shows who to offer a gift to.
     */
    public function reveal(FamilyMember $member): RedirectResponse
    {
        if ($member->recipient_id !== null && $member->revealed_at === null) {
            $member->forceFill(['revealed_at' => now()])->save();
        }

        return to_route('members.show', $member);
    }
}
