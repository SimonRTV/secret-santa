<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('family_exclusions', function (Blueprint $table): void {
            $table->id();
            $table->foreignUuid('family_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('member_id')->constrained('family_members')->cascadeOnDelete();
            $table->foreignUuid('excluded_member_id')->constrained('family_members')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['member_id', 'excluded_member_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('family_exclusions');
    }
};
