<?php

declare(strict_types=1);

namespace App\Support\Ai\Schemas;

use App\Enums\Place;
use Closure;
use Illuminate\Contracts\JsonSchema\JsonSchema;

final class DecomposeIntentionSchema
{
    public const string VERSION = '2';

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
                    // nullable() does not add null to the enum, and strict mode rejects a null missing from it.
                    'place' => $schema->string()
                        ->enum([...array_column(Place::cases(), 'value'), null])
                        ->description('Where this has to happen, only when it cannot happen anywhere else: home, work, out (shops, outside, errands) or computer. Null when it can be done anywhere.')
                        ->nullable()
                        ->required(),
                ]))
                ->description('Ordered. The first one must be startable right now.')
                ->required(),
        ];
    }
}
