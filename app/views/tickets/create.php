<?php
/**
 * Vista: Crear Ticket
 * RF_01 / RF_02 — Formulario de captura (Teléfono/WhatsApp)
 * RF_03 — El folio se genera automáticamente en el backend
 * Rol: Mesa de Ayuda (3)
 */
require BASE_PATH . '/app/views/layouts/header.php';
?>

<div class="hd-breadcrumb fade-in-up">
    <a href="<?= BASE_URL ?>/index.php?controller=Dashboard&action=index"><i class="bi bi-house-fill"></i></a>
    <i class="bi bi-chevron-right" style="font-size:.7rem;"></i>
    <a href="<?= BASE_URL ?>/index.php?controller=Ticket&action=index">Tickets</a>
    <i class="bi bi-chevron-right" style="font-size:.7rem;"></i>
    <span>Nuevo Ticket</span>
</div>

<div class="d-flex align-items-center mb-4 gap-3 fade-in-up">
    <div class="stat-icon blue flex-shrink-0" style="width:42px; height:42px;">
        <i class="bi bi-ticket-perforated-fill"></i>
    </div>
    <div>
        <h1 class="h4 mb-0">Registrar Nuevo Ticket</h1>
        <p class="mb-0" style="font-size:0.82rem; color: var(--text-secondary);">El folio se generará automáticamente al guardar</p>
    </div>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger d-flex align-items-start gap-2 mb-4 fade-in-up" role="alert">
        <i class="bi bi-exclamation-triangle-fill flex-shrink-0 mt-1"></i>
        <div>
            <strong>Por favor corrige los siguientes errores:</strong>
            <ul class="mb-0 mt-1">
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
<?php endif; ?>

<form method="POST" action="<?= BASE_URL ?>/index.php?controller=Ticket&action=store" id="formTicket" novalidate>

    <div class="row g-4">

        <div class="col-12 col-lg-5 fade-in-up delay-1">
            <div class="hd-card h-100">
                <div class="hd-card-header">
                    <h2 class="hd-card-title">
                        <i class="bi bi-person-circle text-accent"></i>
                        Datos del Solicitante
                    </h2>
                    <span class="hd-badge badge-abierto">
                        <i class="bi bi-magic"></i> Autocompletado activo
                    </span>
                </div>
                <div class="hd-card-body">

                    <div class="mb-3">
                        <label for="clave_reportante" class="form-label">
                            Clave del Reportante <span style="color:var(--danger);">*</span>
                        </label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-id-card"></i></span>
                            <input type="text" class="form-control" id="clave_reportante"
                                   name="clave_reportante" placeholder="Ej: EMP-0042"
                                   value="<?= htmlspecialchars($_POST['clave_reportante'] ?? '') ?>"
                                   required autocomplete="off">
                        </div>
                        <div class="form-text mt-2" style="font-size:0.75rem; color:var(--text-muted);">
                            <i class="bi bi-info-circle"></i> Escribe la clave y haz clic fuera del campo. Si el solicitante ya existe, el sistema llenará los datos por ti.
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="nombre_completo" class="form-label">
                            Nombre Completo <span style="color:var(--danger);">*</span>
                        </label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-person"></i></span>
                            <input type="text" class="form-control" id="nombre_completo"
                                   name="nombre_solicitante" placeholder="Nombre y apellidos"
                                   value="<?= htmlspecialchars($_POST['nombre_solicitante'] ?? '') ?>"
                                   required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="correo" class="form-label">
                            Correo Electrónico <span style="color:var(--danger);">*</span>
                        </label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                            <input type="email" class="form-control" id="correo"
                                   name="correo" placeholder="usuario@dominio.com"
                                   value="<?= htmlspecialchars($_POST['correo'] ?? '') ?>"
                                   required>
                        </div>
                    </div>

                    <div class="mb-0">
                        <label for="id_departamento" class="form-label">
                            Departamento <span style="color:var(--danger);">*</span>
                        </label>
                        <select class="form-select" id="id_departamento" name="id_departamento" required>
                            <option value="">— Selecciona departamento —</option>
                            <?php foreach ($departamentos as $dep): ?>
                                <option value="<?= $dep['id'] ?>"
                                    <?= ($_POST['id_departamento'] ?? '') == $dep['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($dep['nombre_departamento']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                </div>
            </div>
        </div>

        <div class="col-12 col-lg-7 fade-in-up delay-2">
            <div class="hd-card h-100">
                <div class="hd-card-header">
                    <h2 class="hd-card-title">
                        <i class="bi bi-ticket-perforated text-accent"></i>
                        Detalles del Ticket
                    </h2>
                </div>
                <div class="hd-card-body">

                    <div class="row g-3 mb-3">
                        <div class="col-12 col-sm-6">
                            <label for="id_canal" class="form-label">
                                Canal de Contacto <span style="color:var(--danger);">*</span>
                            </label>
                            <select class="form-select" id="id_canal" name="id_canal" required>
                                <option value="">— Selecciona canal —</option>
                                <?php foreach ($canales as $canal): ?>
                                    <option value="<?= $canal['id'] ?>"
                                        <?= ($_POST['id_canal'] ?? '') == $canal['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($canal['nombre_canal']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-12 col-sm-6">
                            <label class="form-label">
                                Prioridad <span style="color:var(--danger);">*</span>
                            </label>
                            <div class="d-flex gap-2 mt-1">
                                <?php
                                $prioridades = [
                                    'alta'  => ['badge-alta',  'bi-arrow-up-circle-fill',   'Alta'],
                                    'media' => ['badge-media', 'bi-dash-circle-fill',        'Media'],
                                    'baja'  => ['badge-baja',  'bi-arrow-down-circle-fill',  'Baja'],
                                ];
                                $prioridadActual = $_POST['prioridad'] ?? 'media';
                                foreach ($prioridades as $val => [$badgeClass, $icon, $label]):
                                ?>
                                    <label class="flex-fill" style="cursor:pointer;" title="Prioridad <?= $label ?>">
                                        <input type="radio" name="prioridad" value="<?= $val ?>"
                                               class="d-none prioridad-radio"
                                               <?= $prioridadActual === $val ? 'checked' : '' ?>>
                                        <div class="hd-badge <?= $badgeClass ?> w-100 justify-content-center py-2 prioridad-label
                                                    <?= $prioridadActual === $val ? 'selected-prio' : '' ?>"
                                             style="border-radius: var(--radius-sm); font-size:0.8rem; transition: var(--transition);">
                                            <i class="bi <?= $icon ?>"></i> <?= $label ?>
                                        </div>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <div class="mb-0">
                        <label for="descripcion" class="form-label">
                            Descripción del Problema <span style="color:var(--danger);">*</span>
                        </label>
                        <textarea class="form-control" id="descripcion" name="descripcion"
                                  rows="7" placeholder="Describe detalladamente el problema reportado por el usuario..."
                                  required><?= htmlspecialchars($_POST['descripcion'] ?? '') ?></textarea>
                        <div class="d-flex justify-content-between mt-1">
                            <div class="form-text" style="font-size:0.74rem; color:var(--text-muted);">
                                Incluye pasos para reproducir, equipo afectado, mensajes de error, etc.
                            </div>
                            <div id="charCount" style="font-size:0.74rem; color:var(--text-muted);">0 caracteres</div>
                        </div>
                    </div>

                </div>
            </div>
        </div>

    </div><div class="d-flex justify-content-end gap-2 mt-4 fade-in-up delay-3">
        <a href="<?= BASE_URL ?>/index.php?controller=Ticket&action=index"
           class="btn btn-outline-secondary">
            <i class="bi bi-x-circle"></i> Cancelar
        </a>
        <button type="submit" class="btn btn-primary" id="btnGuardar">
            <i class="bi bi-floppy"></i> Guardar Ticket
        </button>
    </div>

</form>

<?php 
// Inyectamos los scripts en el footer usando ob_start() para no romper la vista
ob_start(); 
?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // ─── Contador de caracteres para descripción ───
    const descTA = document.getElementById('descripcion');
    const charCount = document.getElementById('charCount');
    if (descTA) {
        descTA.addEventListener('input', () => {
            charCount.textContent = descTA.value.length + ' caracteres';
        });
        charCount.textContent = descTA.value.length + ' caracteres';
    }

    // ─── Selección visual de prioridad ───
    document.querySelectorAll('.prioridad-radio').forEach(radio => {
        radio.addEventListener('change', function () {
            document.querySelectorAll('.prioridad-label').forEach(l => l.classList.remove('selected-prio'));
            this.closest('label').querySelector('.prioridad-label').classList.add('selected-prio');
        });
    });

    // ─── Validación frontend con SweetAlert2 + efecto de carga ───
    document.getElementById('formTicket').addEventListener('submit', function(e) {
        const campos = [
            { id: 'clave_reportante', nombre: 'Clave del Reportante' },
            { id: 'nombre_completo',  nombre: 'Nombre Completo'      },
            { id: 'correo',           nombre: 'Correo Electrónico'   },
            { id: 'id_departamento',  nombre: 'Departamento'         },
            { id: 'id_canal',         nombre: 'Canal de Contacto'    },
            { id: 'descripcion',      nombre: 'Descripción del Problema' },
        ];

        const vacios = campos.filter(c => {
            const el = document.getElementById(c.id);
            return !el || el.value.trim() === '';
        });

        const prioSeleccionada = document.querySelector('input[name="prioridad"]:checked');

        if (vacios.length > 0 || !prioSeleccionada) {
            e.preventDefault();
            const items = vacios.map(c => `<li>${c.nombre}</li>`).join('');
            const prioItem = !prioSeleccionada ? '<li>Prioridad</li>' : '';
            Swal.fire({
                icon: 'warning',
                title: 'Campos incompletos',
                html: `<p style="margin-bottom:.5rem;">Por favor completa los siguientes campos:</p>
                       <ul class="text-start mb-0">${items}${prioItem}</ul>`,
                confirmButtonText: 'Entendido',
                confirmButtonColor: '#238636',
            });
            return;
        }

        const btn = document.getElementById('btnGuardar');
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Guardando...';
        btn.disabled = true;
    });

    // ─── Magia AJAX: Autocompletado del Solicitante ───
    const inputClave = document.getElementById('clave_reportante');
    const inputNombre = document.getElementById('nombre_completo');
    const inputCorreo = document.getElementById('correo');
    const selectDepto = document.getElementById('id_departamento');

    if (inputClave) {
        inputClave.addEventListener('blur', function() {
            const clave = this.value.trim();
            if (clave === '') {
                inputClave.classList.remove('is-valid');
                return;
            }

            // URL del endpoint JSON que añadimos en TicketController
            const url = `<?= BASE_URL ?>/index.php?controller=Ticket&action=buscarSolicitanteJson&clave=${encodeURIComponent(clave)}`;

            // Indicador visual de que estamos buscando
            inputClave.style.opacity = '0.7';

            fetch(url)
                .then(response => response.json())
                .then(data => {
                    inputClave.style.opacity = '1'; // Restaurar opacidad
                    if (data.existe) {
                        // ¡Bingo! Llenar datos
                        inputNombre.value = data.nombre_completo;
                        inputCorreo.value = data.correo;
                        selectDepto.value = data.id_departamento;

                        // Efecto de Bootstrap
                        inputClave.classList.remove('is-invalid');
                        inputClave.classList.add('is-valid');
                    } else {
                        // Usuario nuevo: limpiar campos y quitar borde verde
                        inputNombre.value = '';
                        inputCorreo.value = '';
                        selectDepto.value = '';
                        inputClave.classList.remove('is-valid');
                    }
                })
                .catch(error => {
                    console.error('Error al consultar el reportante:', error);
                    inputClave.style.opacity = '1';
                });
        });
    }
});
</script>
<style>
.selected-prio {
    box-shadow: 0 0 0 2px var(--bg-page), 0 0 0 4px rgba(255,255,255,0.2) !important;
    transform: translateY(-2px);
}
</style>
<?php 
$extraJs = ob_get_clean(); 
require BASE_PATH . '/app/views/layouts/footer.php'; 
?>