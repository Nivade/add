<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

final class CorrectDeadlineRequest extends FormRequest
{
    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'deadline_at' => ['nullable', 'date_format:Y-m-d\TH:i'],
        ];
    }

    /** What a `datetime-local` field sends is a wall clock, so it is read in the person's zone. */
    public function deadlineAt(User $user): ?CarbonImmutable
    {
        $value = $this->string('deadline_at')->toString();

        if ($value === '') {
            return null;
        }

        return CarbonImmutable::createFromFormat('Y-m-d\TH:i', $value, $user->timezone) ?: null;
    }
}
