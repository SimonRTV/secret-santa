<?php

namespace App\Actions\Families;

use App\Models\Family;
use App\Models\FamilyExclusion;
use App\Models\FamilyMember;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DrawFamilyNames
{
    public function __construct(private AssignSecretSantas $assignSecretSantas) {}

    /**
     * Draw names once for the family. Repeated requests keep the first draw.
     *
     * @throws ValidationException when there are too few participants or the exclusions leave no possible draw
     */
    public function handle(Family $family): void
    {
        DB::transaction(function () use ($family): void {
            $lockedFamily = Family::query()->lockForUpdate()->findOrFail($family->id);
            if ($lockedFamily->isDrawn()) {
                return;
            }

            $participants = $lockedFamily->memberIds();
            if (count($participants) < 3) {
                throw ValidationException::withMessages(['draw' => 'Ajoutez au moins trois personnes pour que le tirage reste une surprise.']);
            }

            $forbiddenPairs = array_values($lockedFamily->exclusions()->get()
                ->map(fn (FamilyExclusion $exclusion): array => [$exclusion->member_id, $exclusion->excluded_member_id])
                ->all());
            $assignments = $this->assignSecretSantas->handle($participants, $forbiddenPairs);
            if ($assignments === null) {
                throw ValidationException::withMessages(['draw' => 'Avec ces exceptions, personne ne peut tirer un nom valable. Retirez une exception ou ajoutez un participant.']);
            }

            foreach ($assignments as $giver => $recipient) {
                FamilyMember::query()->whereKey($giver)->update(['recipient_id' => $recipient, 'revealed_at' => null]);
            }
            $lockedFamily->forceFill(['drawn_at' => now()])->save();
        }, attempts: 3);
    }
}
