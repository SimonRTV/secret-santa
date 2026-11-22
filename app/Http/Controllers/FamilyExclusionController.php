<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFamilyExclusionRequest;
use App\Models\Family;
use App\Models\FamilyExclusion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FamilyExclusionController extends Controller
{
    public function store(StoreFamilyExclusionRequest $request, Family $family): RedirectResponse
    {
        DB::transaction(function () use ($request, $family): void {
            $lockedFamily = Family::query()->lockForUpdate()->findOrFail($family->id);
            $this->ensureOpen($lockedFamily);
            $lockedFamily->exclusions()->firstOrCreate(FamilyExclusion::pairFor(
                $request->validated('member_id'),
                $request->validated('excluded_member_id'),
            ));
        }, attempts: 3);

        return to_route('families.show', $family);
    }

    public function destroy(Family $family, FamilyExclusion $exclusion): RedirectResponse
    {
        DB::transaction(function () use ($family, $exclusion): void {
            $this->ensureOpen(Family::query()->lockForUpdate()->findOrFail($family->id));
            $exclusion->delete();
        }, attempts: 3);

        return to_route('families.show', $family);
    }

    private function ensureOpen(Family $family): void
    {
        if ($family->isDrawn()) {
            throw ValidationException::withMessages(['exclusion' => 'Le tirage a déjà eu lieu. Annulez-le pour modifier les exceptions.']);
        }
    }
}
