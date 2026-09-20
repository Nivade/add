<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reminders', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('appointment_kind', 32);
            $table->ulid('appointment_id');
            $table->timestamp('sent_at');
            $table->timestamps();

            // One reminder per appointment is what keeps this sparse rather than a stream.
            $table->unique(['user_id', 'appointment_kind', 'appointment_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reminders');
    }
};
