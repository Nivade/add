<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/** A control that acts on the session rather than a step says which moment of it the person was looking at. */
final class SessionControlRequest extends FormRequest
{
    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return ['seen_event_id' => ['required', 'string']];
    }

    public function seenEventId(): string
    {
        return $this->string('seen_event_id')->toString();
    }
}
