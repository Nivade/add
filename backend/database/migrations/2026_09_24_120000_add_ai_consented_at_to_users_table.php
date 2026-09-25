<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            // Null means never asked or withdrawn; choosing the openai driver for this person is the consent it records.
            $table->timestamp('ai_consented_at')->nullable()->after('calendar_feed_url');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('ai_consented_at');
        });
    }
};
