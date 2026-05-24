<?php
/**
 * Vista: Mis Tickets Asignados
 * RF_06 — Solo Técnico (Rol 2)
 */
require BASE_PATH . '/app/views/layouts/header.php';

$flashSuccess = $_SESSION['flash_success'] ?? null;
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

function badgeEMT(string $e): string {
    return match (strtolower($e)) {
        'abierto'    => 'badge-abierto',
        'en proceso' => 'badge-proceso',
        'cerrado'    => 'badge-cerrado',
        default      => 'badge-pendiente',
    };
}
function badgePMT(string $p): string {
    return match (strtolower($p)) {
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
    <span>Mis Folios Asignados</span>
</div>

<?php if ($flashSuccess): ?>
    <div class="alert alert-success alert-dismissible d-flex align-items-center gap-2 mb-4 fade-in-up">
        <i class="bi bi-check-circle-fill"></i><span><?= $flashSuccess ?></span>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2 fade-in-up">
    <div>
        <h1 class="h4 mb-1"><i class="bi bi-person-lines-fill me-2 text-accent"></i>Mis Folios Asignados</h1>
        <p class="mb-0" style="font-size:0.82rem;">
            <span class="text-accent fw-semibold"><?= count($tickets) ?></span> ticket(s) a tu cargo
        </p>
    </div>
</div>

<!-- Filtro de estatus -->
<div class="hd-card mb-4 fade-in-up delay-1">
    <div class="hd-card-header">
        <h2 class="hd-card-title"><i class="bi bi-funnel text-accent"></i> Filtrar por Estatus</h2>
    </div>
    <div class="hd-card-body">
        <form method="GET" action="<?= BASE_URL ?>/index.php">
            <input type="hidden" name="controller" value="Ticket">
            <input type="hidden" name="action" value="misTickets">
            <div class="row g-2 align-items-end">
                <div class="col-12 col-md-4">
                    <select class="form-select" name="id_estatus" id="id_estatus_mis">
                        <option value="">Todos los estatus</option>
                        <?php foreach ($estatus as $e): ?>
                            <option value="<?= $e['id'] ?>" <?= ($filtros['id_estatus'] ?? '') == $e['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($e['nombre_estatus']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="bi bi-search"></i> Filtrar
                    </button>
                    <a href="<?= BASE_URL ?>/index.php?controller=Ticket&action=misTickets" class="btn btn-outline-secondary btn-sm ms-1">
                        <i class="bi bi-x-circle"></i>
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Tabla de tickets -->
<div class="hd-card fade-in-up delay-2">
    <div class="hd-card-body p-0">
        <?php if (empty($tickets)): ?>
            <div class="empty-state">
                <i class="bi bi-inbox d-block"></i>
                <p>No tienes tickets asignados con este filtro.</p>
            </div>
        <?php else: ?>
            <div class="hd-table-wrapper" style="border:none; border-radius:0;">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Folio</th>
                            <th>Solicitante</th>
                            <th>Canal</th>
                            <th>Prioridad</th>
                            <th>Estatus</th>
                            <th>Fecha</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tickets as $t): ?>
                            <tr>
                                <td><span class="folio-tag"><?= htmlspecialchars($t['folio']) ?></span></td>
                                <td>
                                    <div style="font-weight:500;"><?= htmlspecialchars($t['solicitante']) ?></div>
                                    <div style="font-size:0.74rem; color:var(--text-muted);"><?= htmlspecialchars($t['clave']) ?></div>
                                </td>
                                <td style="font-size:0.82rem;"><?= htmlspecialchars($t['canal']) ?></td>
                                <td>
                                    <span class="hd-badge <?= badgePMT($t['prioridad']) ?>">
                                        <?= ucfirst($t['prioridad']) ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="hd-badge <?= badgeEMT($t['estatus']) ?>">
                                        <?= htmlspecialchars($t['estatus']) ?>
                                    </span>
                                </td>
                                <td style="font-size:0.78rem; color:var(--text-muted); white-space:nowrap;">
                                    <?= date('d/m/Y H:i', strtotime($t['fecha_creacion'])) ?>
                                </td>
                                <td>
                                    <a href="<?= BASE_URL ?>/index.php?controller=Ticket&action=show&id=<?= $t['id'] ?>"
                                       class="btn btn-outline-primary btn-sm">
                                        <i class="bi bi-eye"></i> Ver
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
