<?php

declare(strict_types=1);

namespace App\Http\Requests\Concerns;

/** A step control says which step it was pressed for, so a second tap cannot land on the next one. */
trait NamesTheStep
{
    /** @return array{step_id: list<string>} */
    protected function stepRules(): array
    {
        return ['step_id' => ['required', 'string']];
    }

    public function stepId(): string
    {
        return $this->string('step_id')->toString();
    }
}
