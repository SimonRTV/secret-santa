<?php

namespace App\Models;

use Database\Factories\FamilyExclusionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A pair of participants who must not draw each other, stored once with the
 * lowest identifier first so that both directions share one unique row.
 */
#[Fillable(['member_id', 'excluded_member_id'])]
class FamilyExclusion extends Model
{
    /** @use HasFactory<FamilyExclusionFactory> */
    use HasFactory;

    /** @return BelongsTo<Family, $this> */
    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }

    /** @return BelongsTo<FamilyMember, $this> */
    public function member(): BelongsTo
    {
        return $this->belongsTo(FamilyMember::class, 'member_id');
    }

    /** @return BelongsTo<FamilyMember, $this> */
    public function excludedMember(): BelongsTo
    {
        return $this->belongsTo(FamilyMember::class, 'excluded_member_id');
    }

    /**
     * @return array{member_id: string, excluded_member_id: string}
     */
    public static function pairFor(string $firstMemberId, string $secondMemberId): array
    {
        [$memberId, $excludedMemberId] = strcmp($firstMemberId, $secondMemberId) < 0
            ? [$firstMemberId, $secondMemberId]
            : [$secondMemberId, $firstMemberId];

        return ['member_id' => $memberId, 'excluded_member_id' => $excludedMemberId];
    }
}
