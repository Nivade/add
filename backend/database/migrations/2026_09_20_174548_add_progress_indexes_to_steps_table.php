<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('steps', function (Blueprint $table): void {
            // The candidate pool and "finished today" both lead on status, which the
            // intention-first composite cannot serve.
            $table->index(['status', 'completed_at']);
        });
    }

    public function down(): void
    {
        Schema::table('steps', function (Blueprint $table): void {
            $table->dropIndex(['status', 'completed_at']);
        });
    }
};
