<?php

namespace Database\Seeders;

use App\Models\FamilyExclusion;
use Illuminate\Database\Seeder;

class FamilyExclusionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        FamilyExclusion::factory()->create();
    }
}
