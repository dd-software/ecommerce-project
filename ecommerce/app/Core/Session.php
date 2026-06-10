<?php
declare(strict_types=1);

namespace App\Core;

class Session
{
    private static ?Session $instance = null;

    private function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            ini_set('session.cookie_httponly', '1');
            ini_set('session.cookie_secure', '0');
            ini_set('session.cookie_samesite', 'Lax');
            ini_set('session.use_strict_mode', '1');
            session_start();
        }
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    public function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    public function remove(string $key): void
    {
        unset($_SESSION[$key]);
    }

    public function isAuthenticated(): bool
    {
        return $this->has('usuario_id') && $this->has('usuario_rol');
    }

    public function getUsuarioId(): ?string
    {
        return $this->get('usuario_id');
    }

    public function getUsuarioRol(): ?string
    {
        return $this->get('usuario_rol');
    }

    public function isAdmin(): bool
    {
        return in_array($this->getUsuarioRol(), ['admin', 'supervisor']);
    }

    public function isAdminOrEmpleado(): bool
    {
        return in_array($this->getUsuarioRol(), ['admin', 'supervisor', 'empleado']);
    }

    public function login(array $usuario): void
    {
        $this->set('usuario_id', $usuario['id']);
        $this->set('usuario_nombre', $usuario['nombre']);
        $this->set('usuario_email', $usuario['email']);
        $this->set('usuario_rol', $usuario['rol']);
        $this->set('usuario_activo', $usuario['activo']);
        session_regenerate_id(true);
    }

    public function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params['path'], $params['domain'],
                $params['secure'], $params['httponly']
            );
        }
        session_destroy();
    }

    public function regenerate(): void
    {
        session_regenerate_id(true);
    }
}
