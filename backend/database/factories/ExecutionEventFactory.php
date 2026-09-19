<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ExecutionEventType;
use App\Models\ExecutionEvent;
use App\Models\ExecutionSession;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ExecutionEvent> */
class ExecutionEventFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'execution_session_id' => ExecutionSession::factory(),
            'step_id' => null,
            'type' => ExecutionEventType::Started,
            'payload' => null,
        ];
    }
}
