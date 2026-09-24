<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

final class ClarifyIntentionRequest extends FormRequest
{
    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'answer' => ['required', 'string', 'max:2000'],
        ];
    }

    public function answer(): string
    {
        return $this->string('answer')->toString();
    }
}
