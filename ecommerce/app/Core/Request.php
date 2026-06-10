<?php
declare(strict_types=1);

namespace App\Core;

class Request
{
    public static function method(): string
    {
        return $_SERVER['REQUEST_METHOD'] ?? 'GET';
    }

    public static function uri(): string
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        // Remove query string
        $uri = parse_url($uri, PHP_URL_PATH);
        // Remove base path if needed
        return rtrim($uri, '/') ?: '/';
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return $_GET[$key] ?? $default;
    }

    public static function post(string $key, mixed $default = null): mixed
    {
        return $_POST[$key] ?? $default;
    }

    public static function input(string $key, mixed $default = null): mixed
    {
        $body = self::body();
        return $body[$key] ?? $_POST[$key] ?? $_GET[$key] ?? $default;
    }

    public static function body(): array
    {
        static $body = null;
        if ($body === null) {
            $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
            if (str_contains($contentType, 'application/json')) {
                $raw = file_get_contents('php://input');
                $body = json_decode($raw, true) ?? [];
            } else {
                $body = $_POST;
            }
        }
        return $body;
    }

    public static function all(): array
    {
        return array_merge($_GET, $_POST, self::body());
    }

    public static function header(string $key, mixed $default = null): mixed
    {
        $key = 'HTTP_' . strtoupper(str_replace('-', '_', $key));
        return $_SERVER[$key] ?? $default;
    }

    public static function ip(): string
    {
        return $_SERVER['HTTP_X_FORWARDED_FOR'] 
            ?? $_SERVER['HTTP_CLIENT_IP'] 
            ?? $_SERVER['REMOTE_ADDR'] 
            ?? '0.0.0.0';
    }

    public static function userAgent(): string
    {
        return $_SERVER['HTTP_USER_AGENT'] ?? '';
    }

    public static function isAjax(): bool
    {
        return strtolower(self::header('X-Requested-With', '')) === 'xmlhttprequest';
    }

    public static function validate(array $rules): array
    {
        $errors = [];
        $data = [];
        
        foreach ($rules as $field => $ruleList) {
            $ruleList = explode('|', $ruleList);
            $value = self::input($field);
            $data[$field] = $value;

            foreach ($ruleList as $rule) {
                if ($rule === 'required' && (is_null($value) || $value === '')) {
                    $errors[$field] = "El campo $field es obligatorio";
                    break;
                }
                if ($rule === 'email' && $value && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $errors[$field] = "El campo $field debe ser un email válido";
                    break;
                }
                if (str_starts_with($rule, 'min:') && $value) {
                    $min = (int) substr($rule, 4);
                    if (strlen((string)$value) < $min) {
                        $errors[$field] = "El campo $field debe tener al menos $min caracteres";
                        break;
                    }
                }
                if (str_starts_with($rule, 'max:') && $value) {
                    $max = (int) substr($rule, 4);
                    if (strlen((string)$value) > $max) {
                        $errors[$field] = "El campo $field debe tener máximo $max caracteres";
                        break;
                    }
                }
                if ($rule === 'numeric' && $value && !is_numeric($value)) {
                    $errors[$field] = "El campo $field debe ser numérico";
                    break;
                }
            }
        }

        if (!empty($errors)) {
            Response::json(['success' => false, 'errors' => $errors], 422);
            exit;
        }

        return $data;
    }

    public static function sanitize(string $value): string
    {
        return htmlspecialchars(strip_tags(trim($value)), ENT_QUOTES, 'UTF-8');
    }
}
