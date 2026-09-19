<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('calendar_events', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('source', 32);
            $table->string('external_id');
            $table->string('title');
            $table->string('location')->nullable();
            $table->timestamp('starts_at');
            $table->timestamp('ends_at')->nullable();
            // Null means the plan assumed it; a number means the person said so.
            $table->unsignedInteger('travel_seconds')->nullable();
            $table->unsignedInteger('preparation_seconds')->nullable();
            $table->unsignedInteger('gathering_seconds')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'source', 'external_id']);
            $table->index(['user_id', 'starts_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calendar_events');
    }
};
