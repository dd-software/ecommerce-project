<?php
declare(strict_types=1);

namespace App\Core;

class View
{
    private static string $layoutPath = '';
    private static array $globals = [];

    public static function setLayoutPath(string $path): void
    {
        self::$layoutPath = $path;
    }

    public static function share(string $key, mixed $value): void
    {
        self::$globals[$key] = $value;
    }

    public static function render(string $template, array $data = [], ?string $layout = null): string
    {
        $data = array_merge(self::$globals, $data);
        
        $content = self::renderFile($template, $data);
        
        if ($layout !== null) {
            $data['content'] = $content;
            return self::renderFile($layout, $data);
        }
        
        return $content;
    }

    private static function renderFile(string $template, array $data): string
    {
        $templatePath = self::$layoutPath ?: dirname(__DIR__, 1) . '/views';
        $file = $templatePath . '/' . $template . '.php';
        
        if (!file_exists($file)) {
            throw new \RuntimeException("Template not found: $template ($file)");
        }

        extract($data, EXTR_SKIP);
        
        ob_start();
        include $file;
        return ob_get_clean();
    }

    public static function component(string $component, array $data = []): string
    {
        return self::renderFile("components/$component", $data);
    }
}
