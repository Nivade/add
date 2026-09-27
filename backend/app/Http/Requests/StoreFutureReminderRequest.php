<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

final class StoreFutureReminderRequest extends FormRequest
{
    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'text' => ['required', 'string', 'max:500'],
        ];
    }

    public function text(): string
    {
        return $this->string('text')->toString();
    }
}
