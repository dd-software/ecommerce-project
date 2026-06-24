<?php
// ============================================================
// Inicio de Sesión
// ============================================================
// [PEDAGÓGICO] Página de login con:
// - Validación de credenciales contra BD
// - Rate limiting simple (sesión) para evitar fuerza bruta
// - Token CSRF para prevenir ataques CSRF
// - Redirección a página anterior después del login exitoso
// ============================================================

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/funciones.php';

// ============================================================
// Verificar si ya está logueado
// ============================================================
// [PEDAGÓGICO] Si el usuario ya tiene sesión activa y hay un
// parámetro redirect, lo redirigimos directamente para que
// no vea el formulario de login.
$redirect = $_GET['redirect'] ?? '';
if (esta_logueado() && !empty($redirect)) {
    if (strpos($redirect, 'http') !== 0) {
        redireccionar($redirect);
    } else {
        redireccionar('index.php');
    }
}

$pdo    = getDB();
$error  = '';
$email  = '';

// ============================================================
// Procesar formulario POST
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validar token CSRF
    $token = $_POST['_csrf_token'] ?? '';
    if (!csrf_validar($token)) {
        $error = 'Error de seguridad. Recarga la página.';
    } else {
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

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

        // Verificar si está bloqueado
        if ($_SESSION['login_bloqueo_hasta'] !== null) {
            if (time() < $_SESSION['login_bloqueo_hasta']) {
                $minutos_restantes = ceil(($_SESSION['login_bloqueo_hasta'] - time()) / 60);
                $error = "⏱️ Demasiados intentos. Espera {$minutos_restantes} minuto(s).";
            } else {
                // Reiniciar contador si pasó el tiempo de bloqueo
                $_SESSION['login_intentos'] = 0;
                $_SESSION['login_bloqueo_hasta'] = null;
            }
        }

        // Si no hay error de rate limiting, validar credenciales
        if (empty($error)) {
            // Validar que los campos no estén vacíos
            if (empty($email) || empty($password)) {
                $error = 'Por favor ingresa tu email y contraseña.';
            } else {
                // Buscar usuario por email
                $stmt = $pdo->prepare("
                    SELECT id, nombre, email, password, rol, activo
                    FROM usuarios
                    WHERE email = :email
                ");
                $stmt->execute([':email' => $email]);
                $usuario = $stmt->fetch();

                // ============================================================
                // Validaciones de seguridad
                // ============================================================
                if (!$usuario) {
                    // Email no registrado
                    $error = 'Email o contraseña incorrectos.';
                } elseif ((int) $usuario['activo'] !== 1) {
                    // Usuario desactivado
                    $error = 'Tu cuenta está desactivada. Contacta al administrador.';
                } elseif (!password_verify($password, $usuario['password'])) {
                    // Contraseña incorrecta
                    $error = 'Email o contraseña incorrectos.';
                } else {
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
                    $stmt = $pdo->prepare("
                        UPDATE usuarios
                        SET ultimo_acceso = NOW()
                        WHERE id = :id
                    ");
                    $stmt->execute([':id' => $usuario['id']]);

                    // Limpiar contador de intentos
                    unset($_SESSION['login_intentos']);
                    unset($_SESSION['login_bloqueo_hasta']);

                    // Migrar carrito de sesión a BD si existen items
                    if (!empty($_SESSION['carrito'])) {
                        foreach ($_SESSION['carrito'] as $prod_id => $item) {
                            // Verificar si ya existe en BD
                            $stmt = $pdo->prepare("
                                SELECT id FROM items_carrito
                                WHERE usuario_id = :uid AND producto_id = :pid
                            ");
                            $stmt->execute([
                                ':uid' => $usuario['id'],
                                ':pid' => $prod_id,
                            ]);
                            $existente = $stmt->fetch();

                            if ($existente) {
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

                    // ============================================================
                    // Redirigir según procedencia
                    // ============================================================
                    // [PEDAGÓGICO] Si el usuario llegó aquí desde otra página
                    // (ej: checkout), lo redirigimos de vuelta.
                    $destino = $_POST['redirect'] ?? 'index.php';

                    // Validar que el destino no sea una URL externa (open redirect)
                    if (strpos($destino, 'http') === 0) {
                        $destino = 'index.php';
                    }

                    redireccionar($destino);
                }
            }
        }

        // ============================================================
        // Si llegamos aquí, hubo error: incrementar contador
        // ============================================================
        if (!empty($error)) {
            $_SESSION['login_intentos']++;
            if ($_SESSION['login_intentos'] >= 5) {
                // Bloquear por 15 minutos
                $_SESSION['login_bloqueo_hasta'] = time() + (15 * 60);
                $error = '⏱️ Demasiados intentos. Espera 15 minutos.';
            }
        }
    }
}

// ============================================================
// Determinar página de redirección post-login
// ============================================================
$redirect = $_GET['redirect'] ?? 'index.php';
if (strpos($redirect, 'http') === 0) {
    $redirect = 'index.php';
}

require_once __DIR__ . '/includes/header.php';
?>

<!-- ============================================================
     Formulario de Inicio de Sesión
     ============================================================ -->
<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <h2 class="text-center mb-4">🔑 Iniciar Sesión</h2>

                <!-- Mensaje de error -->
                <?php if ($error): ?>
                    <div class="alert alert-danger" role="alert">
                        <?= escapar($error) ?>
                    </div>
                <?php endif; ?>

                <!-- Mensaje informativo si ya está logueado -->
                <?php if (esta_logueado()): ?>
                    <div class="alert alert-info" role="alert">
                        Ya has iniciado sesión como <strong><?= escapar($_SESSION['usuario_nombre']) ?></strong>.
                        <a href="index.php" class="alert-link">Ir al inicio</a>
                    </div>
                <?php else: ?>

                <form method="POST" action="login.php" novalidate>
                    <!-- Token CSRF -->
                    <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">
                    <!-- Redirección post-login -->
                    <input type="hidden" name="redirect" value="<?= escapar($redirect) ?>">

                    <!-- Campo Email -->
                    <div class="mb-3">
                        <label for="email" class="form-label">📧 Correo electrónico</label>
                        <input type="email"
                               id="email"
                               name="email"
                               class="form-control <?= (!empty($error) && !empty($email)) ? 'is-invalid' : '' ?>"
                               placeholder="ejemplo@correo.com"
                               value="<?= escapar($email) ?>"
                               required
                               autofocus>
                    </div>

                    <!-- Campo Contraseña -->
                    <div class="mb-3">
                        <label for="password" class="form-label">🔒 Contraseña</label>
                        <input type="password"
                               id="password"
                               name="password"
                               class="form-control"
                               placeholder="Ingresa tu contraseña"
                               required>
                    </div>

                    <!-- Botón de envío -->
                    <button type="submit" class="btn btn-primary w-100 py-2 mb-3">
                        🔓 Iniciar Sesión
                    </button>

                    <!-- Enlace a registro -->
                    <p class="text-center mb-0">
                        ¿No tienes cuenta?
                        <a href="registro.php" class="text-decoration-none">Regístrate aquí</a>
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
