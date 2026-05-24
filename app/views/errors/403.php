<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 — Acceso denegado | Mesa de Ayuda</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/style.css">
</head>
<body>
<div class="d-flex align-items-center justify-content-center min-vh-100 text-center p-3">
    <div class="fade-in-up">
        <div style="font-size:5rem; font-weight:800; color:#ef4444; line-height:1; margin-bottom:1rem;">403</div>
        <h1 class="h3 mb-2">Acceso Denegado</h1>
        <p style="color:var(--text-muted); max-width:360px; margin:0 auto 1.5rem;">
            No tienes permisos para acceder a este recurso.
        </p>
        <a href="<?= BASE_URL ?>/index.php?controller=Dashboard&action=index" class="btn btn-primary">
            <i class="bi bi-house"></i> Volver al inicio
        </a>
    </div>
</div>
</body>
</html>
