<?php
/**
 * Vista: Crear Usuario
 * RF_11 — Solo Coordinador (Rol 1)
 */
require BASE_PATH . '/app/views/layouts/header.php';
?>

<div class="hd-breadcrumb fade-in-up">
    <a href="<?= BASE_URL ?>/index.php?controller=Dashboard&action=index"><i class="bi bi-house-fill"></i></a>
    <i class="bi bi-chevron-right" style="font-size:.7rem;"></i>
    <a href="<?= BASE_URL ?>/index.php?controller=Usuario&action=index">Usuarios</a>
    <i class="bi bi-chevron-right" style="font-size:.7rem;"></i>
    <span>Nuevo Usuario</span>
</div>

<div class="d-flex align-items-center mb-4 gap-3 fade-in-up">
    <div class="stat-icon purple flex-shrink-0" style="width:42px; height:42px;">
        <i class="bi bi-person-plus-fill"></i>
    </div>
    <div>
        <h1 class="h4 mb-0">Registrar Nuevo Usuario</h1>
        <p class="mb-0" style="font-size:0.82rem;">Crea un nuevo acceso al sistema de mesa de ayuda</p>
    </div>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger d-flex align-items-start gap-2 mb-4 fade-in-up">
        <i class="bi bi-exclamation-triangle-fill flex-shrink-0 mt-1"></i>
        <div>
            <strong>Corrige los siguientes errores:</strong>
            <ul class="mb-0 mt-1">
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
                <h2 class="hd-card-title"><i class="bi bi-person-gear text-accent"></i> Datos del Usuario</h2>
            </div>
            <div class="hd-card-body">
                <form method="POST" action="<?= BASE_URL ?>/index.php?controller=Usuario&action=store" novalidate id="formUsuario">

                    <div class="row g-3">
                        <div class="col-12 col-sm-6">
                            <label for="clave_acceso" class="form-label">
                                Clave de Acceso <span style="color:var(--danger);">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-at"></i></span>
                                <input type="text" class="form-control" id="clave_acceso" name="clave_acceso"
                                       placeholder="Ej: jmartinez" required autocomplete="off"
                                       value="<?= htmlspecialchars($_POST['clave_acceso'] ?? '') ?>">
                            </div>
                            <div class="form-text" style="font-size:0.73rem; color:var(--text-muted);">Sin espacios ni caracteres especiales</div>
                        </div>

                        <div class="col-12 col-sm-6">
                            <label for="id_rol" class="form-label">
                                Rol <span style="color:var(--danger);">*</span>
                            </label>
                            <select class="form-select" id="id_rol" name="id_rol" required>
                                <option value="">— Selecciona rol —</option>
                                <?php foreach ($roles as $rol): ?>
                                    <option value="<?= $rol['id'] ?>"
                                        <?= ($_POST['id_rol'] ?? '') == $rol['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($rol['nombre_rol']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-12">
                            <label for="nombre_completo" class="form-label">
                                Nombre Completo <span style="color:var(--danger);">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-person"></i></span>
                                <input type="text" class="form-control" id="nombre_completo" name="nombre_completo"
                                       placeholder="Nombre(s) Apellido(s)" required
                                       value="<?= htmlspecialchars($_POST['nombre_completo'] ?? '') ?>">
                            </div>
                        </div>

                        <div class="col-12">
                            <label for="correo" class="form-label">
                                Correo Institucional <span style="color:var(--danger);">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                <input type="email" class="form-control" id="correo" name="correo"
                                       placeholder="usuario@institucion.edu.mx" required
                                       value="<?= htmlspecialchars($_POST['correo'] ?? '') ?>">
                            </div>
                        </div>

                        <div class="col-12"><hr></div>

                        <div class="col-12 col-sm-6">
                            <label for="password" class="form-label">
                                Contraseña <span style="color:var(--danger);">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                <input type="password" class="form-control" id="password" name="password"
                                       placeholder="Mínimo 8 caracteres" required minlength="8"
                                       autocomplete="new-password">
                            </div>
                        </div>

                        <div class="col-12 col-sm-6">
                            <label for="password2" class="form-label">
                                Confirmar Contraseña <span style="color:var(--danger);">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                                <input type="password" class="form-control" id="password2" name="password2"
                                       placeholder="Repite la contraseña" required
                                       autocomplete="new-password">
                            </div>
                        </div>
                    </div><!-- /row -->

                    <!-- Barra de fuerza de contraseña -->
                    <div class="mt-3" id="passStrengthWrapper" style="display:none;">
                        <div style="font-size:0.75rem; color:var(--text-muted); margin-bottom:4px;">Fuerza de contraseña:</div>
                        <div style="height:4px; border-radius:2px; background:rgba(255,255,255,0.08); overflow:hidden;">
                            <div id="passStrengthBar" style="height:100%; width:0%; transition: all 0.3s;"></div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-4">
                        <a href="<?= BASE_URL ?>/index.php?controller=Usuario&action=index"
                           class="btn btn-outline-secondary">
                            <i class="bi bi-x-circle"></i> Cancelar
                        </a>
                        <button type="submit" class="btn btn-primary" id="btnGuardar">
                            <i class="bi bi-person-plus"></i> Crear Usuario
                        </button>
                    </div>

                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Indicador de fuerza de contraseña
const passInput   = document.getElementById('password');
const passWrapper = document.getElementById('passStrengthWrapper');
const passBar     = document.getElementById('passStrengthBar');

passInput.addEventListener('input', function () {
    const val = this.value;
    passWrapper.style.display = val ? 'block' : 'none';

    let strength = 0;
    if (val.length >= 8)  strength += 25;
    if (/[A-Z]/.test(val)) strength += 25;
    if (/[0-9]/.test(val)) strength += 25;
    if (/[^A-Za-z0-9]/.test(val)) strength += 25;

    passBar.style.width = strength + '%';
    passBar.style.backgroundColor = strength <= 25 ? '#ef4444' :
                                    strength <= 50 ? '#f59e0b' :
                                    strength <= 75 ? '#3b82f6' : '#10b981';
});

// Loading state
document.getElementById('formUsuario').addEventListener('submit', function () {
    const btn = document.getElementById('btnGuardar');
    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Creando...';
    btn.disabled = true;
});
</script>

<?php require BASE_PATH . '/app/views/layouts/footer.php'; ?>
