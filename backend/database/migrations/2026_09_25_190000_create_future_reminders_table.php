<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Raw SQL, not Blueprint: Blueprint has no check(), and SQLite cannot ALTER TABLE
     * to add a CHECK constraint after creation, so "exactly one of trigger_at /
     * calendar_event_id" has to be baked into the CREATE TABLE statement itself.
     */
    public function up(): void
    {
        DB::statement(<<<'SQL'
            CREATE TABLE "future_reminders" (
                "id" varchar not null,
                "user_id" integer not null,
                "message" varchar not null,
                "trigger_at" datetime,
                "calendar_event_id" varchar,
                "offset_seconds" integer,
                "sent_at" datetime,
                "created_at" datetime,
                "updated_at" datetime,
                foreign key("user_id") references "users"("id") on delete cascade,
                foreign key("calendar_event_id") references "calendar_events"("id") on delete cascade,
                primary key ("id"),
                check ((trigger_at is null) != (calendar_event_id is null))
            )
            SQL);

        Schema::table('future_reminders', function ($table): void {
            $table->index('user_id');
            $table->index('calendar_event_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('future_reminders');
    }
};
