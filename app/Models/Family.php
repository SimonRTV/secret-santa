<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\FamilyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * @property string $id
 * @property string $name
 * @property string|null $budget
 * @property CarbonImmutable|null $exchange_on
 * @property CarbonImmutable|null $drawn_at
 */
#[Fillable(['name', 'budget', 'exchange_on'])]
class Family extends Model
{
    /** @use HasFactory<FamilyFactory> */
    use HasFactory, HasUuids;

    /** The most participants a single exchange accepts. */
    public const MaxMembers = 60;

    public function newUniqueId(): string
    {
        return (string) Str::uuid();
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'drawn_at' => 'immutable_datetime',
            'exchange_on' => 'immutable_date',
        ];
    }

    /** @return HasMany<FamilyMember, $this> */
    public function members(): HasMany
    {
        return $this->hasMany(FamilyMember::class);
    }

    /** @return HasMany<FamilyExclusion, $this> */
    public function exclusions(): HasMany
    {
        return $this->hasMany(FamilyExclusion::class);
    }

    /**
     * The participants' identifiers, in the order they joined.
     *
     * @return list<string>
     */
    public function memberIds(): array
    {
        return array_values($this->members()->orderBy('position')->oldest()->get()
            ->map(fn (FamilyMember $member): string => $member->id)
            ->all());
    }

    public function isDrawn(): bool
    {
        return $this->drawn_at !== null;
    }
}
