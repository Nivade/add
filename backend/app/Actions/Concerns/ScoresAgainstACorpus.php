<?php

declare(strict_types=1);

namespace App\Actions\Concerns;

use Illuminate\Support\Facades\File;

/** An eval reads its corpus from storage/ai-eval and writes its baseline beside it. */
trait ScoresAgainstACorpus
{
    /** @return list<array<string, mixed>> */
    private function readCorpus(string $file): array
    {
        /** @var list<array<string, mixed>> */
        return json_decode(File::get(storage_path('ai-eval/'.$file)), true, flags: JSON_THROW_ON_ERROR);
    }

    /** @param  list<array<string, mixed>>  $scored */
    private function writeBaseline(string $file, string $key, array $scored): void
    {
        File::put(storage_path('ai-eval/'.$file), json_encode([
            'scored_at' => now()->toIso8601String(),
            'model' => config('ai.openai.model'),
            $key => $scored,
        ], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR)."\n");
    }
}
