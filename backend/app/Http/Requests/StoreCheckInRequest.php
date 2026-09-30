<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\CheckInAnswer;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreCheckInRequest extends FormRequest
{
    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'response' => ['required', Rule::enum(CheckInAnswer::class)],
        ];
    }

    public function answer(): CheckInAnswer
    {
        return $this->enum('response', CheckInAnswer::class);
    }
}
