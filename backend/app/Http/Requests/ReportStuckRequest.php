<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\StuckReason;
use App\Http\Requests\Concerns\NamesTheStep;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class ReportStuckRequest extends FormRequest
{
    use NamesTheStep;

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            ...$this->stepRules(),
            'reason' => ['required', Rule::enum(StuckReason::class)],
            'note' => ['nullable', 'string', 'max:2000'],
        ];
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
