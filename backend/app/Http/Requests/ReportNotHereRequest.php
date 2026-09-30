<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\Place;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class ReportNotHereRequest extends FormRequest
{
    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'place' => ['required', Rule::enum(Place::class)],
        ];
    }

    public function place(): Place
    {
        return $this->enum('place', Place::class);
    }
}
