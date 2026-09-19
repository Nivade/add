<?php

declare(strict_types=1);

namespace App\Http\Requests\Api;

use App\Enums\CaptureSource;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCaptureRequest extends FormRequest
{
    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'max:10000'],
            'source' => ['sometimes', Rule::enum(CaptureSource::class)],
        ];
    }

    public function source(): CaptureSource
    {
        return $this->enum('source', CaptureSource::class) ?? CaptureSource::Text;
    }
}
