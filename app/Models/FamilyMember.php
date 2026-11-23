<?php

namespace App\Models;

use Database\Factories\FamilyMemberFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

#[Fillable(['name', 'email', 'wishes'])]
class FamilyMember extends Model
{
    /** @use HasFactory<FamilyMemberFactory> */
    use HasFactory, HasUuids, Notifiable;

    public function newUniqueId(): string
    {
        return (string) Str::uuid();
    }

    protected static function booted(): void
    {
        static::creating(function (FamilyMember $member): void {
            if (! $member->position) {
                $highestPosition = static::query()->where('family_id', $member->family_id)->max('position');
                $member->position = max(1, (int) $highestPosition + 1);
            }
        });
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['revealed_at' => 'immutable_datetime', 'position' => 'integer'];
    }

    /** @return BelongsTo<Family, $this> */
    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }

    /** @return BelongsTo<FamilyMember, $this> */
    public function recipient(): BelongsTo
    {
        return $this->belongsTo(self::class, 'recipient_id');
    }

    /**
     * The wish list, one idea per line, without blank lines.
     *
     * @return list<string>
     */
    public function wishList(): array
    {
        return array_values(array_filter(
            array_map(trim(...), preg_split('/\R/', (string) $this->wishes) ?: []),
            fn (string $wish): bool => $wish !== '',
        ));
    }
}
