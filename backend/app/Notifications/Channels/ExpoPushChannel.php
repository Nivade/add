<?php

declare(strict_types=1);

namespace App\Notifications\Channels;

use App\Contracts\ExpoPushable;
use App\Models\Device;
use App\Models\User;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/** Expo's push service, spoken to directly: one dependency less, and the payload is three keys. */
final class ExpoPushChannel
{
    private const string ENDPOINT = 'https://exp.host/--/api/v2/push/send';

    public function send(object $notifiable, Notification $notification): void
    {
        if (! $notifiable instanceof User || ! $notification instanceof ExpoPushable) {
            return;
        }

        /** @var list<string> $tokens */
        $tokens = $notifiable->devices()->pluck('push_token')->all();

        if ($tokens === []) {
            return;
        }

        $message = $notification->toExpo($notifiable);

        $response = Http::asJson()->post(self::ENDPOINT, array_map(
            fn (string $token): array => ['to' => $token, ...$message],
            $tokens,
        ));

        if ($response->failed()) {
            Log::warning('Expo rejected a push.', ['status' => $response->status()]);

            return;
        }

        $this->forgetDeadTokens($tokens, $response->json('data'));
    }

    /**
     * Expo answers per message, in order, and a device that uninstalled answers `DeviceNotRegistered`.
     *
     * @param  list<string>  $tokens
     */
    private function forgetDeadTokens(array $tokens, mixed $receipts): void
    {
        if (! is_array($receipts)) {
            return;
        }

        $dead = [];

        foreach (array_values($receipts) as $index => $receipt) {
            if (is_array($receipt) && ($receipt['details']['error'] ?? null) === 'DeviceNotRegistered') {
                $dead[] = $tokens[$index];
            }
        }

        if ($dead !== []) {
            Device::query()->whereIn('push_token', $dead)->delete();
        }
    }
}
