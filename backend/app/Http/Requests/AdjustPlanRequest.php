<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\PlanRung;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class AdjustPlanRequest extends FormRequest
{
    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        $rules = [];

        foreach (PlanRung::cases() as $rung) {
            $rules[$rung->value] = ['sometimes', 'nullable', 'integer', 'min:0', 'max:1440'];
        }

        return $rules;
    }

    /** @return array<string, int|null> */
    public function minutes(): array
    {
        $minutes = [];

        foreach (PlanRung::cases() as $rung) {
            if ($this->has($rung->value)) {
                $stated = $this->input($rung->value);

                $minutes[$rung->value] = $stated === null || $stated === '' ? null : (int) $stated;
            }
        }

        return $minutes;
    }
}
