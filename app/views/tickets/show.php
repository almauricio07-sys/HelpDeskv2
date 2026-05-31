<?php
/**
 * Vista: Detalle del Ticket
 *
 * RF_07 — Asignar técnico (Mesa/Coordinador)
 * RF_08 — Comentarios Técnicos / Notas internas (equipo interno)
 * RF_09 — Actualizar estatus (Soporte Técnico / Mesa de Ayuda)
 * RF_10 — Cerrar ticket (Mesa/Coordinador)
 *
 * Estatus: 1=Pendiente Asignación | 2=En Proceso | 3=Terminado
 *          4=Pendiente Validación  | 5=Cerrado
 */
require BASE_PATH . '/app/views/layouts/header.php';

$flashSuccess = $_SESSION['flash_success'] ?? null;
$flashError   = $_SESSION['flash_error']   ?? null;
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

$rolId          = (int) $_SESSION['rol_id'];
$idEstatus      = (int) ($ticket['id_estatus'] ?? 0);
$esCerrado      = $idEstatus === 5;
$enValidacion   = $idEstatus === 4;

// ── Helpers de badge ──────────────────────────────────────────────────────
function badgeEstShow(string $estatus): string {
    return match (strtolower(trim($estatus))) {
        'pendiente de asignación' => 'badge-pendiente',
        'en proceso'              => 'badge-proceso',
        'terminado'               => 'badge-media',
        'pendiente de validación' => 'badge-abierto',
        'cerrado'                 => 'badge-cerrado',
        default                   => 'badge-pendiente',
    };
}
function badgePrioShow(string $prioridad): string {
    return match (strtolower(trim($prioridad))) {
        'alta'  => 'badge-alta',
        'media' => 'badge-media',
        'baja'  => 'badge-baja',
        default => 'badge-pendiente',
    };
}
?>

<!-- Breadcrumb -->
<div class="hd-breadcrumb fade-in-up">
    <a href="<?= BASE_URL ?>/index.php?controller=Dashboard&action=index">
        <i class="bi bi-house-fill"></i>
    </a>
    <i class="bi bi-chevron-right" style="font-size:.7rem;"></i>
    <a href="<?= BASE_URL ?>/index.php?controller=Ticket&action=<?= $rolId === 2 ? 'misTickets' : 'index' ?>">
        <?= $rolId === 2 ? 'Mis Folios' : 'Tickets' ?>
    </a>
    <i class="bi bi-chevron-right" style="font-size:.7rem;"></i>
    <span class="folio-tag"><?= htmlspecialchars($ticket['folio']) ?></span>
</div>

<!-- Flash -->
<?php if ($flashSuccess): ?>
    <div class="alert alert-success alert-dismissible d-flex align-items-center gap-2 mb-4 fade-in-up">
        <i class="bi bi-check-circle-fill"></i>
        <span><?= $flashSuccess ?></span>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>
<?php if ($flashError): ?>
    <div class="alert alert-danger alert-dismissible d-flex align-items-center gap-2 mb-4 fade-in-up">
        <i class="bi bi-exclamation-circle-fill"></i>
        <span><?= htmlspecialchars($flashError) ?></span>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Encabezado del ticket -->
<div class="d-flex align-items-start justify-content-between mb-4 flex-wrap gap-2 fade-in-up">
    <div>
        <div class="d-flex align-items-center flex-wrap gap-2 mb-1">
            <span class="folio-tag" style="font-size:0.9rem;">
                <?= htmlspecialchars($ticket['folio']) ?>
            </span>
            <span class="hd-badge <?= badgeEstShow($ticket['estatus']) ?>">
                <?= htmlspecialchars($ticket['estatus']) ?>
            </span>
            <span class="hd-badge <?= badgePrioShow($ticket['prioridad']) ?>">
                <i class="bi bi-flag-fill"></i> <?= ucfirst(strtolower($ticket['prioridad'])) ?>
            </span>
        </div>
        <h1 class="h4 mb-0">Detalle del Ticket</h1>
    </div>

    <!-- RF_10: Botones de cierre — Mesa/Coordinador -->
    <?php if (in_array($rolId, [1, 3]) && !$esCerrado && !$enValidacion): ?>
        <button type="button" class="btn btn-outline-success shadow-sm"
                data-bs-toggle="modal" data-bs-target="#modalCerrar">
            <i class="bi bi-check2-circle"></i> Finalizar Ticket
        </button>
    <?php elseif (in_array($rolId, [1, 3]) && $enValidacion): ?>
        <!-- RF_10 Flujo básico + Flujo Alt. 2a -->
        <div class="d-flex gap-2 flex-wrap">
            <button type="button" class="btn btn-success"
                    data-bs-toggle="modal" data-bs-target="#modalCerrar">
                <i class="bi bi-check2-circle"></i> Validar y Cerrar
            </button>
            <button type="button" class="btn btn-outline-danger"
                    data-bs-toggle="modal" data-bs-target="#modalRechazar">
                <i class="bi bi-arrow-counterclockwise"></i> Rechazar y Devolver
            </button>
        </div>
    <?php endif; ?>
</div>

<div class="row g-4">

    <!-- ══ Columna principal ══════════════════════════════════════════════ -->
    <div class="col-12 col-lg-8">

        <!-- Descripción del problema -->
        <div class="hd-card mb-4 fade-in-up delay-1">
            <div class="hd-card-header">
                <h2 class="hd-card-title">
                    <i class="bi bi-chat-left-text text-accent"></i> Descripción del Problema
                </h2>
            </div>
            <div class="hd-card-body">
                <p style="line-height:1.8; color:var(--text-primary); margin:0; white-space:pre-wrap;">
                    <?= nl2br(htmlspecialchars($ticket['descripcion'])) ?>
                </p>
            </div>
        </div>

        <!-- RF_08: Comentarios Técnicos (Notas Internas) -->
        <!-- Visibles exclusivamente para el equipo de soporte (roles 1, 2, 3).
             Un perfil de Solicitante jamás accede a esta sección (CU RF_08 Flujo Alt. 2a). -->
        <div class="hd-card fade-in-up delay-2">
            <div class="hd-card-header">
                <h2 class="hd-card-title">
                    <i class="bi bi-sticky text-accent"></i>
                    Comentarios Técnicos
                    <span class="hd-badge badge-abierto ms-1"
                          style="font-size:0.65rem;"><?= count($notas) ?></span>
                </h2>
            </div>
            <div class="hd-card-body">

                <!-- Historial de notas -->
                <?php if (empty($notas)): ?>
                    <div class="empty-state py-3">
                        <i class="bi bi-sticky d-block mb-2" style="font-size:1.8rem; color:var(--text-muted);"></i>
                        <p style="font-size:0.82rem; color:var(--text-muted); margin:0;">
                            Sin comentarios técnicos aún. Sé el primero en documentar el avance.
                        </p>
                    </div>
                <?php else: ?>
                    <div class="mb-3">
                        <?php foreach ($notas as $nota): ?>
                            <div class="nota-item">
                                <div class="nota-meta">
                                    <i class="bi bi-person-circle"></i>
                                    <strong style="color:var(--text-secondary);">
                                        <?= htmlspecialchars($nota['tecnico']) ?>
                                    </strong>
                                    <span class="hd-badge ms-1"
                                          style="font-size:0.62rem; background:rgba(255,255,255,0.06); color:var(--text-muted);">
                                        <?= htmlspecialchars($nota['nombre_rol'] ?? '') ?>
                                    </span>
                                    <span class="ms-auto" style="font-size:0.75rem; color:var(--text-muted);">
                                        <i class="bi bi-clock me-1"></i>
                                        <?= date('d/m/Y H:i', strtotime($nota['fecha_registro'])) ?>
                                    </span>
                                </div>
                                <p class="nota-texto mb-0">
                                    <?= nl2br(htmlspecialchars($nota['nota'])) ?>
                                </p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <!-- Formulario RF_08 — oculto si el ticket está cerrado -->
                <?php if (!$esCerrado): ?>
                    <hr style="border-color:rgba(255,255,255,0.07);">
                    <h3 style="font-size:0.82rem; font-weight:600; color:var(--text-secondary); margin-bottom:10px;">
                        <i class="bi bi-plus-circle text-accent me-1"></i> Agregar Comentario Técnico
                    </h3>
                    <form method="POST"
                          action="<?= BASE_URL ?>/index.php?controller=Ticket&action=agregarNota"
                          id="formNota">
                        <input type="hidden" name="id_ticket" value="<?= $ticket['id'] ?>">
                        <div class="mb-3">
                            <textarea class="form-control" name="nota" rows="4"
                                      placeholder="Describe las acciones realizadas, diagnóstico, avances o piezas utilizadas..."
                                      required id="notaTextarea"></textarea>
                            <div class="form-text" style="font-size:0.72rem; color:var(--text-muted);">
                                <i class="bi bi-shield-lock me-1"></i>
                                Visible solo para el equipo interno de soporte.
                            </div>
                        </div>
                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-primary btn-sm" id="btnNota">
                                <i class="bi bi-send"></i> Publicar Nota
                            </button>
                        </div>
                    </form>
                <?php else: ?>
                    <div class="alert alert-secondary mb-0" style="font-size:0.82rem;">
                        <i class="bi bi-lock-fill me-1"></i>
                        Ticket cerrado — historial de comentarios en solo lectura.
                    </div>
                <?php endif; ?>

            </div>
        </div>

    </div><!-- /columna principal -->

    <!-- ══ Sidebar ═══════════════════════════════════════════════════════ -->
    <div class="col-12 col-lg-4">

        <!-- Info del Solicitante -->
        <div class="hd-card mb-4 fade-in-up delay-1">
            <div class="hd-card-header">
                <h2 class="hd-card-title">
                    <i class="bi bi-person-circle text-accent"></i> Solicitante
                </h2>
            </div>
            <div class="hd-card-body">
                <div class="d-flex flex-column gap-2">
                    <div class="d-flex align-items-start gap-2">
                        <i class="bi bi-person mt-1" style="color:var(--text-muted); width:16px;"></i>
                        <div>
                            <div style="font-size:0.73rem; color:var(--text-muted);">Nombre</div>
                            <div style="font-weight:500;"><?= htmlspecialchars($ticket['solicitante']) ?></div>
                        </div>
                    </div>
                    <div class="d-flex align-items-start gap-2">
                        <i class="bi bi-id-card mt-1" style="color:var(--text-muted); width:16px;"></i>
                        <div>
                            <div style="font-size:0.73rem; color:var(--text-muted);">Clave</div>
                            <div><span class="folio-tag"><?= htmlspecialchars($ticket['clave']) ?></span></div>
                        </div>
                    </div>
                    <div class="d-flex align-items-start gap-2">
                        <i class="bi bi-envelope mt-1" style="color:var(--text-muted); width:16px;"></i>
                        <div>
                            <div style="font-size:0.73rem; color:var(--text-muted);">Correo</div>
                            <div style="font-size:0.82rem; word-break:break-all;">
                                <?= htmlspecialchars($ticket['correo_solicitante'] ?? '—') ?>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex align-items-start gap-2">
                        <i class="bi bi-building mt-1" style="color:var(--text-muted); width:16px;"></i>
                        <div>
                            <div style="font-size:0.73rem; color:var(--text-muted);">Departamento</div>
                            <div style="font-size:0.82rem;">
                                <?= htmlspecialchars($ticket['departamento'] ?? '—') ?>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex align-items-start gap-2">
                        <i class="bi bi-telephone mt-1" style="color:var(--text-muted); width:16px;"></i>
                        <div>
                            <div style="font-size:0.73rem; color:var(--text-muted);">Canal de contacto</div>
                            <div style="font-size:0.82rem;"><?= htmlspecialchars($ticket['canal']) ?></div>
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
                            <div style="font-size:0.73rem; color:var(--text-muted);">Creación</div>
                            <div style="font-size:0.82rem;">
                                <?= date('d/m/Y H:i', strtotime($ticket['fecha_creacion'])) ?>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex align-items-start gap-2">
                        <i class="bi bi-calendar-check mt-1" style="color:var(--text-muted); width:16px;"></i>
                        <div>
                            <div style="font-size:0.73rem; color:var(--text-muted);">Cierre</div>
                            <div style="font-size:0.82rem;">
                                <?= $ticket['fecha_cierre']
                                    ? date('d/m/Y H:i', strtotime($ticket['fecha_cierre']))
                                    : '<span style="color:var(--text-muted);">Pendiente</span>' ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- RF_07: Asignar técnico (Mesa/Coordinador) -->
        <?php if (in_array($rolId, [1, 3]) && !$esCerrado): ?>
            <div class="hd-card mb-4 fade-in-up delay-3">
                <div class="hd-card-header">
                    <h2 class="hd-card-title">
                        <i class="bi bi-person-gear text-accent"></i> Asignar Técnico
                    </h2>
                </div>
                <div class="hd-card-body">
                    <?php if ($ticket['tecnico']): ?>
                        <div class="alert alert-info mb-3" style="font-size:0.82rem; padding:8px 12px;">
                            <i class="bi bi-person-check-fill me-1"></i>
                            Asignado: <strong><?= htmlspecialchars($ticket['tecnico']) ?></strong>
                        </div>
                    <?php endif; ?>
                    <form method="POST"
                          action="<?= BASE_URL ?>/index.php?controller=Ticket&action=asignar">
                        <input type="hidden" name="id_ticket" value="<?= $ticket['id'] ?>">
                        <div class="mb-3">
                            <label class="form-label">Técnico Responsable</label>
                            <select class="form-select" name="id_tecnico" required>
                                <option value="">— Selecciona técnico —</option>
                                <?php foreach ($tecnicos as $tec): ?>
                                    <option value="<?= $tec['id'] ?>"
                                        <?= ($ticket['id_tecnico'] ?? '') == $tec['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($tec['nombre_completo']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm w-100">
                            <i class="bi bi-person-check"></i>
                            <?= $ticket['tecnico'] ? 'Reasignar Técnico' : 'Asignar Técnico' ?>
                        </button>
                    </form>
                </div>
            </div>
        <?php endif; ?>

        <!-- RF_09: Actualizar estatus — Soporte Técnico (Rol 2) -->
        <?php if ($rolId === 2 && !$esCerrado): ?>
            <div class="hd-card mb-4 fade-in-up delay-3">
                <div class="hd-card-header">
                    <h2 class="hd-card-title">
                        <i class="bi bi-arrow-repeat text-accent"></i> Actualizar Estatus
                    </h2>
                </div>
                <div class="hd-card-body">

                    <?php if ($enValidacion): ?>
                        <!-- Ticket ya enviado a validación -->
                        <div class="alert alert-warning mb-0" style="font-size:0.82rem;">
                            <i class="bi bi-hourglass-split me-2"></i>
                            Ya marcaste este folio como <strong>Terminado</strong>.
                            Está en espera de validación por Mesa de Ayuda para su cierre definitivo.
                        </div>

                    <?php elseif (empty($ticket['id_tecnico'])): ?>
                        <!-- RF_09 Flujo Alt. 4a: sin técnico asignado -->
                        <div class="alert alert-danger mb-0" style="font-size:0.82rem;">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>
                            No puedes cambiar el estatus.
                            <strong>Debe asignarse un especialista primero (RF_07).</strong>
                        </div>

                    <?php else: ?>
                        <!-- Formulario de cambio de estatus -->
                        <form method="POST"
                              action="<?= BASE_URL ?>/index.php?controller=Ticket&action=actualizarEstatus"
                              id="formEstatus">
                            <input type="hidden" name="id_ticket" value="<?= $ticket['id'] ?>">
                            <div class="mb-3">
                                <label class="form-label">Nuevo Estatus</label>
                                <select class="form-select" name="id_estatus" required id="selectEstatus">
                                    <option value="">— Selecciona —</option>
                                    <option value="2" <?= $idEstatus === 2 ? 'selected' : '' ?>>
                                        En Proceso
                                    </option>
                                    <option value="3">
                                        Terminado — enviar a validación
                                    </option>
                                </select>
                                <div class="form-text mt-2" style="font-size:0.73rem; color:var(--text-muted);">
                                    <i class="bi bi-info-circle"></i>
                                    Al marcar <strong>Terminado</strong>, el folio pasa a
                                    <em>Pendiente de Validación</em> y se notifica a Mesa de Ayuda.
                                    El técnico no realiza el cierre definitivo.
                                </div>
                            </div>
                            <button type="submit" class="btn btn-outline-primary btn-sm w-100"
                                    id="btnActualizarEstatus">
                                <i class="bi bi-send-check"></i> Actualizar Estado
                            </button>
                        </form>
                    <?php endif; ?>

                </div>
            </div>
        <?php endif; ?>

        <!-- RF_09: Actualizar estatus — Mesa de Ayuda (Rol 3, solo "En Proceso") -->
        <?php if ($rolId === 3 && !$esCerrado && !$enValidacion): ?>
            <div class="hd-card mb-4 fade-in-up delay-3">
                <div class="hd-card-header">
                    <h2 class="hd-card-title">
                        <i class="bi bi-arrow-repeat text-accent"></i> Actualizar Estatus
                    </h2>
                </div>
                <div class="hd-card-body">
                    <form method="POST"
                          action="<?= BASE_URL ?>/index.php?controller=Ticket&action=actualizarEstatus"
                          id="formEstatus">
                        <input type="hidden" name="id_ticket" value="<?= $ticket['id'] ?>">
                        <select class="form-select mb-3" name="id_estatus" required>
                            <option value="2" <?= $idEstatus === 2 ? 'selected' : '' ?>>En Proceso</option>
                        </select>
                        <button type="submit" class="btn btn-outline-primary btn-sm w-100">
                            <i class="bi bi-check-circle"></i> Marcar En Proceso
                        </button>
                    </form>
                </div>
            </div>
        <?php endif; ?>

        <!-- Técnico Asignado (visualización) -->
        <div class="hd-card fade-in-up delay-4">
            <div class="hd-card-header">
                <h2 class="hd-card-title">
                    <i class="bi bi-tools text-accent"></i> Técnico Asignado
                </h2>
            </div>
            <div class="hd-card-body">
                <?php if ($ticket['tecnico']): ?>
                    <div class="d-flex align-items-center gap-3">
                        <div class="user-avatar-sm" style="width:38px; height:38px; font-size:0.9rem;">
                            <?php
                            $words = array_slice(explode(' ', $ticket['tecnico']), 0, 2);
                            echo htmlspecialchars(implode('', array_map(fn($w) => strtoupper($w[0]), $words)));
                            ?>
                        </div>
                        <div>
                            <div style="font-weight:500;">
                                <?= htmlspecialchars($ticket['tecnico']) ?>
                            </div>
                            <span class="role-badge">Soporte Técnico</span>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="empty-state py-2">
                        <i class="bi bi-person-x d-block mb-1" style="font-size:1.5rem; color:var(--text-muted);"></i>
                        <p style="font-size:0.8rem; color:var(--text-muted); margin:0;">Sin técnico asignado</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </div><!-- /sidebar -->
</div><!-- /row -->

<!-- ══ Modal: Confirmar cierre / validación ══════════════════════════════ -->
<?php if (in_array($rolId, [1, 3]) && !$esCerrado): ?>
<div class="modal fade" id="modalCerrar" tabindex="-1" aria-labelledby="modalCerrarLabel">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalCerrarLabel">
                    <i class="bi bi-check2-circle me-2 text-accent"></i>
                    <?= $enValidacion ? 'Validar y Cerrar Ticket' : 'Confirmar Cierre' ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <?php if ($enValidacion): ?>
                    <div class="alert alert-info mb-3" style="font-size:0.82rem;">
                        <i class="bi bi-info-circle me-1"></i>
                        Este ticket está marcado como <strong>Pendiente de Validación</strong>.
                        Al cerrarlo, confirmas que el técnico resolvió el problema correctamente.
                    </div>
                <?php endif; ?>
                <p>
                    ¿Confirmas el cierre del ticket
                    <span class="folio-tag"><?= htmlspecialchars($ticket['folio']) ?></span>?
                </p>
                <p class="mb-0" style="font-size:0.82rem; color:var(--text-muted);">
                    Esta acción registrará la fecha y hora de cierre definitivo y agregará
                    una nota de validación automática al historial.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary btn-sm"
                        data-bs-dismiss="modal">
                    <i class="bi bi-x-circle"></i> Cancelar
                </button>
                <form method="POST"
                      action="<?= BASE_URL ?>/index.php?controller=Ticket&action=cerrar">
                    <input type="hidden" name="id_ticket" value="<?= $ticket['id'] ?>">
                    <button type="submit" class="btn btn-success btn-sm">
                        <i class="bi bi-check2-circle"></i>
                        <?= $enValidacion ? 'Validar y Cerrar' : 'Sí, Cerrar Ticket' ?>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if (in_array($rolId, [1, 3]) && $enValidacion): ?>
<!-- ══ Modal: RF_10 Flujo Alt. 2a — Rechazar validación ════════════════════ -->
<div class="modal fade" id="modalRechazar" tabindex="-1" aria-labelledby="modalRechazarLabel">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalRechazarLabel">
                    <i class="bi bi-arrow-counterclockwise me-2 text-danger"></i>
                    Rechazar y Devolver a En Proceso
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST"
                  action="<?= BASE_URL ?>/index.php?controller=Ticket&action=rechazarValidacion">
                <div class="modal-body">
                    <input type="hidden" name="id_ticket" value="<?= $ticket['id'] ?>">
                    <div class="alert alert-warning mb-3" style="font-size:0.82rem;">
                        <i class="bi bi-exclamation-triangle me-1"></i>
                        El ticket regresará al estatus <strong>En Proceso</strong>.
                        La razón quedará registrada como nota interna visible para el técnico.
                    </div>
                    <div class="mb-0">
                        <label class="form-label" for="razon_rechazo">
                            Razón del rechazo <span style="color:var(--danger);">*</span>
                        </label>
                        <textarea class="form-control" id="razon_rechazo" name="razon_rechazo"
                                  rows="3" required
                                  placeholder="Describe por qué se rechaza el cierre y qué debe corregir el técnico..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary btn-sm"
                            data-bs-dismiss="modal">
                        <i class="bi bi-x-circle"></i> Cancelar
                    </button>
                    <button type="submit" class="btn btn-danger btn-sm">
                        <i class="bi bi-arrow-counterclockwise"></i> Rechazar y Devolver
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
// ID del ticket inyectado desde PHP — no depende de querySelector ni del DOM
const HD_TICKET_ID = <?= (int) ($ticket['id'] ?? 0) ?>;

document.addEventListener('DOMContentLoaded', function () {

    // Envía POST con application/x-www-form-urlencoded y espera JSON de respuesta
    function postJSON(url, params) {
        return fetch(url, {
            method: 'POST',
            credentials: 'same-origin',
            body: params
        }).then(function (res) {
            if (!res.ok) throw new Error('HTTP ' + res.status);
            return res.json();
        });
    }

    // ─── 1. NOTAS INTERNAS (RF_08) ────────────────────────────────────
    const formNota = document.getElementById('formNota');
    if (formNota) {
        formNota.addEventListener('submit', function (e) {
            e.preventDefault();

            if (HD_TICKET_ID <= 0) {
                Swal.fire('Error', 'No se pudo identificar el ticket. Recarga la página.', 'error');
                return;
            }

            const notaEl = document.getElementById('notaTextarea');
            const nota   = notaEl ? notaEl.value.trim() : '';

            if (!nota) {
                Swal.fire('Aviso', 'El comentario no puede estar vacío.', 'warning');
                return;
            }

            Swal.fire({ title: 'Guardando nota...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

            const params = new URLSearchParams();
            params.append('id_ticket', HD_TICKET_ID);
            params.append('nota', nota);

            postJSON(formNota.action, params)
                .then(function (data) {
                    if (data.success) {
                        Swal.fire({ title: '¡Nota guardada!', text: data.message, icon: 'success', timer: 1500, showConfirmButton: false })
                            .then(() => window.location.reload());
                    } else {
                        Swal.fire('Error', data.message, 'error');
                    }
                })
                .catch(function (err) {
                    console.error('[formNota]', err);
                    Swal.fire('Error crítico', 'No se pudo conectar al servidor.', 'error');
                });
        });
    }

    // ─── 2. CAMBIO DE ESTATUS (RF_09) ────────────────────────────────
    const formEstatus = document.getElementById('formEstatus');
    if (formEstatus) {
        formEstatus.addEventListener('submit', function (e) {
            e.preventDefault();

            // selectEstatus existe solo en el form de rol 2; en rol 3 se lee por name
            const selectEstatus = document.getElementById('selectEstatus');
            const idEstatusEl   = selectEstatus || formEstatus.elements['id_estatus'];
            const idEstatusVal  = idEstatusEl ? idEstatusEl.value : '';

            const params = new URLSearchParams();
            params.append('id_ticket', HD_TICKET_ID);
            params.append('id_estatus', idEstatusVal);

            if (selectEstatus && selectEstatus.value === '3') {
                Swal.fire({
                    title: '¿Enviar a validación?',
                    text: 'El folio desaparecerá de tu bandeja y pasará a Mesa de Ayuda.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Sí, enviar a Mesa',
                    cancelButtonText: 'Cancelar'
                }).then(function (result) {
                    if (result.isConfirmed) procesarEstatus(formEstatus.action, params, true);
                });
            } else {
                procesarEstatus(formEstatus.action, params, false);
            }
        });
    }

    function procesarEstatus(url, params, esTerminado) {
        Swal.fire({ title: 'Actualizando estatus...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

        postJSON(url, params)
            .then(function (data) {
                if (data.success) {
                    Swal.fire({ title: '¡Actualizado!', text: data.message, icon: 'success', confirmButtonColor: '#28a745' })
                        .then(function () {
                            if (esTerminado) {
                                window.location.href = '<?= BASE_URL ?>/index.php?controller=Ticket&action=misTickets';
                            } else {
                                window.location.reload();
                            }
                        });
                } else {
                    Swal.fire('Aviso', data.message, 'warning');
                }
            })
            .catch(function (err) {
                console.error('[formEstatus]', err);
                Swal.fire('Error Crítico', 'Hubo un problema de comunicación con el servidor.', 'error');
            });
    }

});
</script>
<?php require BASE_PATH . '/app/views/layouts/footer.php'; ?>
