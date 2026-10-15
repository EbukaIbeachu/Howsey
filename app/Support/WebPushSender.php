<?php

namespace App\Support;

use App\Models\PushSubscription;
use App\Models\User;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class WebPushSender
{
    public static function configured(): bool
    {
        return filled(self::publicKey()) && filled(self::privateKey()) && filled(self::subject());
    }

    public static function publicKey(): ?string
    {
        return self::trimConfigValue(config('services.web_push.public_key'));
    }

    public static function privateKey(): ?string
    {
        return self::trimConfigValue(config('services.web_push.private_key'));
    }

    public static function subject(): ?string
    {
        return self::trimConfigValue(config('services.web_push.subject'));
    }

    public static function sendToUser(User $user): void
    {
        if (! self::configured()) {
            return;
        }

        $user->loadMissing('pushSubscriptions');

        foreach ($user->pushSubscriptions as $subscription) {
            self::sendToSubscription($subscription);
        }
    }

    public static function sendToSubscription(PushSubscription $subscription): void
    {
        if (! self::configured() || blank($subscription->endpoint)) {
            return;
        }

        $authorizationHeader = self::buildAuthorizationHeader($subscription->endpoint);
        if ($authorizationHeader === null) {
            return;
        }

        try {
            $client = new Client([
                'timeout' => 10,
                'connect_timeout' => 5,
                'http_errors' => false,
            ]);

            $response = $client->post($subscription->endpoint, [
                'headers' => [
                    'Authorization' => $authorizationHeader,
                    'TTL' => '60',
                    'Urgency' => 'normal',
                    'Content-Length' => '0',
                ],
                'body' => '',
            ]);

            $statusCode = $response->getStatusCode();

            if (in_array($statusCode, [201, 202, 204], true)) {
                $subscription->forceFill(['last_seen_at' => now()])->save();
                return;
            }

            if (in_array($statusCode, [404, 410], true)) {
                $subscription->delete();
                return;
            }

            Log::warning('Web Push delivery returned a non-success response.', [
                'subscription_id' => $subscription->id,
                'status' => $statusCode,
            ]);
        } catch (\Throwable $exception) {
            Log::warning('Web Push delivery failed.', [
                'subscription_id' => $subscription->id,
                'message' => $exception->getMessage(),
            ]);
        }
    }

    private static function buildAuthorizationHeader(string $endpoint): ?string
    {
        $jwt = self::createVapidJwt($endpoint);
        $publicKey = self::publicKey();

        if ($jwt === null || blank($publicKey)) {
            return null;
        }

        return 'vapid t='.$jwt.', k='.$publicKey;
    }

    private static function createVapidJwt(string $endpoint): ?string
    {
        $audience = self::subscriptionEndpointOrigin($endpoint);
        $subject = self::subject();
        $privateKey = self::normalizePrivateKey(self::privateKey());

        if (blank($audience) || blank($subject) || blank($privateKey)) {
            return null;
        }

        $header = self::base64UrlEncode(json_encode([
            'typ' => 'JWT',
            'alg' => 'ES256',
        ], JSON_UNESCAPED_SLASHES));

        $payload = self::base64UrlEncode(json_encode([
            'aud' => $audience,
            'exp' => time() + (12 * 60 * 60),
            'sub' => $subject,
        ], JSON_UNESCAPED_SLASHES));

        $unsignedToken = $header.'.'.$payload;
        $privateKeyResource = openssl_pkey_get_private($privateKey);

        if ($privateKeyResource === false) {
            Log::warning('Web Push private key could not be loaded.');
            return null;
        }

        $signature = '';
        $signed = openssl_sign($unsignedToken, $signature, $privateKeyResource, OPENSSL_ALGO_SHA256);
        openssl_free_key($privateKeyResource);

        if (! $signed) {
            Log::warning('Web Push JWT signing failed.');
            return null;
        }

        $joseSignature = self::convertDerSignatureToJose($signature, 64);
        if ($joseSignature === null) {
            Log::warning('Web Push signature conversion failed.');
            return null;
        }

        return $unsignedToken.'.'.self::base64UrlEncode($joseSignature);
    }

    private static function convertDerSignatureToJose(string $derSignature, int $partLength): ?string
    {
        $offset = 0;

        if (ord($derSignature[$offset]) !== 0x30) {
            return null;
        }

        $offset++;
        $length = ord($derSignature[$offset]);
        if ($length & 0x80) {
            $offset += ($length & 0x1f) + 1;
        } else {
            $offset++;
        }

        if (ord($derSignature[$offset]) !== 0x02) {
            return null;
        }

        $offset++;
        $rLength = ord($derSignature[$offset]);
        $offset++;
        $r = substr($derSignature, $offset, $rLength);
        $offset += $rLength;

        if (ord($derSignature[$offset]) !== 0x02) {
            return null;
        }

        $offset++;
        $sLength = ord($derSignature[$offset]);
        $offset++;
        $s = substr($derSignature, $offset, $sLength);

        $r = ltrim($r, "\x00");
        $s = ltrim($s, "\x00");

        $r = str_pad($r, $partLength / 2, "\x00", STR_PAD_LEFT);
        $s = str_pad($s, $partLength / 2, "\x00", STR_PAD_LEFT);

        return $r.$s;
    }

    private static function subscriptionEndpointOrigin(string $endpoint): ?string
    {
        $parts = parse_url($endpoint);
        if (! is_array($parts) || empty($parts['scheme']) || empty($parts['host'])) {
            return null;
        }

        $origin = $parts['scheme'].'://'.$parts['host'];
        if (! empty($parts['port'])) {
            $origin .= ':'.$parts['port'];
        }

        return $origin;
    }

    private static function normalizePrivateKey(?string $privateKey): ?string
    {
        if (blank($privateKey)) {
            return null;
        }

        return str_replace(["\r\n", '\n', '\r'], [PHP_EOL, PHP_EOL, PHP_EOL], $privateKey);
    }

    private static function trimConfigValue(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed !== '' ? $trimmed : null;
    }

    private static function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
