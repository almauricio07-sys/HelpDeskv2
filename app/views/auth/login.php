<?php
/**
 * Vista: Login
 * Página de inicio de sesión — Dark Mode
 */
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Inicio de sesión — Sistema de Mesa de Ayuda Los Bélicos">
    <title>Acceso al Sistema | Mesa de Ayuda</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/style.css">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🎫</text></svg>">
</head>
<body>

<div class="login-page">
    <div class="login-card fade-in-up">

        <!-- Logo -->
        <div class="login-logo">
            <i class="bi bi-headset"></i>
        </div>

        <h1 class="login-title">Mesa de Ayuda</h1>
        <p class="login-subtitle">Los Bélicos &mdash; Acceso al Sistema</p>

        <!-- Alerta de error -->
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger d-flex align-items-center gap-2 mb-3" role="alert">
                <i class="bi bi-exclamation-circle-fill"></i>
                <span><?= htmlspecialchars($error) ?></span>
            </div>
        <?php endif; ?>

        <!-- Formulario -->
        <form action="<?= BASE_URL ?>/index.php?controller=Auth&action=login" method="POST" novalidate id="loginForm">

            <div class="mb-3">
                <label for="clave_acceso" class="form-label">
                    <i class="bi bi-person me-1"></i>Clave de Acceso
                </label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-person-fill"></i></span>
                    <input
                        type="text"
                        class="form-control"
                        id="clave_acceso"
                        name="clave_acceso"
                        placeholder="Tu clave de usuario"
                        autocomplete="username"
                        required
                    >
                </div>
            </div>

            <div class="mb-4">
                <label for="password" class="form-label">
                    <i class="bi bi-lock me-1"></i>Contraseña
                </label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                    <input
                        type="password"
                        class="form-control"
                        id="password"
                        name="password"
                        placeholder="Tu contraseña"
                        autocomplete="current-password"
                        required
                    >
                    <button class="input-group-text" type="button" id="togglePass" title="Mostrar/ocultar contraseña" style="cursor:pointer;">
                        <i class="bi bi-eye" id="eyeIcon"></i>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn btn-primary w-100" id="btnLogin" style="padding: 0.6rem!important;">
                <i class="bi bi-box-arrow-in-right"></i>
                <span>Ingresar al Sistema</span>
            </button>

        </form>

        <hr class="my-3">
        <p class="text-center mb-0" style="font-size:0.75rem; color: var(--text-muted);">
            <i class="bi bi-shield-lock me-1"></i>
            Acceso restringido al personal autorizado
        </p>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Toggle de visibilidad de contraseña
    document.getElementById('togglePass').addEventListener('click', function () {
        const inp  = document.getElementById('password');
        const icon = document.getElementById('eyeIcon');
        if (inp.type === 'password') {
            inp.type = 'text';
            icon.className = 'bi bi-eye-slash';
        } else {
            inp.type = 'password';
            icon.className = 'bi bi-eye';
        }
    });

    // Efecto de carga en submit
    document.getElementById('loginForm').addEventListener('submit', function () {
        const btn = document.getElementById('btnLogin');
        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Ingresando...';
        btn.disabled = true;
    });
</script>
</body>
</html>
