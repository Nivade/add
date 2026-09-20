<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('intentions', function (Blueprint $table): void {
            // Nobody types a deadline: every one of them was read out of their words, until they say it is right.
            $table->timestamp('deadline_confirmed_at')->nullable()->after('deadline_at');
        });
    }

    public function down(): void
    {
        Schema::table('intentions', function (Blueprint $table): void {
            $table->dropColumn('deadline_confirmed_at');
        });
    }
};
