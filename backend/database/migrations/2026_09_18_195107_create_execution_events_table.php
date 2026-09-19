<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('execution_events', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('execution_session_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('step_id')->nullable();
            $table->string('type', 32);
            $table->json('payload')->nullable();
            $table->timestamp('created_at');

            $table->index(['execution_session_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('execution_events');
    }
};
