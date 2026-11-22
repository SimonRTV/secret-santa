<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFamilyRequest;
use App\Models\Family;
use App\Models\FamilyMember;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Random\Randomizer;

class FamilyController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('families/Index', [
            'families' => Family::query()->whereKey($request->session()->get('families', []))
                ->latest()->get(['id', 'name']),
        ]);
    }

    public function store(StoreFamilyRequest $request): RedirectResponse
    {
        $family = DB::transaction(function () use ($request): Family {
            $family = Family::create(['name' => $request->validated('name')]);
            $family->members()->create(['name' => $request->validated('first_name')]);

            return $family;
        });
        $request->session()->push('families', $family->id);

        return to_route('families.show', $family);
    }

    public function show(Family $family): Response
    {
        return Inertia::render('families/Show', [
            'family' => $family->only('id', 'name'),
            'drawn' => $family->drawn_at !== null,
            'members' => $family->members()->oldest()->orderBy('id')->get()->map(fn (FamilyMember $person): array => [
                'id' => $person->id, 'name' => $person->name, 'hasWishes' => filled($person->wishes),
            ]),
        ]);
    }

    public function draw(Family $family, Randomizer $randomizer): RedirectResponse
    {
        DB::transaction(function () use ($family, $randomizer): void {
            $lockedFamily = Family::query()->lockForUpdate()->findOrFail($family->id);
            if ($lockedFamily->drawn_at !== null) {
                return;
            }
            $members = $randomizer->shuffleArray($lockedFamily->members()->pluck('id')->all());
            if (count($members) < 2) {
                throw ValidationException::withMessages(['draw' => 'Ajoutez au moins deux personnes pour lancer le tirage.']);
            }
            foreach ($members as $index => $id) {
                FamilyMember::query()->whereKey($id)->update(['recipient_id' => $members[($index + 1) % count($members)]]);
            }
            $lockedFamily->drawn_at = now();
            $lockedFamily->save();
        }, attempts: 3);

        return to_route('families.show', $family);
    }
}
