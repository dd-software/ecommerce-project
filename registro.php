<?php
// ============================================================
// Registro de Usuario
// ============================================================
// [PEDAGÓGICO] Formulario de registro con:
// - Validaciones del lado del servidor
// - Email único en BD
// - Password con requisitos mínimos de seguridad
// - Auto-login después del registro exitoso
// - Rol siempre 'cliente' (solo admin puede asignar admin)
// ============================================================

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/funciones.php';

// CORRECCIÓN REQUERIDA: Eliminamos la llamada prematura de header.php de esta sección 
// para evitar que se envíe salida HTML antes de ejecutar las funciones de redirección.

$pdo   = getDB();
$error = '';
$datos = [
    'nombre'   => '',
    'apellido' => '',
    'email'    => '',
];

// ============================================================
// Procesar formulario POST
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validar token CSRF
    $token = $_POST['_csrf_token'] ?? '';
    if (!csrf_validar($token)) {
        $error = 'Error de seguridad. Recarga la página.';
    } else {
        // Obtener y limpiar datos del formulario
        $datos['nombre']           = trim($_POST['nombre'] ?? '');
        $datos['apellido']         = trim($_POST['apellido'] ?? '');
        $datos['email']            = trim($_POST['email'] ?? '');
        $password                  = $_POST['password'] ?? '';
        $confirmar_password        = $_POST['confirmar_password'] ?? '';

        $errores = [];

        // ============================================================
        // Validar Nombre
        // ============================================================
        if (empty($datos['nombre'])) {
            $errores[] = 'El nombre es obligatorio.';
        } elseif (strlen($datos['nombre']) < 2) {
            $errores[] = 'El nombre debe tener al menos 2 caracteres.';
        }

        // ============================================================
        // Validar Apellido
        // ============================================================
        if (empty($datos['apellido'])) {
            $errores[] = 'El apellido es obligatorio.';
        } elseif (strlen($datos['apellido']) < 2) {
            $errores[] = 'El apellido debe tener al menos 2 caracteres.';
        }

        // ============================================================
        // Validar Email
        // ============================================================
        if (empty($datos['email'])) {
            $errores[] = 'El correo electrónico es obligatorio.';
        } elseif (!filter_var($datos['email'], FILTER_VALIDATE_EMAIL)) {
            $errores[] = 'El formato del correo electrónico no es válido.';
        } else {
            // [PEDAGÓGICO] Verificar que el email no esté registrado
            $stmt = $pdo->prepare("
                SELECT id FROM usuarios WHERE email = :email
            ");
            $stmt->execute([':email' => $datos['email']]);
            if ($stmt->fetch()) {
                $errores[] = 'Este correo electrónico ya está registrado.';
            }
        }

        // ============================================================
        // Validar Contraseña
        // ============================================================
        // [PEDAGÓGICO] Requisitos mínimos:
        // - 8+ caracteres
// - Al menos 1 mayúscula
// - Al menos 1 número
        if (empty($password)) {
            $errores[] = 'La contraseña es obligatoria.';
        } elseif (strlen($password) < 8) {
            $errores[] = 'La contraseña debe tener al menos 8 caracteres.';
        } elseif (!preg_match('/[A-Z]/', $password)) {
            $errores[] = 'La contraseña debe contener al menos una letra mayúscula.';
        } elseif (!preg_match('/[0-9]/', $password)) {
            $errores[] = 'La contraseña debe contener al menos un número.';
        }

        // ============================================================
        // Validar Confirmación de Contraseña
        // ============================================================
        if ($password !== $confirmar_password) {
            $errores[] = 'Las contraseñas no coinciden.';
        }

        // ============================================================
        // Si no hay errores, registrar usuario
        // ============================================================
        if (empty($errores)) {
            // [PEDAGÓGICO] password_hash con bcrypt (cost 12) es el
            // estándar actual para almacenar contraseñas de forma segura.
            $password_hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

            $stmt = $pdo->prepare("
                INSERT INTO usuarios (nombre, apellido, email, password, rol, activo, fecha_registro)
                VALUES (:nombre, :apellido, :email, :password, 'cliente', 1, NOW())
            ");
            $stmt->execute([
                ':nombre'   => $datos['nombre'],
                ':apellido' => $datos['apellido'],
                ':email'    => $datos['email'],
                ':password' => $password_hash,
            ]);

            $nuevo_id = (int) $pdo->lastInsertId();

            // ============================================================
            // Auto-login: iniciar sesión automáticamente
            // ============================================================
            $_SESSION['usuario_id']     = $nuevo_id;
            $_SESSION['usuario_nombre'] = $datos['nombre'];
            $_SESSION['usuario_email']  = $datos['email'];
            $_SESSION['usuario_rol']    = 'cliente';

            // Mensaje de éxito y redirección
            $_SESSION['exito'] = '✅ Cuenta creada exitosamente. ¡Bienvenido!';
            redireccionar('index.php');
        } else {
            // Unir errores en un solo mensaje
            $error = implode('<br>', $errores);
        }
    }
}

// SOLUCIÓN UBICACIÓN EXACTA: Cargamos la cabecera visual de la interfaz justo aquí, 
// una vez completadas con éxito todas las lógicas de procesamiento y redirección de PHP.
require_once __DIR__ . '/includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-8 col-lg-6">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <h2 class="text-center mb-4">📝 Crear Cuenta</h2>

                <?php if (esta_logueado()): ?>
                    <div class="alert alert-info" role="alert">
                        Ya tienes una sesión activa como
                        <strong><?= escapar($_SESSION['usuario_nombre']) ?></strong>.
                        <a href="index.php" class="alert-link">Ir al inicio</a>
                    </div>
                <?php else: ?>

                <?php if ($error): ?>
                    <div class="alert alert-danger" role="alert">
                        <?= $error ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="registro.php" novalidate>
                    <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="nombre" class="form-label">👤 Nombre</label>
                            <input type="text"
                                   id="nombre"
                                   name="nombre"
                                   class="form-control"
                                   placeholder="Tu nombre"
                                   value="<?= escapar($datos['nombre']) ?>"
                                   required>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="apellido" class="form-label">👤 Apellido</label>
                            <input type="text"
                                   id="apellido"
                                   name="apellido"
                                   class="form-control"
                                   placeholder="Tu apellido"
                                   value="<?= escapar($datos['apellido']) ?>"
                                   required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label">📧 Correo electrónico</label>
                        <input type="email"
                               id="email"
                               name="email"
                               class="form-control"
                               placeholder="ejemplo@correo.com"
                               value="<?= escapar($datos['email']) ?>"
                               required>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">🔒 Contraseña</label>
                        <input type="password"
                               id="password"
                               name="password"
                               class="form-control"
                               placeholder="Mínimo 8 caracteres, 1 mayúscula, 1 número"
                               required>
                        <div class="form-text">
                            Debe tener al menos <strong>8 caracteres</strong>,
                            una <strong>mayúscula</strong> y un <strong>número</strong>.
                        </div>
                    </div>

                    <div class="mb-4">
                        <label for="confirmar_password" class="form-label">🔒 Confirmar contraseña</label>
                        <input type="password"
                               id="confirmar_password"
                               name="confirmar_password"
                               class="form-control"
                               placeholder="Repite la contraseña"
                               required>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 py-2 mb-3">
                        📝 Crear Cuenta
                    </button>

                    <p class="text-center mb-0">
                        ¿Ya tienes cuenta?
                        <a href="login.php" class="text-decoration-none">Inicia sesión aquí</a>
                    </p>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/includes/footer.php';
?>