<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('captures', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('body');
            $table->string('source', 32);
            $table->foreignUlid('intention_id')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamp('created_at');

            $table->index(['user_id', 'processed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('captures');
    }
};
