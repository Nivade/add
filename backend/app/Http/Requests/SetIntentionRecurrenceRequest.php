<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

final class SetIntentionRecurrenceRequest extends FormRequest
{
    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'every_days' => ['required', 'integer', 'min:1', 'max:365'],
        ];
    }

    public function everyDays(): int
    {
        return $this->integer('every_days');
    }
}
