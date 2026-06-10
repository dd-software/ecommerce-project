<?php
declare(strict_types=1);

namespace App\Core;

class Validator
{
    public static function uuid(string $value): bool
    {
        return preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $value) === 1;
    }

    public static function email(string $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
    }

    public static function precio(float $value): bool
    {
        return $value > 0;
    }

    public static function cantidad(int $value): bool
    {
        return $value > 0;
    }

    public static function stockNoNegativo(int $value): bool
    {
        return $value >= 0;
    }

    public static function password(string $value): bool
    {
        // Mínimo 8 caracteres, al menos una mayúscula, al menos un número
        return strlen($value) >= 8 
            && preg_match('/[A-Z]/', $value) === 1
            && preg_match('/[0-9]/', $value) === 1;
    }

    public static function nombre(string $value): bool
    {
        $len = mb_strlen($value);
        return $len >= 2 && $len <= 100;
    }

    public static function sanitizeHtml(string $value): string
    {
        return strip_tags($value, '<p><b><i><strong><em><ul><ol><li><br><a>');
    }
}
