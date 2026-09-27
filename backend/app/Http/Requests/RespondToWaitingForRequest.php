<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\WaitingForResponse;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class RespondToWaitingForRequest extends FormRequest
{
    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'response' => ['required', Rule::enum(WaitingForResponse::class)],
        ];
    }

    public function response(): WaitingForResponse
    {
        return $this->enum('response', WaitingForResponse::class);
    }
}
