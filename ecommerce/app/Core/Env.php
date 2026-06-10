<?php
declare(strict_types=1);

namespace App\Core;

class Env
{
    private static ?Env $instance = null;
    private array $vars = [];

    private function __construct()
    {
        $envFile = dirname(__DIR__, 2) . '/.env';
        if (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                $line = trim($line);
                if ($line === '' || str_starts_with($line, '#')) {
                    continue;
                }
                $pos = strpos($line, '=');
                if ($pos !== false) {
                    $key = trim(substr($line, 0, $pos));
                    $value = trim(substr($line, $pos + 1));
                    $value = trim($value, '"\'');
                    $this->vars[$key] = $value;
                }
            }
        }
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->vars[$key] ?? $default;
    }

    public function set(string $key, mixed $value): void
    {
        $this->vars[$key] = $value;
    }

    public function all(): array
    {
        return $this->vars;
    }
}
