<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const string BOTH_OR_NEITHER = '(trigger_at is null) = (calendar_event_id is null)';

    public function up(): void
    {
        Schema::create('future_reminders', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('message');
            $table->timestamp('trigger_at')->nullable();
            $table->foreignUlid('calendar_event_id')->nullable()->constrained()->cascadeOnDelete();
            $table->integer('offset_seconds')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });

        $this->requireExactlyOneTrigger();
    }

    public function down(): void
    {
        Schema::dropIfExists('future_reminders');
    }

    /** SQLite cannot add a CHECK after creation, and MySQL forbids one on a cascading foreign key, so both get triggers. */
    private function requireExactlyOneTrigger(): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'pgsql') {
            DB::statement('alter table future_reminders add constraint future_reminders_exactly_one_trigger check (not ('.self::BOTH_OR_NEITHER.'))');

            return;
        }

        foreach (['insert', 'update'] as $event) {
            $condition = str_replace(['trigger_at', 'calendar_event_id'], ['new.trigger_at', 'new.calendar_event_id'], self::BOTH_OR_NEITHER);

            DB::unprepared($driver === 'sqlite'
                ? "create trigger future_reminders_exactly_one_trigger_{$event} before {$event} on future_reminders when {$condition} begin select raise(abort, 'future_reminders needs exactly one trigger'); end"
                : "create trigger future_reminders_exactly_one_trigger_{$event} before {$event} on future_reminders for each row begin if {$condition} then signal sqlstate '23000' set message_text = 'future_reminders needs exactly one trigger'; end if; end");
        }
    }
};
