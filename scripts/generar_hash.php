<?php
/**
 * Script de utilidad: Generador de Hash BCrypt
 * 
 * IMPORTANTE: Eliminar este archivo en producción.
 * 
 * Uso: Acceder desde el navegador en http://localhost/Mesa/scripts/generar_hash.php
 */

// Solo accesible en entorno local
if (!in_array($_SERVER['REMOTE_ADDR'], ['127.0.0.1', '::1'])) {
    http_response_code(403);
    die('Acceso denegado.');
}

$hash = '';
$password = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['password'])) {
    $password = $_POST['password'];
    $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Generar Hash | Utilidad</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <style>
        body { background: #0a0a0a; color: #f0f0f0; font-family: monospace; padding: 2rem; }
        .card { background: #141414; border: 1px solid rgba(255,255,255,0.1); border-radius: 10px; padding: 1.5rem; max-width: 600px; margin: auto; }
        input { background: #1e1e1e !important; border: 1px solid rgba(255,255,255,0.1) !important; color: #f0f0f0 !important; }
        .hash-box { background: #1e1e1e; padding: 1rem; border-radius: 6px; word-break: break-all; font-size: 0.85rem; color: #10b981; border: 1px solid rgba(16,185,129,0.3); }
    </style>
</head>
<body>
<div class="card">
    <h1 style="font-size:1.2rem; color:#0d6efd; margin-bottom:1rem;">🔑 Generador de Hash BCrypt</h1>
    <form method="POST">
        <div class="mb-3">
            <label class="form-label" style="color:#9ca3af;">Contraseña a hashear:</label>
            <input type="text" class="form-control" name="password" value="<?= htmlspecialchars($password) ?>" placeholder="Escribe la contraseña..." required>
        </div>
        <button type="submit" class="btn btn-primary btn-sm">Generar Hash</button>
    </form>
    <?php if ($hash): ?>
        <hr style="border-color: rgba(255,255,255,0.1); margin: 1.5rem 0;">
        <div style="font-size:0.8rem; color:#9ca3af; margin-bottom:0.5rem;">Hash generado (cost=12):</div>
        <div class="hash-box"><?= htmlspecialchars($hash) ?></div>
        <div style="font-size:0.75rem; color:#6b7280; margin-top:0.5rem;">
            ✅ Copia y pega este valor en la columna <code>password_hash</code> de tu tabla <code>usuarios</code>.
        </div>
    <?php endif; ?>
</div>
</body>
</html>
