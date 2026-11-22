<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('families', function (Blueprint $table): void {
            $table->string('budget', 40)->nullable()->after('name');
            $table->date('exchange_on')->nullable()->after('budget');
        });
        Schema::table('family_members', function (Blueprint $table): void {
            $table->timestamp('revealed_at')->nullable()->after('recipient_id');
            $table->unsignedSmallInteger('position')->default(0)->after('family_id');
        });
    }

    public function down(): void
    {
        Schema::table('family_members', function (Blueprint $table): void {
            $table->dropColumn(['revealed_at', 'position']);
        });
        Schema::table('families', function (Blueprint $table): void {
            $table->dropColumn(['budget', 'exchange_on']);
        });
    }
};
