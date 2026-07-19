<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Http\Request;

final class WebhookAuthenticator
{
    public function authorized(Request $request, string $provider): bool
    {
        $expected = trim((string) config("webhooks.{$provider}.token"));

        if ($expected === '') {
            return false;
        }

        $received = trim((string) $request->bearerToken());

        if ($received === '') {
            foreach ((array) config("webhooks.{$provider}.headers", []) as $header) {
                $received = trim((string) $request->header((string) $header));

                if ($received !== '') {
                    break;
                }
            }
        }

        return $received !== '' && hash_equals($expected, $received);
    }
}
