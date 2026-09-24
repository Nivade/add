<?php

declare(strict_types=1);

namespace App\Support\Ai\Schemas;

use Closure;
use Illuminate\Contracts\JsonSchema\JsonSchema;

final class ParseCaptureSchema
{
    public const string VERSION = '3';

    /**
     * Strict mode requires every key, so "optional" is a nullable type, never an absent one.
     *
     * @return Closure(JsonSchema):array<string, mixed>
     */
    public static function builder(): Closure
    {
        return fn (JsonSchema $schema): array => [
            'title' => $schema->string()
                ->description('The intention, in the person\'s own words, as a short phrase.')
                ->required(),
            'why' => $schema->string()
                ->description('The reason they gave, if they gave one. Null otherwise.')
                ->nullable()
                ->required(),
            'deadline_at' => $schema->string()
                ->description('ISO 8601 datetime, only when the text names a real date or appointment. Null otherwise.')
                ->nullable()
                ->required(),
            'clarifying_question' => $schema->string()
                ->description('One short question to ask them, when the first step depends on their answer. Null otherwise.')
                ->nullable()
                ->required(),
        ];
    }
}
