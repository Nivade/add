<?php

declare(strict_types=1);

namespace App\Support\Ai\Schemas;

use Closure;
use Illuminate\Contracts\JsonSchema\JsonSchema;

final class ClassifyIngestionSchema
{
    public const string VERSION = '1';

    /**
     * Strict mode requires every key, so "optional" is a nullable type, never an absent one.
     *
     * @return Closure(JsonSchema):array<string, mixed>
     */
    public static function builder(): Closure
    {
        return fn (JsonSchema $schema): array => [
            'actionable' => $schema->boolean()
                ->description('True only when a real deadline, renewal, payment or reply is named.')
                ->required(),
            'title' => $schema->string()
                ->description('The thing itself, a few words, only when actionable. Null otherwise.')
                ->nullable()
                ->required(),
            'why' => $schema->string()
                ->description('The one line explaining it, only when actionable. Null otherwise.')
                ->nullable()
                ->required(),
            'deadline_at' => $schema->string()
                ->description('ISO 8601 datetime, only when the text names a real date. Null otherwise.')
                ->nullable()
                ->required(),
            'estimated_seconds' => $schema->integer()
                ->description('Rough effort estimate in seconds, only when actionable. Null otherwise.')
                ->nullable()
                ->required(),
        ];
    }
}
