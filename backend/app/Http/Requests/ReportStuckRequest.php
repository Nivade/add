<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\StuckReason;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class ReportStuckRequest extends FormRequest
{
    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'step_id' => ['required', 'string'],
            'reason' => ['required', Rule::enum(StuckReason::class)],
            'note' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function stepId(): string
    {
        return $this->string('step_id')->toString();
    }

    public function reason(): StuckReason
    {
        return $this->enum('reason', StuckReason::class) ?? StuckReason::SomethingElse;
    }

    public function note(): ?string
    {
        $note = $this->string('note')->toString();

        return $note === '' ? null : $note;
    }
}
