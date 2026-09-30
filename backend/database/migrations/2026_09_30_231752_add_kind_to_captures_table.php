<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('captures', function (Blueprint $table): void {
            $table->string('kind', 32)->nullable();
            // No foreign key: the kind says which table the id is in.
            $table->ulid('routed_id')->nullable();
            $table->timestamp('kind_confirmed_at')->nullable();
            $table->json('parsed')->nullable();
            $table->timestamp('failed_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('captures', function (Blueprint $table): void {
            $table->dropColumn(['kind', 'routed_id', 'kind_confirmed_at', 'parsed', 'failed_at']);
        });
    }
};
