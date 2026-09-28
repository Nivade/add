<?php

declare(strict_types=1);

namespace App\Http\Requests\Settings;

use App\Support\Calendar\PublicFeedHost;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

final class CalendarFeedUpdateRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'url' => ['bail', 'required', 'string', 'max:2048', 'url:https,webcal', function (string $attribute, mixed $value, Closure $fail): void {
                if (! app(PublicFeedHost::class)->allows((string) $value)) {
                    $fail(__('That address is not a public calendar feed.'));
                }
            }],
        ];
    }
}
