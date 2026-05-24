<?php
/**
 * Vista: Tablero general de tickets
 * RF_04 / RF_05 — Filtros por folio y nombre del solicitante
 * Roles: Mesa de Ayuda (3), Coordinador (1)
 */
require BASE_PATH . '/app/views/layouts/header.php';

$flashSuccess = $_SESSION['flash_success'] ?? null;
$flashError   = $_SESSION['flash_error']   ?? null;
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

function badgeEstatusIdx(string $e): string {
    return match (strtolower($e)) {
        'abierto'    => 'badge-abierto',
        'en proceso' => 'badge-proceso',
        'cerrado'    => 'badge-cerrado',
        default      => 'badge-pendiente',
    };
}

function badgePrioridadIdx(string $p): string {
    return match (strtolower($p)) {
        'alta'  => 'badge-alta',
        'media' => 'badge-media',
        'baja'  => 'badge-baja',
        default => 'badge-pendiente',
    };
}
?>

<!-- Breadcrumb -->
<div class="hd-breadcrumb">
    <a href="<?= BASE_URL ?>/index.php?controller=Dashboard&action=index"><i class="bi bi-house-fill"></i></a>
    <i class="bi bi-chevron-right" style="font-size:.7rem;"></i>
    <span>Todos los Tickets</span>
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

<!-- Encabezado + botón nuevo ticket -->
<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2 fade-in-up">
    <div>
        <h1 class="h4 mb-1"><i class="bi bi-ticket-perforated me-2 text-accent"></i>Tablero de Tickets</h1>
        <p class="mb-0" style="font-size:0.82rem;">
            <span class="text-accent fw-semibold"><?= count($tickets) ?></span> resultado(s) encontrados
        </p>
    </div>
    <?php if ($_SESSION['rol_id'] == 3): ?>
        <a href="<?= BASE_URL ?>/index.php?controller=Ticket&action=create" class="btn btn-primary">
            <i class="bi bi-plus-lg"></i> Nuevo Ticket
        </a>
    <?php endif; ?>
</div>

<!-- ═══ FILTROS ══════════════════════════════════════════════════════════════ -->
<div class="hd-card mb-4 fade-in-up delay-1">
    <div class="hd-card-header">
        <h2 class="hd-card-title"><i class="bi bi-funnel text-accent"></i> Filtros de Búsqueda</h2>
        <a href="<?= BASE_URL ?>/index.php?controller=Ticket&action=index" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-x-circle"></i> Limpiar
        </a>
    </div>
    <div class="hd-card-body">
        <form method="GET" action="<?= BASE_URL ?>/index.php" id="formFiltros">
            <input type="hidden" name="controller" value="Ticket">
            <input type="hidden" name="action" value="index">
            <div class="row g-3">
                <div class="col-12 col-md-4">
                    <label class="form-label" for="folio">Folio</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <input type="text" class="form-control" id="folio" name="folio"
                               placeholder="HD-20240101-0001"
                               value="<?= htmlspecialchars($filtros['folio'] ?? '') ?>">
                    </div>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label" for="solicitante">Nombre del Solicitante</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-person"></i></span>
                        <input type="text" class="form-control" id="solicitante" name="solicitante"
                               placeholder="Buscar por nombre..."
                               value="<?= htmlspecialchars($filtros['solicitante'] ?? '') ?>">
                    </div>
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label" for="id_estatus">Estatus</label>
                    <select class="form-select" id="id_estatus" name="id_estatus">
                        <option value="">Todos los estatus</option>
                        <?php foreach ($estatus as $e): ?>
                            <option value="<?= $e['id'] ?>"
                                <?= ($filtros['id_estatus'] ?? '') == $e['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($e['nombre_estatus']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-md-1 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-search"></i>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- ═══ TABLA DE TICKETS ═════════════════════════════════════════════════════ -->
<div class="hd-card fade-in-up delay-2">
    <div class="hd-card-body p-0">
        <?php if (empty($tickets)): ?>
            <div class="empty-state">
                <i class="bi bi-ticket-perforated d-block"></i>
                <p>No se encontraron tickets con los filtros aplicados.</p>
                <a href="<?= BASE_URL ?>/index.php?controller=Ticket&action=index" class="btn btn-outline-primary btn-sm mt-2">
                    <i class="bi bi-x-circle"></i> Limpiar filtros
                </a>
            </div>
        <?php else: ?>
            <!-- Vista tabla (desktop) -->
            <div class="d-none d-md-block">
                <div class="hd-table-wrapper" style="border:none; border-radius:0;">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Folio</th>
                                <th>Solicitante</th>
                                <th>Canal</th>
                                <th>Prioridad</th>
                                <th>Estatus</th>
                                <th>Técnico</th>
                                <th>Fecha</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tickets as $i => $t): ?>
                                <tr>
                                    <td style="color:var(--text-muted);"><?= $i + 1 ?></td>
                                    <td><span class="folio-tag"><?= htmlspecialchars($t['folio']) ?></span></td>
                                    <td>
                                        <div style="font-weight:500;"><?= htmlspecialchars($t['solicitante']) ?></div>
                                        <div style="font-size:0.74rem; color:var(--text-muted);"><?= htmlspecialchars($t['clave']) ?></div>
                                    </td>
                                    <td style="font-size:0.82rem;"><?= htmlspecialchars($t['canal']) ?></td>
                                    <td>
                                        <span class="hd-badge <?= badgePrioridadIdx($t['prioridad']) ?>">
                                            <?= ucfirst($t['prioridad']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="hd-badge <?= badgeEstatusIdx($t['estatus']) ?>">
                                            <?= htmlspecialchars($t['estatus']) ?>
                                        </span>
                                    </td>
                                    <td style="font-size:0.82rem;">
                                        <?= $t['tecnico']
                                            ? htmlspecialchars($t['tecnico'])
                                            : '<span class="text-muted-hd"><i class="bi bi-dash"></i> Sin asignar</span>' ?>
                                    </td>
                                    <td style="font-size:0.78rem; color:var(--text-muted); white-space:nowrap;">
                                        <?= date('d/m/Y', strtotime($t['fecha_creacion'])) ?><br>
                                        <span style="font-size:0.72rem;"><?= date('H:i', strtotime($t['fecha_creacion'])) ?></span>
                                    </td>
                                    <td>
                                        <div class="d-flex gap-2">
                                            <a href="<?= BASE_URL ?>/index.php?controller=Ticket&action=show&id=<?= $t['id'] ?>"
                                            class="btn btn-action-edit btn-sm" title="Ver / Editar">
                                                <i class="bi bi-pencil-fill"></i>
                                            </a>
                                            
                                            <?php if ($_SESSION['rol_id'] == 1): /* Solo el coordinador puede ver este botón extra */ ?>
                                                <button type="button" class="btn btn-action-delete btn-sm" title="Eliminar">
                                                    <i class="bi bi-trash-fill"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Vista tarjetas (mobile) -->
            <div class="d-md-none p-3">
                <div class="d-flex flex-column gap-3">
                    <?php foreach ($tickets as $t): ?>
                        <div class="hd-card" style="background:rgba(255,255,255,0.03);">
                            <div class="hd-card-body">
                                <div class="d-flex align-items-start justify-content-between mb-2">
                                    <span class="folio-tag"><?= htmlspecialchars($t['folio']) ?></span>
                                    <span class="hd-badge <?= badgeEstatusIdx($t['estatus']) ?>"><?= htmlspecialchars($t['estatus']) ?></span>
                                </div>
                                <div class="fw-semibold mb-1"><?= htmlspecialchars($t['solicitante']) ?></div>
                                <div class="d-flex gap-2 flex-wrap mb-2">
                                    <span class="hd-badge <?= badgePrioridadIdx($t['prioridad']) ?>"><?= ucfirst($t['prioridad']) ?></span>
                                    <span style="font-size:0.75rem; color:var(--text-muted);"><?= htmlspecialchars($t['canal']) ?></span>
                                </div>
                                <a href="<?= BASE_URL ?>/index.php?controller=Ticket&action=show&id=<?= $t['id'] ?>"
                                   class="btn btn-outline-primary btn-sm w-100 mt-1">
                                    <i class="bi bi-eye"></i> Ver Detalle
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require BASE_PATH . '/app/views/layouts/footer.php'; ?>
