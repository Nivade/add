<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\CommitmentResponse;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class RespondToCommitmentRequest extends FormRequest
{
    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'response' => ['required', Rule::enum(CommitmentResponse::class)],
        ];
    }

    public function response(): CommitmentResponse
    {
        return $this->enum('response', CommitmentResponse::class);
    }
}
