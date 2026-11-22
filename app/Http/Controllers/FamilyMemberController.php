<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFamilyMemberRequest;
use App\Http\Requests\UpdateFamilyMemberRequest;
use App\Models\Family;
use App\Models\FamilyMember;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class FamilyMemberController extends Controller
{
    public function store(StoreFamilyMemberRequest $request, Family $family): RedirectResponse
    {
        $this->whileOpen($family, 'name', function (Family $lockedFamily) use ($request): void {
            if ($lockedFamily->members()->count() >= Family::MaxMembers) {
                throw ValidationException::withMessages(['name' => 'Un échange peut réunir '.Family::MaxMembers.' personnes au maximum.']);
            }
            $lockedFamily->members()->create(['name' => trim($request->validated('name'))]);
        });

        return to_route('families.show', $family);
    }

    public function update(UpdateFamilyMemberRequest $request, Family $family, FamilyMember $member): RedirectResponse
    {
        $member->update(['name' => trim($request->validated('name'))]);

        return to_route('families.show', $family);
    }

    public function destroy(Family $family, FamilyMember $member): RedirectResponse
    {
        $this->whileOpen($family, 'member', fn () => $member->delete());
        Inertia::flash('toast', ['type' => 'success', 'message' => $member->name.' ne participe plus.']);

        return to_route('families.show', $family);
    }

    /**
     * Run a change to the participant list only while names are not drawn yet.
     *
     * @param  Closure(Family): mixed  $change
     */
    private function whileOpen(Family $family, string $errorKey, Closure $change): void
    {
        DB::transaction(function () use ($family, $errorKey, $change): void {
            $lockedFamily = Family::query()->lockForUpdate()->findOrFail($family->id);
            if ($lockedFamily->isDrawn()) {
                throw ValidationException::withMessages([$errorKey => 'Le tirage a déjà eu lieu. Annulez-le pour modifier la liste.']);
            }
            $change($lockedFamily);
        }, attempts: 3);
    }
}
