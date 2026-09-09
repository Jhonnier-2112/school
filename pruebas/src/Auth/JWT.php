<?php

namespace App\Auth;

class JWT {
    public static function generate(array $payload, string $secret, int $hours = 72): string {
        $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
        $now = time();
        $payload['iat'] = $now;
        $payload['nbf'] = $now;
        $payload['exp'] = $now + ($hours * 3600);
        $payload['iss'] = 'icfes-backend-php';

        $base64Header = self::base64UrlEncode($header);
        $base64Payload = self::base64UrlEncode(json_encode($payload));

        $signature = hash_hmac('sha256', "$base64Header.$base64Payload", $secret, true);
        $base64Signature = self::base64UrlEncode($signature);

        return "$base64Header.$base64Payload.$base64Signature";
    }

    public static function validate(string $token, string $secret): ?array {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return null;
        }

        [$base64Header, $base64Payload, $base64Signature] = $parts;

        $header = json_decode(self::base64UrlDecode($base64Header), true);
        if (!$header || ($header['alg'] ?? '') !== 'HS256') {
            return null;
        }

        $expectedSig = hash_hmac('sha256', "$base64Header.$base64Payload", $secret, true);
        if (!hash_equals($expectedSig, self::base64UrlDecode($base64Signature))) {
            return null;
        }

        $payload = json_decode(self::base64UrlDecode($base64Payload), true);
        if (!$payload) {
            return null;
        }

        $now = time();
        if (isset($payload['exp']) && $payload['exp'] < $now) {
            return null; // Expired
        }
        if (isset($payload['nbf']) && $payload['nbf'] > $now) {
            return null; // Not active yet
        }

        return $payload;
    }

    private static function base64UrlEncode(string $data): string {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private static function base64UrlDecode(string $data): string {
        return base64_decode(strtr($data, '-_', '+/'));
    }
}
