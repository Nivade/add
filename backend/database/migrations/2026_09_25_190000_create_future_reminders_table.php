<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('future_reminders', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('message');
            $table->timestamp('trigger_at');
            $table->foreignUlid('calendar_event_id')->nullable()->constrained()->cascadeOnDelete();
            $table->integer('offset_seconds')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'sent_at', 'trigger_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('future_reminders');
    }
};
