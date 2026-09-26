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
            $table->unsignedSmallInteger('recurrence_every_days')->nullable();
            $table->timestamp('recurrence_next_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('intentions', function (Blueprint $table): void {
            $table->dropColumn(['recurrence_every_days', 'recurrence_next_at']);
        });
    }
};
