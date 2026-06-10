<?php
declare(strict_types=1);

namespace App\Core;

class Logger
{
    private string $logDir;

    public function __construct()
    {
        $this->logDir = dirname(__DIR__, 2) . '/storage/logs';
        if (!is_dir($this->logDir)) {
            mkdir($this->logDir, 0775, true);
        }
    }

    public function info(string $message, array $context = []): void
    {
        $this->write('INFO', $message, $context);
    }

    public function error(string $message, array $context = []): void
    {
        $this->write('ERROR', $message, $context);
    }

    public function warning(string $message, array $context = []): void
    {
        $this->write('WARNING', $message, $context);
    }

    public function audit(string $modulo, string $accion, array $context = []): void
    {
        $this->write('AUDIT', "[$modulo] $accion", $context);
    }

    private function write(string $level, string $message, array $context = []): void
    {
        $date = date('Y-m-d');
        $file = $this->logDir . "/ecommerce-{$date}.log";
        
        $log = sprintf(
            "[%s] %s: %s %s\n",
            date('Y-m-d H:i:s'),
            $level,
            $message,
            $context ? json_encode($context, JSON_UNESCAPED_UNICODE) : ''
        );
        
        file_put_contents($file, $log, FILE_APPEND | LOCK_EX);
    }
}
