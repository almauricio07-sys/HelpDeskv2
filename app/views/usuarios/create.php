<?php
/**
 * Vista: Crear Usuario
 * RF_11 — Solo Coordinador (Rol 1)
 */
require BASE_PATH . '/app/views/layouts/header.php';
?>

<!-- Breadcrumb -->
<div class="hd-breadcrumb fade-in-up">
    <a href="<?= BASE_URL ?>/index.php?controller=Dashboard&action=index"><i class="bi bi-house-fill"></i></a>
    <i class="bi bi-chevron-right" style="font-size:.7rem;"></i>
    <a href="<?= BASE_URL ?>/index.php?controller=Usuario&action=index">Usuarios</a>
    <i class="bi bi-chevron-right" style="font-size:.7rem;"></i>
    <span>Nuevo Usuario</span>
</div>

<!-- Encabezado -->
<div class="d-flex align-items-center mb-4 gap-3 fade-in-up">
    <div class="stat-icon purple flex-shrink-0" style="width:44px; height:44px;">
        <i class="bi bi-person-plus-fill"></i>
    </div>
    <div>
        <h1 class="h4 mb-0">Registrar Nuevo Usuario</h1>
        <p class="mb-0" style="font-size:0.82rem; color:var(--text-muted);">
            Crea un nuevo acceso al sistema de mesa de ayuda
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
                    <i class="bi bi-person-gear text-accent"></i> Datos del Usuario
                </h2>
            </div>
            <div class="hd-card-body">
                <form method="POST"
                      action="<?= BASE_URL ?>/index.php?controller=Usuario&action=store"
                      novalidate
                      id="formUsuario">

                    <div class="row g-3">

                        <!-- Clave de Acceso -->
                        <div class="col-12 col-sm-6">
                            <label for="clave_acceso" class="form-label">
                                Clave de Acceso <span style="color:var(--danger);">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-at"></i></span>
                                <input type="text" class="form-control" id="clave_acceso"
                                       name="clave_acceso" placeholder="Ej: jmartinez"
                                       required autocomplete="off"
                                       value="<?= htmlspecialchars($_POST['clave_acceso'] ?? '') ?>">
                            </div>
                            <div class="form-text" style="font-size:0.73rem; color:var(--text-muted);">
                                Sin espacios ni caracteres especiales. Debe ser única.
                            </div>
                        </div>

                        <!-- Rol -->
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

                        <!-- Nombre Completo -->
                        <div class="col-12">
                            <label for="nombre_completo" class="form-label">
                                Nombre Completo <span style="color:var(--danger);">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-person"></i></span>
                                <input type="text" class="form-control" id="nombre_completo"
                                       name="nombre_completo" placeholder="Nombre(s) Apellido(s)"
                                       required
                                       value="<?= htmlspecialchars($_POST['nombre_completo'] ?? '') ?>">
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
                                       name="correo" placeholder="usuario@institucion.edu.mx"
                                       required
                                       value="<?= htmlspecialchars($_POST['correo'] ?? '') ?>">
                            </div>
                            <div class="form-text" style="font-size:0.73rem; color:var(--text-muted);">
                                <i class="bi bi-info-circle"></i>
                                El correo institucional debe ser único en el sistema.
                            </div>
                        </div>

                        <div class="col-12"><hr style="border-color: rgba(255,255,255,0.08);"></div>

                        <!-- Contraseña -->
                        <div class="col-12 col-sm-6">
                            <label for="password" class="form-label">
                                Contraseña <span style="color:var(--danger);">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                <input type="password" class="form-control" id="password"
                                       name="password" placeholder="Mínimo 8 caracteres"
                                       required minlength="8" autocomplete="new-password">
                                <button type="button" class="btn btn-outline-secondary"
                                        onclick="togglePass('password', this)" title="Mostrar contraseña">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Confirmar Contraseña -->
                        <div class="col-12 col-sm-6">
                            <label for="password2" class="form-label">
                                Confirmar Contraseña <span style="color:var(--danger);">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                                <input type="password" class="form-control" id="password2"
                                       name="password2" placeholder="Repite la contraseña"
                                       required autocomplete="new-password">
                                <button type="button" class="btn btn-outline-secondary"
                                        onclick="togglePass('password2', this)" title="Mostrar contraseña">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Barra de fuerza -->
                        <div class="col-12" id="passStrengthWrapper" style="display:none;">
                            <div style="font-size:0.75rem; color:var(--text-muted); margin-bottom:4px;">
                                Fuerza de la contraseña:
                            </div>
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
                        <button type="submit" class="btn btn-primary" id="btnGuardar">
                            <i class="bi bi-person-plus"></i> Crear Usuario
                        </button>
                    </div>

                </form>
            </div>
        </div>

        <!-- Nota informativa -->
        <div class="hd-card mt-3 fade-in-up delay-2"
             style="border-left: 3px solid var(--accent); background: rgba(47,129,247,0.05);">
            <div class="hd-card-body py-2 px-3 d-flex gap-2 align-items-start">
                <i class="bi bi-shield-lock text-accent mt-1 flex-shrink-0"></i>
                <div style="font-size:0.8rem; color:var(--text-muted); line-height:1.5;">
                    El nuevo perfil quedará disponible inmediatamente en el
                    <strong style="color:var(--text-secondary);">Catálogo de Usuarios</strong>.
                    Si se asigna el rol <em>Soporte Técnico</em> o <em>Mesa de Ayuda</em>,
                    el usuario podrá iniciar sesión y gestionar tickets desde su primer acceso.
                </div>
            </div>
        </div>

    </div>
</div>

<script>
// ── Toggle visibilidad de contraseña ──────────────────────────────────────
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

// ── Indicador de fuerza de contraseña ────────────────────────────────────
const passInput   = document.getElementById('password');
const passWrapper = document.getElementById('passStrengthWrapper');
const passBar     = document.getElementById('passStrengthBar');
const passLabel   = document.getElementById('passStrengthLabel');

const levels = [
    { color: '#ef4444', label: 'Muy débil'  },
    { color: '#f59e0b', label: 'Débil'      },
    { color: '#3b82f6', label: 'Aceptable'  },
    { color: '#10b981', label: 'Fuerte'     },
];

passInput.addEventListener('input', function () {
    const val = this.value;
    passWrapper.style.display = val ? 'block' : 'none';
    if (!val) return;

    let strength = 0;
    if (val.length >= 8)          strength++;
    if (/[A-Z]/.test(val))        strength++;
    if (/[0-9]/.test(val))        strength++;
    if (/[^A-Za-z0-9]/.test(val)) strength++;

    const idx   = Math.max(0, strength - 1);
    const width  = strength * 25;
    passBar.style.width           = width + '%';
    passBar.style.backgroundColor = levels[idx].color;
    passLabel.textContent         = levels[idx].label;
    passLabel.style.color         = levels[idx].color;
});

// ── Confirmación visual de coincidencia de contraseñas ───────────────────
document.getElementById('password2').addEventListener('input', function () {
    const match = this.value === passInput.value;
    this.style.borderColor = this.value ? (match ? '#10b981' : '#ef4444') : '';
});

// ── Loading state al enviar ────────────────────────────────────────────────
document.getElementById('formUsuario').addEventListener('submit', function () {
    const btn = document.getElementById('btnGuardar');
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Creando...';
    btn.disabled  = true;
});
</script>

<?php require BASE_PATH . '/app/views/layouts/footer.php'; ?>
