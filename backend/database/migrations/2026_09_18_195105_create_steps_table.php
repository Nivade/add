<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('steps', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('intention_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->unsignedSmallInteger('position');
            $table->unsignedInteger('estimated_seconds')->nullable();
            $table->string('status', 32);
            $table->unsignedSmallInteger('skip_count')->default(0);
            $table->boolean('generated')->default(false);
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('last_skipped_at')->nullable();
            $table->timestamps();

            $table->index(['intention_id', 'status', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('steps');
    }
};
