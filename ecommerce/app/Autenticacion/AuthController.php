<?php
declare(strict_types=1);

namespace App\Autenticacion;

use App\Core\Database;
use App\Core\Response;
use App\Core\Request;
use App\Core\Session;
use App\Core\Auditoria;
use App\Core\Validator;

class AuthController
{
    private Database $db;
    private Auditoria $auditoria;
    private Session $session;

    private const MAX_LOGIN_ATTEMPTS = 5;
    private const LOCKOUT_MINUTES = 15;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->auditoria = new Auditoria();
        $this->session = Session::getInstance();
    }

    public function status(array $params = []): void
    {
        Response::success(['authenticated' => $this->session->isAuthenticated()]);
    }

    public function loginForm(array $params = []): void
    {
        $redirect = $_GET['redirect'] ?? 'catalogo';
        ?><!DOCTYPE html>
<html lang="es" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión — Ecommerce</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
</head>
<body class="bg-dark">
    <div class="container">
        <div class="row justify-content-center mt-5">
            <div class="col-md-5 col-lg-4">
                <div class="text-center mb-4">
                    <h2>🛒 Ecommerce</h2>
                </div>
                <div class="card bg-dark border-secondary">
                    <div class="card-body p-4">
                        <h4 class="card-title text-center mb-4">Iniciar Sesión</h4>
                        <form method="POST" action="/api/auth/login">
                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control bg-dark text-light" required autofocus>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Contraseña</label>
                                <input type="password" name="password" class="form-control bg-dark text-light" required>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Ingresar</button>
                        </form>
                        <div id="error-msg" class="alert alert-danger mt-3 d-none"></div>
                        <hr class="border-secondary">
                        <div class="text-center">
                            <small class="text-muted">¿No tienes cuenta?</small>
                            <a href="/registro" class="btn btn-outline-light btn-sm w-100 mt-1">Registrarse</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.querySelector('form').addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(e.target);
        const res = await fetch('/api/auth/login', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({email: formData.get('email'), password: formData.get('password')})
        });
        const data = await res.json();
        if (data.success) {
            window.location.href = '/<?= $redirect ?>';
        } else {
            document.getElementById('error-msg').textContent = data.message || 'Error al iniciar sesión';
            document.getElementById('error-msg').classList.remove('d-none');
        }
    });
    </script>
</body>
</html><?php
    }

    public function registroForm(array $params = []): void
    {
        ?><!DOCTYPE html>
<html lang="es" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrarse — Ecommerce</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
</head>
<body class="bg-dark">
    <div class="container">
        <div class="row justify-content-center mt-5">
            <div class="col-md-6 col-lg-5">
                <div class="text-center mb-4"><h2>🛒 Ecommerce</h2></div>
                <div class="card bg-dark border-secondary">
                    <div class="card-body p-4">
                        <h4 class="card-title text-center mb-4">Crear Cuenta</h4>
                        <form id="register-form">
                            <div class="row">
                                <div class="col-6 mb-3">
                                    <label class="form-label">Nombre</label>
                                    <input type="text" name="nombre" class="form-control bg-dark text-light" required>
                                </div>
                                <div class="col-6 mb-3">
                                    <label class="form-label">Apellido</label>
                                    <input type="text" name="apellido" class="form-control bg-dark text-light" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control bg-dark text-light" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Contraseña</label>
                                <input type="password" name="password" class="form-control bg-dark text-light" required minlength="8">
                                <small class="text-muted">Mínimo 8 caracteres, al menos una mayúscula y un número</small>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Confirmar Contraseña</label>
                                <input type="password" name="password_confirm" class="form-control bg-dark text-light" required>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Registrarse</button>
                        </form>
                        <div id="error-msg" class="alert alert-danger mt-3 d-none"></div>
                        <div id="success-msg" class="alert alert-success mt-3 d-none"></div>
                        <hr class="border-secondary">
                        <div class="text-center">
                            <small class="text-muted">¿Ya tienes cuenta?</small>
                            <a href="/login" class="btn btn-outline-light btn-sm w-100 mt-1">Iniciar Sesión</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.getElementById('register-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        const fd = new FormData(e.target);
        if (fd.get('password') !== fd.get('password_confirm')) {
            document.getElementById('error-msg').textContent = 'Las contraseñas no coinciden';
            document.getElementById('error-msg').classList.remove('d-none');
            return;
        }
        const res = await fetch('/api/auth/registro', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                nombre: fd.get('nombre'), apellido: fd.get('apellido'),
                email: fd.get('email'), password: fd.get('password')
            })
        });
        const data = await res.json();
        if (data.success) {
            document.getElementById('success-msg').textContent = 'Registro exitoso. Redirigiendo...';
            document.getElementById('success-msg').classList.remove('d-none');
            setTimeout(() => window.location.href = '/login', 1500);
        } else {
            document.getElementById('error-msg').textContent = data.message || 'Error al registrarse';
            document.getElementById('error-msg').classList.remove('d-none');
        }
    });
    </script>
</body>
</html><?php
    }

    public function login(array $params = []): void
    {
        $data = Request::validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        $email = $data['email'];
        $password = $data['password'];

        // Buscar usuario
        $usuario = $this->db->queryOne(
            'SELECT * FROM usuarios WHERE email = ?',
            [$email]
        );

        if (!$usuario || !password_verify($password, $usuario['password_hash'])) {
            $this->auditoria->registrar(null, 'Autenticacion', 'Intento fallido', null, null, [
                'email' => $email,
                'ip' => Request::ip()
            ]);
            Response::error('Usuario o contraseña incorrectos', 401);
            return;
        }

        // RN-004: usuarios deshabilitados no pueden iniciar sesión
        if (!$usuario['activo']) {
            $this->auditoria->registrar($usuario['id'], 'Autenticacion', 'Intento cuenta inactiva');
            Response::error('Usuario o contraseña incorrectos', 401);
            return;
        }

        // RN-AUT-002: Validar antes de crear sesión
        $this->session->login($usuario);
        
        // Actualizar último acceso
        $this->db->execute(
            'UPDATE usuarios SET ultimo_acceso = NOW() WHERE id = ?',
            [$usuario['id']]
        );

        // Crear sesión en BD
        $sessionId = Auditoria::generateUuid();
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', time() + 7200);
        
        $this->db->execute(
            'INSERT INTO sesiones (id, usuario_id, token, ip_address, user_agent, expires_at) VALUES (?, ?, ?, ?, ?, ?)',
            [$sessionId, $usuario['id'], $token, Request::ip(), Request::userAgent(), $expires]
        );

        $this->auditoria->registrar($usuario['id'], 'Autenticacion', 'Inicio de sesión exitoso');

        Response::success([
            'usuario' => [
                'id' => $usuario['id'],
                'nombre' => $usuario['nombre'],
                'email' => $usuario['email'],
                'rol' => $usuario['rol']
            ],
            'token' => $token
        ], 'Inicio de sesión exitoso');
    }

    public function registro(array $params = []): void
    {
        $data = Request::validate([
            'nombre' => 'required|min:2|max:100',
            'apellido' => 'required|min:2|max:100',
            'email' => 'required|email',
            'password' => 'required|min:8'
        ]);

        $password = $data['password'];
        
        // RN-012: Validar contraseña segura
        if (!Validator::password($password)) {
            Response::error('La contraseña debe tener al menos 8 caracteres, una mayúscula y un número');
            return;
        }

        // RN-011: Validar dominio de email
        if (!Validator::email($data['email'])) {
            Response::error('Formato de email inválido', 400);
            return;
        }

        // Verificar duplicado
        $exists = $this->db->queryOne('SELECT id FROM usuarios WHERE email = ?', [$data['email']]);
        if ($exists) {
            Response::error('El email ya está registrado', 409);
            return;
        }

        $id = Auditoria::generateUuid();
        $passwordHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

        $this->db->execute(
            'INSERT INTO usuarios (id, nombre, apellido, email, password_hash, rol) VALUES (?, ?, ?, ?, ?, ?)',
            [$id, $data['nombre'], $data['apellido'], $data['email'], $passwordHash, 'cliente']
        );

        // Crear carrito para el usuario
        $cartId = Auditoria::generateUuid();
        $this->db->execute('INSERT INTO carritos (id, usuario_id) VALUES (?, ?)', [$cartId, $id]);

        $this->auditoria->registrar($id, 'Autenticacion', 'Registro de usuario');

        Response::success(['id' => $id], 'Registro exitoso', 201);
    }

    public function logout(array $params = []): void
    {
        $usuarioId = $this->session->getUsuarioId();
        if ($usuarioId) {
            $this->auditoria->registrar($usuarioId, 'Autenticacion', 'Cierre de sesión');
        }

        $this->session->logout();

        if (Request::isAjax()) {
            Response::success(null, 'Sesión cerrada');
        }

        header('Location: /');
        exit;
    }
}
