<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('steps', function (Blueprint $table): void {
            // Null is "anywhere", which describes most steps.
            $table->string('place', 16)->nullable()->after('estimated_seconds');
        });
    }

    public function down(): void
    {
        Schema::table('steps', function (Blueprint $table): void {
            $table->dropColumn('place');
        });
    }
};
