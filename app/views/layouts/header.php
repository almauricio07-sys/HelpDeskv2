<?php
/**
 * Layout: Header / Navbar principal
 * Menú dinámico según el rol del usuario en sesión.
 * 
 * Roles: 1=Coordinador | 2=Técnico | 3=Mesa de Ayuda
 */

// Protección: si no hay sesión, redirigir al login
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/index.php?controller=Auth&action=loginForm');
    exit;
}

$rolId        = $_SESSION['rol_id']        ?? 0;
$nombreUsuario = $_SESSION['nombre']        ?? 'Usuario';
$rolNombre    = $_SESSION['rol_nombre']    ?? '';

// Iniciales para el avatar
$initials = implode('', array_map(fn($w) => strtoupper($w[0]), array_slice(explode(' ', $nombreUsuario), 0, 2)));

// Determinar ruta activa
$currentController = $_GET['controller'] ?? 'Auth';
$currentAction     = $_GET['action']     ?? '';

function isActive(string $controller, string $action = ''): string {
    global $currentController, $currentAction;
    $matchCtrl = (strtolower($currentController) === strtolower($controller));
    $matchAct  = empty($action) || (strtolower($currentAction) === strtolower($action));
    return ($matchCtrl && $matchAct) ? 'active' : '';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Sistema de Mesa de Ayuda - Los Bélicos. Gestión integral de tickets de soporte técnico.">
    <meta name="robots" content="noindex, nofollow">
    <title><?= $pageTitle ?? 'Mesa de Ayuda' ?> | Los Bélicos</title>

    <!-- Bootstrap 5 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Roboto+Mono:wght@400;500&display=swap" rel="stylesheet">
    <!-- Estilos propios -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/style.css">
    <!-- Favicon inline -->
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🎫</text></svg>">
</head>
<body>

<!-- ═══ NAVBAR ═══════════════════════════════════════════════════════════════ -->
<nav class="navbar navbar-expand-lg hd-navbar" id="mainNavbar">
    <div class="container-fluid">

        <!-- Brand -->
        <a class="navbar-brand" href="<?= BASE_URL ?>/index.php?controller=Dashboard&action=index">
            <div class="brand-icon"><i class="bi bi-headset text-white"></i></div>
            <span>Mesa de Ayuda</span>
        </a>

        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navMain">
            <i class="bi bi-list text-white fs-5"></i>
        </button>

        <div class="collapse navbar-collapse" id="navMain">
            <ul class="navbar-nav me-auto ms-3 gap-1">

                <!-- ── MENÚ ROL 3: MESA DE AYUDA ─────────────────────────── -->
                <?php if ($rolId == 3): ?>
                    <li class="nav-item">
                        <a class="nav-link <?= isActive('Dashboard') ?>" href="<?= BASE_URL ?>/index.php?controller=Dashboard&action=index">
                            <i class="bi bi-grid-1x2"></i> Panel
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= isActive('Ticket', 'create') ?>" href="<?= BASE_URL ?>/index.php?controller=Ticket&action=create">
                            <i class="bi bi-plus-circle"></i> Nuevo Ticket
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= isActive('Ticket', 'index') ?>" href="<?= BASE_URL ?>/index.php?controller=Ticket&action=index">
                            <i class="bi bi-ticket-perforated"></i> Todos los Tickets
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= isActive('Ticket', 'porValidar') ?>" href="<?= BASE_URL ?>/index.php?controller=Ticket&action=porValidar">
                            <i class="bi bi-clipboard-check"></i> Por Validar
                        </a>
                    </li>

                <!-- ── MENÚ ROL 2: TÉCNICO ────────────────────────────────── -->
                <?php elseif ($rolId == 2): ?>
                    <li class="nav-item">
                        <a class="nav-link <?= isActive('Dashboard') ?>" href="<?= BASE_URL ?>/index.php?controller=Dashboard&action=index">
                            <i class="bi bi-grid-1x2"></i> Panel
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= isActive('Ticket', 'misTickets') ?>" href="<?= BASE_URL ?>/index.php?controller=Ticket&action=misTickets">
                            <i class="bi bi-person-lines-fill"></i> Mis Folios
                        </a>
                    </li>

                <!-- ── MENÚ ROL 1: COORDINADOR ───────────────────────────── -->
                <?php elseif ($rolId == 1): ?>
                    <li class="nav-item">
                        <a class="nav-link <?= isActive('Dashboard') ?>" href="<?= BASE_URL ?>/index.php?controller=Dashboard&action=index">
                            <i class="bi bi-bar-chart-line"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= isActive('Ticket', 'index') ?>" href="<?= BASE_URL ?>/index.php?controller=Ticket&action=index">
                            <i class="bi bi-ticket-perforated"></i> Tickets
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= isActive('Usuario') ?>" href="<?= BASE_URL ?>/index.php?controller=Usuario&action=index">
                            <i class="bi bi-people"></i> Usuarios
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= isActive('Usuario', 'create') ?>" href="<?= BASE_URL ?>/index.php?controller=Usuario&action=create">
                            <i class="bi bi-person-plus"></i> Nuevo Usuario
                        </a>
                    </li>
                <?php endif; ?>

            </ul>

            <!-- Lado derecho: info usuario + logout -->
            <div class="d-flex align-items-center gap-3 ms-auto">
                <div class="user-info-nav d-none d-md-flex">
                    <div class="user-avatar-sm"><?= htmlspecialchars($initials) ?></div>
                    <div>
                        <div style="font-size:0.8rem; color: var(--text-primary); font-weight:500; line-height:1.2;">
                            <?= htmlspecialchars($nombreUsuario) ?>
                        </div>
                        <span class="role-badge"><?= htmlspecialchars($rolNombre) ?></span>
                    </div>
                </div>
                <a href="<?= BASE_URL ?>/index.php?controller=Auth&action=logout"
                   class="btn btn-outline-secondary btn-sm"
                   title="Cerrar sesión">
                    <i class="bi bi-box-arrow-right"></i>
                    <span class="d-none d-lg-inline">Salir</span>
                </a>
            </div>

        </div>
    </div>
</nav>
<!-- ═══ / NAVBAR ═══════════════════════════════════════════════════════════════ -->

<!-- Contenedor principal de la página -->
<main class="hd-main">
    <div class="container-fluid px-3 px-md-4">
