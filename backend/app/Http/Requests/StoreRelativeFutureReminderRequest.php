<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

final class StoreRelativeFutureReminderRequest extends FormRequest
{
    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'message' => ['required', 'string', 'max:500'],
            'offset_minutes' => ['required', 'integer'],
        ];
    }

    public function message(): string
    {
        return $this->string('message')->toString();
    }

    public function offsetSeconds(): int
    {
        return $this->integer('offset_minutes') * 60;
    }
}
