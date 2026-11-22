<?php

namespace Database\Factories;

use App\Models\Family;
use App\Models\FamilyExclusion;
use App\Models\FamilyMember;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FamilyExclusion>
 */
class FamilyExclusionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'family_id' => Family::factory(),
            'member_id' => fn (array $attributes): string => FamilyMember::factory()->create(['family_id' => $attributes['family_id']])->id,
            'excluded_member_id' => fn (array $attributes): string => FamilyMember::factory()->create(['family_id' => $attributes['family_id']])->id,
        ];
    }

    /**
     * Exclude the given pair of participants from drawing each other.
     */
    public function between(FamilyMember $first, FamilyMember $second): static
    {
        return $this->state(['family_id' => $first->family_id, ...FamilyExclusion::pairFor($first->id, $second->id)]);
    }
}
