<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

final class StepControlRequest extends FormRequest
{
    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'step_id' => ['required', 'string'],
        ];
    }

    public function stepId(): string
    {
        return $this->string('step_id')->toString();
    }
}
