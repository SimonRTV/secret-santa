<?php

namespace App\Actions\Families;

use App\Models\Family;
use Illuminate\Support\Facades\DB;

class CancelFamilyDraw
{
    /**
     * Forget every assignment so the participant list can change again.
     */
    public function handle(Family $family): void
    {
        DB::transaction(function () use ($family): void {
            $lockedFamily = Family::query()->lockForUpdate()->findOrFail($family->id);
            $lockedFamily->members()->update(['recipient_id' => null, 'revealed_at' => null]);
            $lockedFamily->forceFill(['drawn_at' => null])->save();
        }, attempts: 3);
    }
}
