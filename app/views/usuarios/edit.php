<?php
/**
 * Vista: Editar Usuario
 * RF_12 — Solo Coordinador (Rol 1)
 */
require BASE_PATH . '/app/views/layouts/header.php';

$initials = implode('', array_map(
    fn($w) => strtoupper($w[0]),
    array_slice(explode(' ', $usuario['nombre_completo']), 0, 2)
));
?>

<!-- Breadcrumb -->
<div class="hd-breadcrumb fade-in-up">
    <a href="<?= BASE_URL ?>/index.php?controller=Dashboard&action=index">
        <i class="bi bi-house-fill"></i>
    </a>
    <i class="bi bi-chevron-right" style="font-size:.7rem;"></i>
    <a href="<?= BASE_URL ?>/index.php?controller=Usuario&action=index">Usuarios</a>
    <i class="bi bi-chevron-right" style="font-size:.7rem;"></i>
    <span>Editar Usuario</span>
</div>

<!-- Encabezado -->
<div class="d-flex align-items-center mb-4 gap-3 fade-in-up">
    <div class="user-avatar-sm flex-shrink-0" style="width:46px; height:46px; font-size:1.1rem;">
        <?= htmlspecialchars($initials) ?>
    </div>
    <div>
        <h1 class="h4 mb-0">
            Editar: <?= htmlspecialchars($usuario['nombre_completo']) ?>
        </h1>
        <p class="mb-0" style="font-size:0.82rem; color:var(--text-muted);">
            Clave: <span class="folio-tag"><?= htmlspecialchars($usuario['clave_acceso']) ?></span>
        </p>
    </div>
</div>

<!-- Bloque de errores -->
<?php if (!empty($errors)): ?>
    <div class="alert alert-danger d-flex align-items-start gap-2 mb-4 fade-in-up">
        <i class="bi bi-exclamation-triangle-fill flex-shrink-0 mt-1" style="font-size:1.1rem;"></i>
        <div>
            <strong>Corrige los siguientes errores antes de continuar:</strong>
            <ul class="mb-0 mt-2 ps-3">
                <?php foreach ($errors as $e): ?>
                    <li><?= htmlspecialchars($e) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
<?php endif; ?>

<div class="row justify-content-center">
    <div class="col-12 col-lg-7 fade-in-up delay-1">
        <div class="hd-card">
            <div class="hd-card-header">
                <h2 class="hd-card-title">
                    <i class="bi bi-pencil-square text-accent"></i> Modificar Datos del Perfil
                </h2>
            </div>
            <div class="hd-card-body">
                <form method="POST"
                      action="<?= BASE_URL ?>/index.php?controller=Usuario&action=update"
                      novalidate
                      id="formEditar">
                    <input type="hidden" name="id" value="<?= $usuario['id'] ?>">

                    <div class="row g-3">

                        <!-- Clave (solo lectura) -->
                        <div class="col-12 col-sm-6">
                            <label class="form-label">Clave de Acceso</label>
                            <input type="text" class="form-control"
                                   value="<?= htmlspecialchars($usuario['clave_acceso']) ?>"
                                   disabled>
                            <div class="form-text" style="font-size:0.73rem; color:var(--text-muted);">
                                La clave no puede modificarse
                            </div>
                        </div>

                        <!-- Rol -->
                        <div class="col-12 col-sm-6">
                            <label for="id_rol" class="form-label">
                                Rol Asignado <span style="color:var(--danger);">*</span>
                            </label>
                            <select class="form-select" id="id_rol" name="id_rol" required>
                                <?php foreach ($roles as $rol): ?>
                                    <option value="<?= $rol['id'] ?>"
                                        <?= $usuario['id_rol'] == $rol['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($rol['nombre_rol']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text" style="font-size:0.73rem; color:var(--text-muted);">
                                Cambiar el rol actualiza los permisos de inmediato
                            </div>
                        </div>

                        <!-- Nombre Completo -->
                        <div class="col-12">
                            <label for="nombre_completo" class="form-label">
                                Nombre Completo <span style="color:var(--danger);">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-person"></i></span>
                                <input type="text" class="form-control" id="nombre_completo"
                                       name="nombre_completo" required
                                       value="<?= htmlspecialchars($usuario['nombre_completo']) ?>">
                            </div>
                        </div>

                        <!-- Correo Institucional -->
                        <div class="col-12">
                            <label for="correo" class="form-label">
                                Correo Institucional <span style="color:var(--danger);">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                <input type="email" class="form-control" id="correo"
                                       name="correo" required
                                       value="<?= htmlspecialchars($usuario['correo_institucional']) ?>">
                            </div>
                        </div>

                        <!-- Estado -->
                        <div class="col-12 col-sm-6">
                            <label for="estado" class="form-label">Estado del Perfil</label>
                            <select class="form-select" id="estado" name="estado">
                                <?php $estadoActual = strtolower($usuario['estado'] ?? ''); ?>
                                <option value="Activo"
                                    <?= $estadoActual === 'activo'   ? 'selected' : '' ?>>
                                    Activo
                                </option>
                                <option value="Inactivo"
                                    <?= $estadoActual === 'inactivo' ? 'selected' : '' ?>>
                                    Inactivo
                                </option>
                            </select>
                        </div>

                        <div class="col-12">
                            <hr style="border-color: rgba(255,255,255,0.08);">
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <i class="bi bi-lock text-accent"></i>
                                <span style="font-size:0.85rem; font-weight:600; color:var(--text-secondary);">
                                    Cambiar Contraseña
                                    <span style="font-weight:400; color:var(--text-muted);">(opcional)</span>
                                </span>
                            </div>
                            <p style="font-size:0.75rem; color:var(--text-muted); margin-bottom:0;">
                                Deja ambos campos vacíos para conservar la contraseña actual.
                            </p>
                        </div>

                        <!-- Nueva Contraseña -->
                        <div class="col-12 col-sm-6">
                            <label for="nueva_password" class="form-label">Nueva Contraseña</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                <input type="password" class="form-control" id="nueva_password"
                                       name="nueva_password" placeholder="Mínimo 8 caracteres"
                                       minlength="8" autocomplete="new-password">
                                <button type="button" class="btn btn-outline-secondary"
                                        onclick="togglePass('nueva_password', this)">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Confirmar Nueva Contraseña -->
                        <div class="col-12 col-sm-6">
                            <label for="nueva_password2" class="form-label">
                                Confirmar Nueva Contraseña
                            </label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                                <input type="password" class="form-control" id="nueva_password2"
                                       name="nueva_password2" placeholder="Repite la nueva contraseña"
                                       autocomplete="new-password">
                                <button type="button" class="btn btn-outline-secondary"
                                        onclick="togglePass('nueva_password2', this)">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Barra de fuerza (nueva contraseña) -->
                        <div class="col-12" id="passStrengthWrapper" style="display:none;">
                            <div style="height:5px; border-radius:3px; background:rgba(255,255,255,0.08); overflow:hidden;">
                                <div id="passStrengthBar"
                                     style="height:100%; width:0%; transition: all 0.35s ease;"></div>
                            </div>
                            <div id="passStrengthLabel"
                                 style="font-size:0.72rem; color:var(--text-muted); margin-top:3px;"></div>
                        </div>

                    </div><!-- /row -->

                    <!-- Acciones -->
                    <div class="d-flex justify-content-end gap-2 mt-4">
                        <a href="<?= BASE_URL ?>/index.php?controller=Usuario&action=index"
                           class="btn btn-outline-secondary">
                            <i class="bi bi-x-circle"></i> Cancelar
                        </a>
                        <button type="submit" class="btn btn-primary" id="btnActualizar">
                            <i class="bi bi-floppy"></i> Guardar Cambios
                        </button>
                    </div>

                </form>
            </div>
        </div>
    </div>
</div>

<script>
// ── Toggle visibilidad ─────────────────────────────────────────────────────
function togglePass(fieldId, btn) {
    const input = document.getElementById(fieldId);
    const icon  = btn.querySelector('i');
    if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'bi bi-eye-slash';
    } else {
        input.type = 'password';
        icon.className = 'bi bi-eye';
    }
}

// ── Barra de fuerza ────────────────────────────────────────────────────────
const newPass    = document.getElementById('nueva_password');
const newPass2   = document.getElementById('nueva_password2');
const wrapper    = document.getElementById('passStrengthWrapper');
const bar        = document.getElementById('passStrengthBar');
const label      = document.getElementById('passStrengthLabel');
const levels     = [
    { color: '#ef4444', label: 'Muy débil' },
    { color: '#f59e0b', label: 'Débil'     },
    { color: '#3b82f6', label: 'Aceptable' },
    { color: '#10b981', label: 'Fuerte'    },
];

newPass.addEventListener('input', function () {
    const val = this.value;
    wrapper.style.display = val ? 'block' : 'none';
    if (!val) return;

    let strength = 0;
    if (val.length >= 8)          strength++;
    if (/[A-Z]/.test(val))        strength++;
    if (/[0-9]/.test(val))        strength++;
    if (/[^A-Za-z0-9]/.test(val)) strength++;

    const idx = Math.max(0, strength - 1);
    bar.style.width           = (strength * 25) + '%';
    bar.style.backgroundColor = levels[idx].color;
    label.textContent         = levels[idx].label;
    label.style.color         = levels[idx].color;

    // Re-evaluar coincidencia si ya hay algo en el campo de confirmación
    if (newPass2.value) newPass2.dispatchEvent(new Event('input'));
});

// ── Validación visual de coincidencia ─────────────────────────────────────
newPass2.addEventListener('input', function () {
    if (!this.value) {
        this.style.borderColor = '';
        return;
    }
    const match = this.value === newPass.value;
    this.style.borderColor = match ? '#10b981' : '#ef4444';
});

// ── Loading state ──────────────────────────────────────────────────────────
document.getElementById('formEditar').addEventListener('submit', function () {
    const btn = document.getElementById('btnActualizar');
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Guardando...';
    btn.disabled  = true;
});
</script>

<?php require BASE_PATH . '/app/views/layouts/footer.php'; ?>
