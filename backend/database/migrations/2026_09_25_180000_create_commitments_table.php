<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commitments', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('intention_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->foreignUlid('step_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->string('description');
            $table->string('provenance', 32);
            $table->timestamp('confirmed_at')->nullable();
            $table->string('status', 32)->default('open');
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commitments');
    }
};
