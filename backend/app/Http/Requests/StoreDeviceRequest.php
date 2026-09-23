<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\DevicePlatform;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreDeviceRequest extends FormRequest
{
    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'push_token' => ['required', 'string', 'max:255'],
            'platform' => ['required', Rule::enum(DevicePlatform::class)],
        ];
    }

    public function platform(): DevicePlatform
    {
        return $this->enum('platform', DevicePlatform::class);
    }
}
