<?php
/**
 * Vista: Detalle del Ticket
 * RF_07 — Asignar técnico (Mesa/Coordinador)
 * RF_08 — Agregar notas internas (Técnico)
 * RF_09 — Actualizar estatus (Técnico)
 * RF_10 — Cerrar ticket (Mesa/Coordinador)
 */
require BASE_PATH . '/app/views/layouts/header.php';

$flashSuccess = $_SESSION['flash_success'] ?? null;
$flashError   = $_SESSION['flash_error']   ?? null;
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

$rolId    = $_SESSION['rol_id'];
$esCerrado = strtolower($ticket['estatus']) === 'cerrado';

function mapBadge(string $val, string $tipo): string {
    if ($tipo === 'estatus') {
        return match (strtolower($val)) {
            'abierto'    => 'badge-abierto',
            'en proceso' => 'badge-proceso',
            'cerrado'    => 'badge-cerrado',
            default      => 'badge-pendiente',
        };
    }
    return match (strtolower($val)) {
        'alta'  => 'badge-alta',
        'media' => 'badge-media',
        'baja'  => 'badge-baja',
        default => 'badge-pendiente',
    };
}
?>

<!-- Breadcrumb -->
<div class="hd-breadcrumb fade-in-up">
    <a href="<?= BASE_URL ?>/index.php?controller=Dashboard&action=index"><i class="bi bi-house-fill"></i></a>
    <i class="bi bi-chevron-right" style="font-size:.7rem;"></i>
    <a href="<?= BASE_URL ?>/index.php?controller=Ticket&action=<?= $rolId == 2 ? 'misTickets' : 'index' ?>">Tickets</a>
    <i class="bi bi-chevron-right" style="font-size:.7rem;"></i>
    <span class="folio-tag"><?= htmlspecialchars($ticket['folio']) ?></span>
</div>

<!-- Flash -->
<?php if ($flashSuccess): ?>
    <div class="alert alert-success alert-dismissible d-flex align-items-center gap-2 mb-4 fade-in-up">
        <i class="bi bi-check-circle-fill"></i><span><?= $flashSuccess ?></span>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>
<?php if ($flashError): ?>
    <div class="alert alert-danger alert-dismissible d-flex align-items-center gap-2 mb-4 fade-in-up">
        <i class="bi bi-exclamation-circle-fill"></i><span><?= htmlspecialchars($flashError) ?></span>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Encabezado -->
<div class="d-flex align-items-start justify-content-between mb-4 flex-wrap gap-2 fade-in-up">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <span class="folio-tag" style="font-size:0.9rem;"><?= htmlspecialchars($ticket['folio']) ?></span>
            <span class="hd-badge <?= mapBadge($ticket['estatus'], 'estatus') ?>">
                <?= htmlspecialchars($ticket['estatus']) ?>
            </span>
            <span class="hd-badge <?= mapBadge($ticket['prioridad'], 'prioridad') ?>">
                <i class="bi bi-flag-fill"></i> <?= ucfirst($ticket['prioridad']) ?>
            </span>
        </div>
        <h1 class="h4 mb-0">Detalle del Ticket</h1>
    </div>
    <!-- Cerrar ticket — Mesa/Coordinador -->
    <?php if (in_array($rolId, [1, 3]) && !$esCerrado): ?>
        <button type="button" class="btn btn-outline-success" data-bs-toggle="modal" data-bs-target="#modalCerrar">
            <i class="bi bi-check2-circle"></i> Cerrar Ticket
        </button>
    <?php endif; ?>
</div>

<div class="row g-4">

    <!-- ══ Columna principal ══════════════════════════════════════════════ -->
    <div class="col-12 col-xl-8">

        <!-- Descripción -->
        <div class="hd-card mb-4 fade-in-up delay-1">
            <div class="hd-card-header">
                <h2 class="hd-card-title"><i class="bi bi-chat-left-text text-accent"></i> Descripción del Problema</h2>
            </div>
            <div class="hd-card-body">
                <p style="line-height:1.8; color:var(--text-primary); margin:0; white-space:pre-wrap;"><?= nl2br(htmlspecialchars($ticket['descripcion'])) ?></p>
            </div>
        </div>

        <!-- Notas internas -->
        <div class="hd-card fade-in-up delay-2">
            <div class="hd-card-header">
                <h2 class="hd-card-title">
                    <i class="bi bi-sticky text-accent"></i>
                    Notas Internas
                    <span class="hd-badge badge-abierto ms-1"><?= count($notas) ?></span>
                </h2>
            </div>
            <div class="hd-card-body">

                <!-- Lista de notas -->
                <?php if (empty($notas)): ?>
                    <div class="empty-state py-3">
                        <i class="bi bi-sticky d-block" style="font-size:1.8rem;"></i>
                        <p style="font-size:0.82rem;">Sin notas internas aún.</p>
                    </div>
                <?php else: ?>
                    <div class="mb-4">
                        <?php foreach ($notas as $nota): ?>
                            <div class="nota-item">
                                <div class="nota-meta">
                                    <i class="bi bi-person-circle"></i>
                                    <strong style="color:var(--text-secondary);"><?= htmlspecialchars($nota['tecnico']) ?></strong>
                                    <span class="ms-auto">
                                        <i class="bi bi-clock me-1"></i>
                                        <?= date('d/m/Y H:i', strtotime($nota['fecha_registro'])) ?>
                                    </span>
                                </div>
                                <p class="nota-texto mb-0"><?= nl2br(htmlspecialchars($nota['nota'])) ?></p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <!-- Formulario de nueva nota (solo Técnico) -->
                <?php if ($rolId == 2 && !$esCerrado): ?>
                    <hr>
                    <h3 class="hd-card-title mb-3" style="font-size:0.85rem;">
                        <i class="bi bi-plus-circle text-accent"></i> Agregar Nota Interna
                    </h3>
                    <form method="POST" action="<?= BASE_URL ?>/index.php?controller=Ticket&action=agregarNota" id="formNota">
                        <input type="hidden" name="id_ticket" value="<?= $ticket['id'] ?>">
                        <div class="mb-3">
                            <textarea class="form-control" name="nota" rows="4"
                                      placeholder="Escribe una nota sobre las acciones realizadas, diagnóstico, avances..."
                                      required id="notaTextarea"></textarea>
                        </div>
                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-primary btn-sm" id="btnNota">
                                <i class="bi bi-send"></i> Publicar Nota
                            </button>
                        </div>
                    </form>
                <?php elseif ($esCerrado): ?>
                    <div class="alert alert-warning mb-0" style="font-size:0.82rem;">
                        <i class="bi bi-lock-fill me-1"></i> Este ticket está cerrado. No se pueden agregar más notas.
                    </div>
                <?php endif; ?>

            </div>
        </div>

    </div><!-- /columna principal -->

    <!-- ══ Sidebar de información ═════════════════════════════════════════ -->
    <div class="col-12 col-xl-4">

        <!-- Info del Solicitante -->
        <div class="hd-card mb-4 fade-in-up delay-1">
            <div class="hd-card-header">
                <h2 class="hd-card-title"><i class="bi bi-person-circle text-accent"></i> Solicitante</h2>
            </div>
            <div class="hd-card-body">
                <div class="d-flex flex-column gap-2">
                    <div class="d-flex align-items-start gap-2">
                        <i class="bi bi-person mt-1" style="color:var(--text-muted); width:16px;"></i>
                        <div>
                            <div style="font-size:0.75rem; color:var(--text-muted);">Nombre</div>
                            <div style="font-weight:500;"><?= htmlspecialchars($ticket['solicitante']) ?></div>
                        </div>
                    </div>
                    <div class="d-flex align-items-start gap-2">
                        <i class="bi bi-id-card mt-1" style="color:var(--text-muted); width:16px;"></i>
                        <div>
                            <div style="font-size:0.75rem; color:var(--text-muted);">Clave</div>
                            <div><span class="folio-tag"><?= htmlspecialchars($ticket['clave']) ?></span></div>
                        </div>
                    </div>
                    <div class="d-flex align-items-start gap-2">
                        <i class="bi bi-envelope mt-1" style="color:var(--text-muted); width:16px;"></i>
                        <div>
                            <div style="font-size:0.75rem; color:var(--text-muted);">Correo</div>
                            <div style="font-size:0.85rem; word-break:break-all;"><?= htmlspecialchars($ticket['correo_solicitante']) ?></div>
                        </div>
                    </div>
                    <div class="d-flex align-items-start gap-2">
                        <i class="bi bi-building mt-1" style="color:var(--text-muted); width:16px;"></i>
                        <div>
                            <div style="font-size:0.75rem; color:var(--text-muted);">Departamento</div>
                            <div style="font-size:0.85rem;"><?= htmlspecialchars($ticket['departamento'] ?? '—') ?></div>
                        </div>
                    </div>
                    <div class="d-flex align-items-start gap-2">
                        <i class="bi bi-telephone mt-1" style="color:var(--text-muted); width:16px;"></i>
                        <div>
                            <div style="font-size:0.75rem; color:var(--text-muted);">Canal de Contacto</div>
                            <div style="font-size:0.85rem;"><?= htmlspecialchars($ticket['canal']) ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Fechas -->
        <div class="hd-card mb-4 fade-in-up delay-2">
            <div class="hd-card-header">
                <h2 class="hd-card-title"><i class="bi bi-calendar3 text-accent"></i> Fechas</h2>
            </div>
            <div class="hd-card-body">
                <div class="d-flex flex-column gap-2">
                    <div class="d-flex align-items-start gap-2">
                        <i class="bi bi-calendar-plus mt-1" style="color:var(--text-muted); width:16px;"></i>
                        <div>
                            <div style="font-size:0.75rem; color:var(--text-muted);">Creación</div>
                            <div style="font-size:0.85rem;"><?= date('d/m/Y H:i', strtotime($ticket['fecha_creacion'])) ?></div>
                        </div>
                    </div>
                    <div class="d-flex align-items-start gap-2">
                        <i class="bi bi-calendar-check mt-1" style="color:var(--text-muted); width:16px;"></i>
                        <div>
                            <div style="font-size:0.75rem; color:var(--text-muted);">Cierre</div>
                            <div style="font-size:0.85rem;">
                                <?= $ticket['fecha_cierre']
                                    ? date('d/m/Y H:i', strtotime($ticket['fecha_cierre']))
                                    : '<span class="text-muted-hd">Pendiente</span>' ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Asignación de Técnico (Mesa/Coordinador) -->
        <?php if (in_array($rolId, [1, 3]) && !$esCerrado): ?>
            <div class="hd-card mb-4 fade-in-up delay-3">
                <div class="hd-card-header">
                    <h2 class="hd-card-title"><i class="bi bi-person-gear text-accent"></i> Asignar Técnico</h2>
                </div>
                <div class="hd-card-body">
                    <?php if ($ticket['tecnico']): ?>
                        <div class="alert alert-info mb-3" style="font-size:0.82rem;">
                            <i class="bi bi-person-check-fill me-1"></i>
                            Asignado: <strong><?= htmlspecialchars($ticket['tecnico']) ?></strong>
                        </div>
                    <?php endif; ?>
                    <form method="POST" action="<?= BASE_URL ?>/index.php?controller=Ticket&action=asignar">
                        <input type="hidden" name="id_ticket" value="<?= $ticket['id'] ?>">
                        <div class="mb-3">
                            <label class="form-label">Técnico Responsable</label>
                            <select class="form-select" name="id_tecnico" required>
                                <option value="">— Selecciona técnico —</option>
                                <?php foreach ($tecnicos as $tec): ?>
                                    <option value="<?= $tec['id'] ?>"
                                        <?= $ticket['id_tecnico'] == $tec['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($tec['nombre_completo']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm w-100">
                            <i class="bi bi-person-check"></i>
                            <?= $ticket['tecnico'] ? 'Reasignar' : 'Asignar' ?> Técnico
                        </button>
                    </form>
                </div>
            </div>
        <?php endif; ?>

        <!-- Actualizar Estatus (Técnico) -->
        <?php if ($rolId == 2 && !$esCerrado): ?>
            <div class="hd-card mb-4 fade-in-up delay-3">
                <div class="hd-card-header">
                    <h2 class="hd-card-title"><i class="bi bi-arrow-repeat text-accent"></i> Actualizar Estatus</h2>
                </div>
                <div class="hd-card-body">
                    <form method="POST" action="<?= BASE_URL ?>/index.php?controller=Ticket&action=actualizarEstatus">
                        <input type="hidden" name="id_ticket" value="<?= $ticket['id'] ?>">
                        <div class="mb-3">
                            <select class="form-select" name="id_estatus" required>
                                <option value="">— Selecciona estatus —</option>
                                <?php foreach ($estatus as $est): ?>
                                    <option value="<?= $est['id'] ?>"
                                        <?= $ticket['id_estatus'] == $est['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($est['nombre_estatus']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-outline-primary btn-sm w-100">
                            <i class="bi bi-check-circle"></i> Actualizar Estatus
                        </button>
                    </form>
                </div>
            </div>
        <?php endif; ?>

        <!-- Info del Técnico (visualización) -->
        <div class="hd-card fade-in-up delay-4">
            <div class="hd-card-header">
                <h2 class="hd-card-title"><i class="bi bi-tools text-accent"></i> Técnico Asignado</h2>
            </div>
            <div class="hd-card-body">
                <?php if ($ticket['tecnico']): ?>
                    <div class="d-flex align-items-center gap-3">
                        <div class="user-avatar-sm" style="width:38px; height:38px; font-size:0.9rem;">
                            <?= strtoupper(substr($ticket['tecnico'], 0, 1)) ?>
                        </div>
                        <div>
                            <div style="font-weight:500;"><?= htmlspecialchars($ticket['tecnico']) ?></div>
                            <span class="role-badge">Técnico</span>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="empty-state py-2">
                        <i class="bi bi-person-x d-block" style="font-size:1.5rem;"></i>
                        <p style="font-size:0.8rem;">Sin técnico asignado</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </div><!-- /sidebar -->
</div><!-- /row -->

<!-- ══ Modal: Confirmar cierre ═══════════════════════════════════════════════ -->
<?php if (in_array($rolId, [1, 3]) && !$esCerrado): ?>
<div class="modal fade" id="modalCerrar" tabindex="-1" aria-labelledby="modalCerrarLabel">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalCerrarLabel">
                    <i class="bi bi-check2-circle me-2 text-accent"></i>Confirmar Cierre
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>¿Estás seguro de que deseas cerrar el ticket <span class="folio-tag"><?= htmlspecialchars($ticket['folio']) ?></span>?</p>
                <p class="mb-0" style="font-size:0.82rem; color:var(--text-muted);">
                    Esta acción registrará la fecha y hora de cierre. El técnico ya no podrá modificar el estatus.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">
                    <i class="bi bi-x-circle"></i> Cancelar
                </button>
                <form method="POST" action="<?= BASE_URL ?>/index.php?controller=Ticket&action=cerrar">
                    <input type="hidden" name="id_ticket" value="<?= $ticket['id'] ?>">
                    <button type="submit" class="btn btn-outline-success btn-sm">
                        <i class="bi bi-check2-circle"></i> Sí, Cerrar Ticket
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
document.getElementById('formNota')?.addEventListener('submit', function () {
    const btn = document.getElementById('btnNota');
    if (btn) {
        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Publicando...';
        btn.disabled = true;
    }
});
</script>

<?php require BASE_PATH . '/app/views/layouts/footer.php'; ?>
