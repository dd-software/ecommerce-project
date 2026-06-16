<?php
// ============================================================
// API JSON: Autenticación de Usuarios (AJAX)
// ============================================================
// [PEDAGÓGICO] Endpoint que maneja autenticación via AJAX.
// Las peticiones se envían con action=XXX via POST.
//
// Acciones:
//   login    -> Validar credenciales, iniciar sesión, fusionar carrito
//   registro -> Validar datos, crear usuario, iniciar sesión
//   logout   -> Destruir sesión
//   status   -> Devolver si está logueado + datos del usuario
//
// Todas responden JSON: {success: bool, data: array, message: string}
// ============================================================

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/funciones.php';

// Forzar respuesta JSON
header('Content-Type: application/json; charset=utf-8');

$pdo = getDB();
$respuesta = [
    'success' => false,
    'data'    => [],
    'message' => '',
];

try {
    // ============================================================
    // Validar método HTTP
    // ============================================================
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido. Usa POST.');
    }

    $accion = trim($_POST['action'] ?? '');
    if (empty($accion)) {
        throw new Exception('Parámetro action es requerido.');
    }

    // ============================================================
    // Switcher de acciones
    // ============================================================
    switch ($accion) {

        // ============================================================
        // LOGIN: Iniciar sesión
        // ============================================================
        case 'login':
            // Validar CSRF
            $token = $_POST['_csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
            if (!csrf_validar($token)) {
                throw new Exception('Error de seguridad. Recarga la página.');
            }

            $email    = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';

            if (empty($email) || empty($password)) {
                throw new Exception('Por favor ingresa tu email y contraseña.');
            }

            // ============================================================
            // Rate Limiting: controlar intentos fallidos
            // ============================================================
            // [PEDAGÓGICO] Guardamos en sesión el número de intentos
            // y el timestamp del primero. Si supera 5 intentos en
            // menos de 15 minutos, bloqueamos temporalmente.
            if (!isset($_SESSION['login_intentos'])) {
                $_SESSION['login_intentos'] = 0;
                $_SESSION['login_bloqueo_hasta'] = null;
            }

            if ($_SESSION['login_bloqueo_hasta'] !== null) {
                if (time() < $_SESSION['login_bloqueo_hasta']) {
                    $minutos_restantes = ceil(($_SESSION['login_bloqueo_hasta'] - time()) / 60);
                    throw new Exception("⏱️ Demasiados intentos. Espera {$minutos_restantes} minuto(s).");
                }
                // Reiniciar si pasó el bloqueo
                $_SESSION['login_intentos'] = 0;
                $_SESSION['login_bloqueo_hasta'] = null;
            }

            // Buscar usuario por email
            $stmt = $pdo->prepare("
                SELECT id, nombre, apellido, email, password, rol, activo
                FROM usuarios
                WHERE email = :email
            ");
            $stmt->execute([':email' => $email]);
            $usuario = $stmt->fetch();

            // Validar credenciales
            if (!$usuario) {
                $_SESSION['login_intentos']++;
                if ($_SESSION['login_intentos'] >= 5) {
                    $_SESSION['login_bloqueo_hasta'] = time() + (15 * 60);
                }
                throw new Exception('Email o contraseña incorrectos.');
            }

            if ((int) $usuario['activo'] !== 1) {
                throw new Exception('Tu cuenta está desactivada. Contacta al administrador.');
            }

            if (!password_verify($password, $usuario['password'])) {
                $_SESSION['login_intentos']++;
                if ($_SESSION['login_intentos'] >= 5) {
                    $_SESSION['login_bloqueo_hasta'] = time() + (15 * 60);
                }
                throw new Exception('Email o contraseña incorrectos.');
            }

            // ============================================================
            // LOGIN EXITOSO
            // ============================================================
            // [PEDAGÓGICO] Almacenamos datos básicos en sesión.
            // NUNCA guardamos la contraseña en sesión.
            $_SESSION['usuario_id']     = (int) $usuario['id'];
            $_SESSION['usuario_nombre'] = $usuario['nombre'];
            $_SESSION['usuario_email']  = $usuario['email'];
            $_SESSION['usuario_rol']    = $usuario['rol'];

            // Actualizar fecha de último acceso
            $stmt = $pdo->prepare("UPDATE usuarios SET ultimo_acceso = NOW() WHERE id = :id");
            $stmt->execute([':id' => $usuario['id']]);

            // Limpiar contador de intentos
            unset($_SESSION['login_intentos']);
            unset($_SESSION['login_bloqueo_hasta']);

            // ============================================================
            // Fusionar carrito de invitado con el de la BD
            // ============================================================
            // [PEDAGÓGICO] Si el usuario tenía items en sesión (invitado),
            // los migramos a la BD al iniciar sesión.
            if (!empty($_SESSION['carrito'])) {
                foreach ($_SESSION['carrito'] as $prod_id => $item) {
                    // Verificar si ya existe en BD
                    $stmt = $pdo->prepare("
                        SELECT id, cantidad FROM items_carrito
                        WHERE usuario_id = :uid AND producto_id = :pid
                    ");
                    $stmt->execute([
                        ':uid' => $usuario['id'],
                        ':pid' => $prod_id,
                    ]);
                    $existente = $stmt->fetch();

                    if ($existente) {
                        // Sumar cantidades
                        $stmt = $pdo->prepare("
                            UPDATE items_carrito
                            SET cantidad = cantidad + :cant
                            WHERE id = :id
                        ");
                        $stmt->execute([
                            ':cant' => $item['cantidad'],
                            ':id'   => $existente['id'],
                        ]);
                    } else {
                        // Insertar nuevo
                        $stmt = $pdo->prepare("
                            INSERT INTO items_carrito (sesion_id, usuario_id, producto_id, cantidad, precio_unitario)
                            VALUES (:sesion, :uid, :pid, :cant, :precio)
                        ");
                        $stmt->execute([
                            ':sesion' => session_id(),
                            ':uid'    => $usuario['id'],
                            ':pid'    => $prod_id,
                            ':cant'   => $item['cantidad'],
                            ':precio' => $item['precio'],
                        ]);
                    }
                }
                // Limpiar carrito de sesión
                unset($_SESSION['carrito']);
            }

            // Construir respuesta con datos del usuario
            $respuesta['success'] = true;
            $respuesta['data'] = [
                'id'     => (int) $usuario['id'],
                'nombre' => $usuario['nombre'],
                'email'  => $usuario['email'],
                'rol'    => $usuario['rol'],
            ];
            $respuesta['message'] = '✅ Inicio de sesión exitoso. Bienvenido ' . $usuario['nombre'] . '.';
            break;

        // ============================================================
        // REGISTRO: Crear nueva cuenta
        // ============================================================
        case 'registro':
            // Validar CSRF
            $token = $_POST['_csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
            if (!csrf_validar($token)) {
                throw new Exception('Error de seguridad. Recarga la página.');
            }

            $nombre   = trim($_POST['nombre'] ?? '');
            $apellido = trim($_POST['apellido'] ?? '');
            $email    = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $password_confirmar = $_POST['password_confirmar'] ?? '';

            // ============================================================
            // Validar datos del formulario
            // ============================================================
            if (empty($nombre) || empty($apellido) || empty($email) || empty($password)) {
                throw new Exception('Todos los campos son obligatorios.');
            }

            // Validar nombre (solo letras y espacios)
            if (!preg_match('/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/', $nombre)) {
                throw new Exception('El nombre solo debe contener letras.');
            }

            if (!preg_match('/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/', $apellido)) {
                throw new Exception('El apellido solo debe contener letras.');
            }

            // Validar formato de email
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception('El formato del email no es válido.');
            }

            // Validar longitud de contraseña (mínimo 8 caracteres)
            if (strlen($password) < 8) {
                throw new Exception('La contraseña debe tener al menos 8 caracteres.');
            }

            // Validar que las contraseñas coincidan
            if ($password !== $password_confirmar) {
                throw new Exception('Las contraseñas no coinciden.');
            }

            // Verificar que el email no esté registrado
            $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = :email");
            $stmt->execute([':email' => $email]);
            if ($stmt->fetch()) {
                throw new Exception('Este email ya está registrado. ¿Olvidaste tu contraseña?');
            }

            // ============================================================
            // Crear usuario en la base de datos
            // ============================================================
            // [PEDAGÓGICO] password_hash() con PASSWORD_BCRYPT genera un hash
            // seguro de 60 caracteres. El costo (12) es el número de rondas
            // de hash: más alto es más seguro pero más lento.
            $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

            $stmt = $pdo->prepare("
                INSERT INTO usuarios (nombre, apellido, email, password, rol, activo, fecha_registro)
                VALUES (:nombre, :apellido, :email, :password, 'cliente', 1, NOW())
            ");
            $stmt->execute([
                ':nombre'   => $nombre,
                ':apellido' => $apellido,
                ':email'    => $email,
                ':password' => $hash,
            ]);

            $usuario_id = (int) $pdo->lastInsertId();

            // ============================================================
            // Iniciar sesión automáticamente después del registro
            // ============================================================
            $_SESSION['usuario_id']     = $usuario_id;
            $_SESSION['usuario_nombre'] = $nombre;
            $_SESSION['usuario_email']  = $email;
            $_SESSION['usuario_rol']    = 'cliente';

            $respuesta['success'] = true;
            $respuesta['data'] = [
                'id'     => $usuario_id,
                'nombre' => $nombre,
                'email'  => $email,
                'rol'    => 'cliente',
            ];
            $respuesta['message'] = '✅ Cuenta creada exitosamente. ¡Bienvenido ' . $nombre . '!';
            break;

        // ============================================================
        // LOGOUT: Cerrar sesión
        // ============================================================
        case 'logout':
            // Validar CSRF para acciones de mutación
            $token = $_POST['_csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
            if (!csrf_validar($token)) {
                throw new Exception('Error de seguridad. Recarga la página.');
            }

            // [PEDAGÓGICO] Destruir la sesión completa. Esto elimina
            // todas las variables de sesión y la cookie del navegador.
            $_SESSION = [];

            // Eliminar la cookie de sesión
            if (ini_get('session.use_cookies')) {
                $params = session_get_cookie_params();
                setcookie(
                    session_name(),
                    '',
                    time() - 42000,
                    $params['path'],
                    $params['domain'],
                    $params['secure'],
                    $params['httponly']
                );
            }

            session_destroy();

            $respuesta['success'] = true;
            $respuesta['data']    = [];
            $respuesta['message'] = '👋 Sesión cerrada correctamente.';
            break;

        // ============================================================
        // STATUS: Verificar estado de autenticación
        // ============================================================
        case 'status':
            // [PEDAGÓGICO] No requiere CSRF porque es solo lectura.
            // Devuelve si el usuario está logueado y sus datos básicos.
            $logueado = esta_logueado();

            $respuesta['success'] = true;
            $respuesta['data'] = [
                'logueado' => $logueado,
                'usuario'  => $logueado ? usuario_actual() : null,
            ];
            $respuesta['message'] = $logueado
                ? 'Usuario autenticado.'
                : 'Usuario no autenticado.';
            break;

        // ============================================================
        // Acción no reconocida
        // ============================================================
        default:
            throw new Exception("Acción desconocida: {$accion}");
    }

} catch (Exception $e) {
    $respuesta['success'] = false;
    $respuesta['message'] = $e->getMessage();
}

die(json_encode($respuesta, JSON_UNESCAPED_UNICODE));
