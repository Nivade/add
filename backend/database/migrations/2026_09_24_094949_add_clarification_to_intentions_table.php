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
            // An open question is one with no answer yet, so the old flag had nothing left to say.
            $table->text('clarifying_question')->nullable()->after('status');
            $table->text('clarification')->nullable()->after('clarifying_question');
            $table->dropColumn('needs_clarification');
        });
    }

    public function down(): void
    {
        Schema::table('intentions', function (Blueprint $table): void {
            $table->boolean('needs_clarification')->default(false)->after('status');
            $table->dropColumn(['clarifying_question', 'clarification']);
        });
    }
};
