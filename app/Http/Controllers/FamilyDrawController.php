<?php

namespace App\Http\Controllers;

use App\Actions\Families\CancelFamilyDraw;
use App\Actions\Families\DrawFamilyNames;
use App\Models\Family;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class FamilyDrawController extends Controller
{
    public function store(Family $family, DrawFamilyNames $drawFamilyNames): RedirectResponse
    {
        $drawFamilyNames->handle($family);

        return to_route('families.show', $family);
    }

    public function destroy(Family $family, CancelFamilyDraw $cancelFamilyDraw): RedirectResponse
    {
        $cancelFamilyDraw->handle($family);
        Inertia::flash('toast', ['type' => 'info', 'message' => 'Le tirage est annulé. Vous pouvez modifier la liste.']);

        return to_route('families.show', $family);
    }
}
