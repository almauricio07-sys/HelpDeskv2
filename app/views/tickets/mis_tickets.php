<?php
/**
 * Vista: Mis Folios Asignados
 * RF_06 — Soporte Técnico (Rol 2)
 *
 * Muestra exclusivamente los tickets donde id_tecnico = id del usuario en sesión.
 * Flujo alternativo 3a: empty state "No tienes tickets asignados en este momento".
 */
require BASE_PATH . '/app/views/layouts/header.php';

$flashSuccess = $_SESSION['flash_success'] ?? null;
$flashError   = $_SESSION['flash_error']   ?? null;
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

function badgeEstatusMT(string $estatus): string {
    return match (strtolower(trim($estatus))) {
        'pendiente de asignación' => 'badge-pendiente',
        'en proceso'              => 'badge-proceso',
        'terminado'               => 'badge-media',
        'pendiente de validación' => 'badge-abierto',
        'cerrado'                 => 'badge-cerrado',
        default                   => 'badge-pendiente',
    };
}

function badgePrioridadMT(string $prioridad): string {
    return match (strtolower(trim($prioridad))) {
        'alta'  => 'badge-alta',
        'media' => 'badge-media',
        'baja'  => 'badge-baja',
        default => 'badge-pendiente',
    };
}

// NOTA: Eliminamos la lógica manual de conteo aquí, 
// ahora usamos el arreglo $stats que envía el TicketController.

?>

<div class="hd-breadcrumb fade-in-up">
    <a href="<?= BASE_URL ?>/index.php?controller=Dashboard&action=index">
        <i class="bi bi-house-fill"></i>
    </a>
    <i class="bi bi-chevron-right" style="font-size:.7rem;"></i>
    <span>Mis Folios Asignados</span>
</div>

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

<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2 fade-in-up">
    <div>
        <h1 class="h4 mb-1">
            <i class="bi bi-person-lines-fill me-2 text-accent"></i>Mis Folios Asignados
        </h1>
        <p class="mb-0" style="font-size:0.82rem; color:var(--text-muted);">
            <span class="text-accent fw-semibold"><?= $stats['por_hacer'] ?? 0 ?></span> activo(s) &mdash;
            <span style="color:#238636; font-weight:600;"><?= $stats['cerrados'] ?? 0 ?></span> cerrado(s)
        </p>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-md-3 fade-in-up delay-1">
        <div class="stat-card">
            <div class="stat-icon blue"><i class="bi bi-ticket-perforated-fill"></i></div>
            <div>
                <div class="stat-value"><?= $stats['total_asignados'] ?? 0 ?></div>
                <div class="stat-label">Histórico Asignados</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3 fade-in-up delay-2">
        <div class="stat-card">
            <div class="stat-icon amber"><i class="bi bi-clock-history"></i></div>
            <div>
                <div class="stat-value"><?= $stats['por_hacer'] ?? 0 ?></div>
                <div class="stat-label">Por Hacer (En Proceso)</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3 fade-in-up delay-3">
        <div class="stat-card">
            <div class="stat-icon text-info"><i class="bi bi-send-check"></i></div>
            <div>
                <div class="stat-value"><?= $stats['en_validacion'] ?? 0 ?></div>
                <div class="stat-label">En Validación (Mesa)</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3 fade-in-up delay-4">
        <div class="stat-card">
            <div class="stat-icon green"><i class="bi bi-check-circle-fill"></i></div>
            <div>
                <div class="stat-value"><?= $stats['cerrados'] ?? 0 ?></div>
                <div class="stat-label">Cerrados</div>
            </div>
        </div>
    </div>
</div>

<div class="hd-card mb-4 fade-in-up delay-1">
    <div class="hd-card-header">
        <h2 class="hd-card-title">
            <i class="bi bi-funnel text-accent"></i> Filtrar por Estatus
        </h2>
        <?php if (!empty($filtros['id_estatus'])): ?>
            <a href="<?= BASE_URL ?>/index.php?controller=Ticket&action=misTickets"
               class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-x-circle"></i> Limpiar
            </a>
        <?php endif; ?>
    </div>
    <div class="hd-card-body">
        <form method="GET" action="<?= BASE_URL ?>/index.php">
            <input type="hidden" name="controller" value="Ticket">
            <input type="hidden" name="action"     value="misTickets">
            <div class="row g-2 align-items-end">
                <div class="col-12 col-md-4">
                    <select class="form-select" name="id_estatus">
                        <option value="">Todos los estatus activos</option>
                        <?php foreach ($estatus as $e): ?>
                            <option value="<?= $e['id'] ?>"
                                <?= ($filtros['id_estatus'] ?? '') == $e['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($e['nombre_estatus']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="bi bi-search"></i> Filtrar
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="hd-card fade-in-up delay-2">
    <div class="hd-card-body p-0">
        <?php if (empty($tickets)): ?>
            <div class="empty-state py-5 text-center">
                <i class="bi bi-inbox d-block mb-3" style="font-size:2.5rem; color:var(--text-muted);"></i>
                <?php if (!empty($filtros['id_estatus'])): ?>
                    <p class="mb-2">No hay tickets con el estatus seleccionado.</p>
                    <a href="<?= BASE_URL ?>/index.php?controller=Ticket&action=misTickets"
                       class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-x-circle"></i> Ver todos
                    </a>
                <?php else: ?>
                    <p class="mb-0">No tienes tickets asignados por hacer en este momento. ¡Buen trabajo!</p>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="hd-table-wrapper" style="border:none; border-radius:0;">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Folio</th>
                            <th>Solicitante</th>
                            <th>Descripción</th>
                            <th>Prioridad</th>
                            <th>Estatus</th>
                            <th>Fecha</th>
                            <th class="text-end">Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tickets as $t): ?>
                            <?php $isCerrado = (int) $t['id_estatus'] === 5; ?>
                            <tr <?= $isCerrado ? 'style="opacity:0.6;"' : '' ?>>

                                <td>
                                    <span class="folio-tag">
                                        <?= htmlspecialchars($t['folio']) ?>
                                    </span>
                                </td>

                                <td>
                                    <div style="font-weight:500;">
                                        <?= htmlspecialchars($t['solicitante']) ?>
                                    </div>
                                    <div style="font-size:0.74rem; color:var(--text-muted);">
                                        <?= htmlspecialchars($t['clave']) ?>
                                    </div>
                                </td>

                                <td style="font-size:0.8rem; max-width:220px; color:var(--text-secondary);">
                                    <?= htmlspecialchars(mb_strimwidth(strip_tags($t['descripcion']), 0, 55, '…')) ?>
                                </td>

                                <td>
                                    <span class="hd-badge <?= badgePrioridadMT($t['prioridad']) ?>">
                                        <?= ucfirst(strtolower($t['prioridad'])) ?>
                                    </span>
                                </td>

                                <td>
                                    <span class="hd-badge <?= badgeEstatusMT($t['estatus']) ?>">
                                        <?= htmlspecialchars($t['estatus']) ?>
                                    </span>
                                </td>

                                <td style="font-size:0.78rem; color:var(--text-muted); white-space:nowrap;">
                                    <?= date('d/m/Y', strtotime($t['fecha_creacion'])) ?>
                                    <div style="font-size:0.72rem;">
                                        <?= date('H:i', strtotime($t['fecha_creacion'])) ?> hrs
                                    </div>
                                </td>

                                <td class="text-end">
                                    <a href="<?= BASE_URL ?>/index.php?controller=Ticket&action=show&id=<?= $t['id'] ?>"
                                       class="btn <?= $isCerrado ? 'btn-outline-secondary' : 'btn-outline-primary' ?> btn-sm"
                                       title="<?= $isCerrado ? 'Ver detalle (solo lectura)' : 'Atender ticket' ?>">
                                        <i class="bi bi-<?= $isCerrado ? 'lock' : 'wrench-adjustable' ?>"></i>
                                        <?= $isCerrado ? 'Ver' : 'Atender' ?>
                                    </a>
                                </td>

                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require BASE_PATH . '/app/views/layouts/footer.php'; ?>