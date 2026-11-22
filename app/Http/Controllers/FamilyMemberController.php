<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFamilyMemberRequest;
use App\Http\Requests\UpdateWishesRequest;
use App\Models\Family;
use App\Models\FamilyMember;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class FamilyMemberController extends Controller
{
    public function store(StoreFamilyMemberRequest $request, Family $family): RedirectResponse
    {
        DB::transaction(function () use ($request, $family): void {
            $lockedFamily = Family::query()->lockForUpdate()->findOrFail($family->id);
            if ($lockedFamily->drawn_at !== null) {
                throw ValidationException::withMessages(['name' => 'Le tirage a déjà eu lieu.']);
            }
            $lockedFamily->members()->create($request->validated());
        }, attempts: 3);

        return to_route('families.show', $family);
    }

    public function show(FamilyMember $member): Response
    {
        return Inertia::render('families/Member', [
            'familyName' => $member->family()->value('name'),
            'person' => $member->only('id', 'name', 'wishes'),
            'recipient' => $member->recipient?->only('name', 'wishes'),
        ]);
    }

    public function update(UpdateWishesRequest $request, FamilyMember $member): RedirectResponse
    {
        $member->update($request->validated());

        return to_route('members.show', $member);
    }
}
