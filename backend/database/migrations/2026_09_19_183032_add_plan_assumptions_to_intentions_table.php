<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('intentions', function (Blueprint $table): void {
            // Null means the plan assumed it; a number means the person said so.
            $table->unsignedInteger('travel_seconds')->nullable()->after('deadline_at');
            $table->unsignedInteger('preparation_seconds')->nullable()->after('travel_seconds');
            $table->unsignedInteger('gathering_seconds')->nullable()->after('preparation_seconds');
        });
    }

    public function down(): void
    {
        Schema::table('intentions', function (Blueprint $table): void {
            $table->dropColumn(['travel_seconds', 'preparation_seconds', 'gathering_seconds']);
        });
    }
};
