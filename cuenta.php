<?php
// ============================================================
// Página de Cuenta de Usuario
// Muestra datos básicos del usuario y sus pedidos
// ============================================================

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/funciones.php';

require_once __DIR__ . '/includes/header.php';

// Si no está logueado, sugerir login/registro
if (!esta_logueado()) {
    ?>
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card shadow-sm">
                <div class="card-body p-4 text-center">
                    <h3>🔐 Acceso requerido</h3>
                    <p class="text-muted">Debes iniciar sesión para ver tu cuenta y pedidos.</p>
                    <a href="login.php?redirect=cuenta.php" class="btn btn-primary me-2">Iniciar sesión</a>
                    <a href="registro.php" class="btn btn-outline-secondary">Crear cuenta</a>
                </div>
            </div>
        </div>
    </div>
    <?php
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

$pdo = getDB();
$usuario = usuario_actual();

// Obtener pedidos del usuario
$stmt = $pdo->prepare("SELECT * FROM pedidos WHERE usuario_id = :uid ORDER BY fecha_creacion DESC");
$stmt->execute([':uid' => $usuario['id']]);
$pedidos = $stmt->fetchAll();
?>

<div class="row justify-content-center">
    <div class="col-md-10 col-lg-8">
        <h2 class="mb-4">📋 Mi Cuenta</h2>

        <div class="card mb-4">
            <div class="card-body">
                <ul class="nav nav-tabs" id="cuentaTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="perfil-tab" data-bs-toggle="tab" data-bs-target="#perfil" type="button" role="tab" aria-controls="perfil" aria-selected="true">
                            👤 Perfil
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="pedidos-tab" data-bs-toggle="tab" data-bs-target="#pedidos" type="button" role="tab" aria-controls="pedidos" aria-selected="false">
                            🧾 Pedidos
                        </button>
                    </li>
                </ul>
                <div class="tab-content pt-4" id="cuentaTabsContent">
                    <div class="tab-pane fade show active" id="perfil" role="tabpanel" aria-labelledby="perfil-tab">
                        <div class="row">
                            <div class="col-12">
                                <div class="card shadow-sm mb-3">
                                    <div class="card-body d-flex justify-content-between align-items-center">
                                        <div>
                                            <div class="fw-bold"><?= escapar($usuario['nombre']) ?></div>
                                            <div class="text-muted"><?= escapar($usuario['email']) ?></div>
                                        </div>
                                        <div>
                                            <a href="logout.php" class="btn btn-outline-danger">Cerrar sesión</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="pedidos" role="tabpanel" aria-labelledby="pedidos-tab">
                        <?php if (empty($pedidos)): ?>
                            <div class="alert alert-info">No has realizado pedidos todavía.</div>
                        <?php else: ?>
                            <?php foreach ($pedidos as $p): ?>
                                <div class="card mb-3 shadow-sm">
                                    <div class="card-body d-flex justify-content-between align-items-center">
                                        <div>
                                            <div class="fw-semibold">Orden: <?= escapar($p['numero']) ?></div>
                                            <div class="text-muted small">Fecha: <?= date('d/m/Y H:i', strtotime($p['fecha_creacion'])) ?></div>
                                            <div class="text-muted small">Estado: <?= escapar(ucfirst($p['estado'])) ?></div>
                                        </div>
                                        <div class="text-end">
                                            <div class="fw-bold text-success"><?= formato_precio($p['total']) ?></div>
                                            <a href="exito.php?orden=<?= urlencode($p['numero']) ?>" class="btn btn-sm btn-outline-primary mt-2">Ver detalle</a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/includes/footer.php';
