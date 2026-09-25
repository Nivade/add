<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

final class StoreWaitingForRequest extends FormRequest
{
    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'subject' => ['required', 'string', 'max:255'],
            'note' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function subject(): string
    {
        return $this->string('subject')->toString();
    }

    public function note(): ?string
    {
        $note = $this->string('note')->toString();

        return $note === '' ? null : $note;
    }
}
