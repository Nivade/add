<?php

declare(strict_types=1);

namespace App\Support\Ai\Schemas;

use Closure;
use Illuminate\Contracts\JsonSchema\JsonSchema;

final class DecomposeIntentionSchema
{
    public const string VERSION = '1';

    /** @return Closure(JsonSchema):array<string, mixed> */
    public static function builder(): Closure
    {
        return fn (JsonSchema $schema): array => [
            'steps' => $schema->array()
                ->items($schema->object([
                    'title' => $schema->string()
                        ->description('One physical action, starting with a verb, doable without deciding anything else.')
                        ->required(),
                    'estimated_seconds' => $schema->integer()
                        ->description('How long this takes for someone who is not in the mood.')
                        ->required(),
                ]))
                ->description('Ordered. The first one must be startable right now.')
                ->required(),
        ];
    }
}
