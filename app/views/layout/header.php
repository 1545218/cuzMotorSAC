<?php
// Obtener usuario actual si no está definido
if (!isset($user) || !$user) {
    require_once ROOT_PATH . '/app/core/Auth.php';
    $auth = new Auth();
    $user = $auth->getUser();
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($title) ? $title . ' - ' : '' ?><?= APP_NAME ?></title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="<?= BASE_PATH ?>/public/css/main.css">
    <!-- Rutas de respaldo para entornos donde `public` está en el path -->
    <link rel="stylesheet" href="<?= BASE_PATH ?>/css/main.css?v=1">
    <link rel="stylesheet" href="/css/main.css?v=1">
    <meta name="robots" content="noindex, nofollow">
    <?php
    // Inyectar token CSRF para ser usado por JavaScript (AJAX)
    if (!isset($auth)) {
        require_once ROOT_PATH . '/app/core/Auth.php';
        $auth = new Auth();
    }
    $csrfToken = htmlspecialchars($auth->getCSRFToken());
    ?>
    <meta name="csrf-token" content="<?= $csrfToken ?>">

    <!-- Estilos migrados a public/css/main.css -->
</head>

<body>
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <nav class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <div class="logo">
                <i class="fas fa-tools"></i>
            </div>
            <h5 class="mb-0"><?= COMPANY_NAME ?></h5>
            <small class="text-muted">Sistema de Inventario</small>
        </div>

        <?php if (isset($user) && $user): ?>
            <div class="user-info">
                <div class="d-flex align-items-center">
                    <div class="avatar me-3">
                        <i class="fas fa-user-circle fa-2x"></i>
                    </div>
                    <div>
                        <div class="fw-bold"><?= htmlspecialchars($user['nombre']) ?></div>
                        <small class="text-muted"><?= ucfirst($user['rol'] ?? 'Usuario') ?></small>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php
        $rol = $_SESSION['rol'] ?? '';
        ?>

        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?= ($_GET['page'] ?? '') == 'dashboard' ? 'active' : '' ?>" href="?page=dashboard">
                    <i class="fas fa-home"></i>Inicio
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= ($_GET['page'] ?? '') == 'productos' ? 'active' : '' ?>" href="?page=productos">
                    <i class="fas fa-box"></i>Inventario
                </a>
            </li>
            <?php if ($rol === 'administrador' || $rol === 'vendedor'): ?>
                <li class="nav-item">
                    <a class="nav-link <?= ($_GET['page'] ?? '') == 'clientes' ? 'active' : '' ?>" href="?page=clientes">
                        <i class="fas fa-users"></i>Clientes
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= ($_GET['page'] ?? '') == 'cotizaciones' ? 'active' : '' ?>" href="?page=cotizaciones">
                        <i class="fas fa-file-invoice-dollar"></i>Cotizaciones
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= ($_GET['page'] ?? '') == 'ventas' ? 'active' : '' ?>" href="?page=ventas">
                        <i class="fas fa-cash-register"></i>Ventas
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= ($_GET['page'] ?? '') == 'marcas' ? 'active' : '' ?>" href="?page=marcas">
                        <i class="fas fa-industry"></i>Marcas
                    </a>
                </li>
            <?php endif; ?>
            <?php if ($rol === 'administrador'): ?>
                <li class="nav-item">
                    <a class="nav-link <?= ($_GET['page'] ?? '') == 'reportes' ? 'active' : '' ?>" href="?page=reportes">
                        <i class="fas fa-chart-bar"></i>Reportes
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= ($_GET['page'] ?? '') == 'config' ? 'active' : '' ?>" href="?page=config">
                        <i class="fas fa-cog"></i>Configuración
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= ($_GET['page'] ?? '') == 'usuarios' ? 'active' : '' ?>" href="?page=usuarios">
                        <i class="fas fa-users-cog"></i>Usuarios
                    </a>
                </li>
            <?php endif; ?>
        </ul>

        <div class="mt-auto p-3">
            <div class="dropdown dropup">
                <a class="nav-link dropdown-toggle" href="#" role="button"
                    data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fas fa-user"></i>Mi Cuenta
                </a>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="?page=perfil">
                            <i class="fas fa-user-edit me-2"></i>Mi Perfil
                        </a></li>
                    <li>
                        <hr class="dropdown-divider">
                    </li>
                    <li><a class="dropdown-item text-danger" href="?page=logout">
                            <i class="fas fa-sign-out-alt me-2"></i>Cerrar Sesión
                        </a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="main-content" id="mainContent">
        <nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom">
            <div class="container-fluid">
                <button class="btn btn-outline-secondary d-lg-none me-3" id="sidebarToggle">
                    <i class="fas fa-bars"></i>
                </button>

                <span class="navbar-text me-auto">
                    <a href="?page=dashboard" class="text-decoration-none">Inicio</a>
                </span>

                <?php if (isset($user) && $user): ?>
                    <div class="dropdown">
                        <button class="btn btn-outline-secondary dropdown-toggle" type="button"
                            data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-user me-2"></i>Administrador
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><span class="dropdown-item-text">
                                    <small class="text-muted">Conectado como <?= ucfirst($user['rol'] ?? 'Usuario') ?></small>
                                </span></li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li><a class="dropdown-item text-danger" href="?page=logout">
                                    <i class="fas fa-sign-out-alt me-2"></i>Cerrar Sesión
                                </a></li>
                        </ul>
                    </div>
                <?php endif; ?>
            </div>
        </nav>


        <div class="content-wrapper p-0">
            <?php
            if (isset($_SESSION['flash_messages']) && !empty($_SESSION['flash_messages'])):
                foreach ($_SESSION['flash_messages'] as $message):
            ?>
                    <div class="alert alert-<?= $message['type'] === 'error' ? 'danger' : $message['type'] ?> alert-dismissible fade show" role="alert">
                        <i class="fas fa-<?= $message['type'] === 'error' ? 'exclamation-triangle' : ($message['type'] === 'success' ? 'check-circle' : 'info-circle') ?> me-2"></i>
                        <?= htmlspecialchars($message['message']) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
            <?php
                endforeach;
                unset($_SESSION['flash_messages']);
            endif;
            ?>