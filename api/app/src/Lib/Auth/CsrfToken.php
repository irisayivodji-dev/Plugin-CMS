<?php

namespace App\Lib\Auth;

class CsrfToken
{
    private const TOKEN_KEY = 'csrf_token';
    private const TOKEN_EXPIRY = 3600; // 1 heure

    public static function generate(): string
    {
        Session::start();
        $token = bin2hex(random_bytes(32));
        Session::set(self::TOKEN_KEY, [
            'token' => $token,
            'expires' => time() + self::TOKEN_EXPIRY
        ]);
        return $token;
    }

    public static function get(): ?string
    {
        Session::start();
        $data = Session::get(self::TOKEN_KEY);
        
        if (!$data || !isset($data['token']) || !isset($data['expires'])) {
            return null;
        }

        if (time() > $data['expires']) {
            Session::remove(self::TOKEN_KEY);
            return null;
        }

        return $data['token'];
    }

    public static function validate(string $token): bool
    {
        $storedToken = self::get();
        
        if (!$storedToken) {
            return false;
        }

        return hash_equals($storedToken, $token);
    }

    public static function regenerate(): string
    {
        Session::remove(self::TOKEN_KEY);
        return self::generate();
    }
}
