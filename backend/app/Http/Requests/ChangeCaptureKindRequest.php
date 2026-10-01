<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\CaptureKind;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class ChangeCaptureKindRequest extends FormRequest
{
    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'kind' => ['required', Rule::enum(CaptureKind::class)->except([CaptureKind::NotForYou])],
        ];
    }

    public function kind(): CaptureKind
    {
        return $this->enum('kind', CaptureKind::class);
    }
}
