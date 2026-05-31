<?php
/**
 * Vista: Folios por Validar
 * RF_10 — Panel exclusivo de Mesa de Ayuda (Rol 3).
 * Muestra los folios asignados a este usuario (id_mesa_asignada) que el técnico
 * marcó como "Terminado" y están en estatus 4 (Pendiente de Validación).
 *
 * Flujo básico:     Validar y Cerrar → estatus 5 (Cerrado).
 * Flujo Alt. 2a:   Rechazar y Devolver → estatus 2 (En Proceso) + nota interna.
 */
require BASE_PATH . '/app/views/layouts/header.php';

$flashSuccess = $_SESSION['flash_success'] ?? null;
$flashError   = $_SESSION['flash_error']   ?? null;
unset($_SESSION['flash_success'], $_SESSION['flash_error']);
?>

<!-- Breadcrumb -->
<div class="hd-breadcrumb fade-in-up">
    <a href="<?= BASE_URL ?>/index.php?controller=Dashboard&action=index"><i class="bi bi-house-fill"></i></a>
    <i class="bi bi-chevron-right" style="font-size:.7rem;"></i>
    <span>Folios por Validar</span>
</div>

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

<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2 fade-in-up">
    <div>
        <h1 class="h4 mb-1"><i class="bi bi-clipboard-check text-accent me-2"></i>Folios por Validar</h1>
        <p class="mb-0" style="font-size:0.82rem;">
            <span class="text-accent fw-semibold"><?= count($tickets) ?></span>
            folio(s) terminado(s) por el técnico, en espera de tu validación y cierre.
        </p>
    </div>
</div>

<div class="hd-card fade-in-up delay-1">
    <div class="hd-card-body p-0">
        <?php if (empty($tickets)): ?>
            <div class="empty-state">
                <i class="bi bi-check2-circle d-block text-success" style="font-size:2.2rem;"></i>
                <p>No tienes folios pendientes de validación. ¡Todo al día!</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Folio</th>
                            <th>Solicitante</th>
                            <th class="d-none d-md-table-cell">Descripcion</th>
                            <th class="d-none d-sm-table-cell">Fecha</th>
                            <th class="text-end">Accion</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tickets as $t): ?>
                            <tr>
                                <td><span class="folio-tag"><?= htmlspecialchars($t['folio']) ?></span></td>
                                <td>
                                    <div style="font-weight:500; max-width:140px;"
                                         class="text-truncate">
                                        <?= htmlspecialchars($t['solicitante']) ?>
                                    </div>
                                    <div style="font-size:0.74rem; color:var(--text-muted);">
                                        <?= htmlspecialchars($t['clave'] ?? '') ?>
                                    </div>
                                </td>
                                <td class="d-none d-md-table-cell"
                                    style="font-size:0.8rem; max-width:240px; color:var(--text-secondary);">
                                    <?= htmlspecialchars(mb_strimwidth(strip_tags($t['descripcion']), 0, 60, '…')) ?>
                                </td>
                                <td class="d-none d-sm-table-cell"
                                    style="font-size:0.78rem; color:var(--text-muted); white-space:nowrap;">
                                    <?= date('d/m/Y', strtotime($t['fecha_creacion'])) ?>
                                    <div style="font-size:0.72rem;">
                                        <?= date('H:i', strtotime($t['fecha_creacion'])) ?> hrs
                                    </div>
                                </td>
                                <td class="text-end">
                                    <div class="d-flex gap-2 justify-content-end flex-wrap">
                                        <a href="<?= BASE_URL ?>/index.php?controller=Ticket&action=show&id=<?= $t['id'] ?>"
                                           class="btn btn-outline-primary btn-sm" title="Revisar el detalle del folio">
                                            <i class="bi bi-box-arrow-in-right"></i>
                                            <span class="d-none d-sm-inline">Revisar</span>
                                        </a>
                                        <form method="POST" action="<?= BASE_URL ?>/index.php?controller=Ticket&action=cerrar" class="d-inline">
                                            <input type="hidden" name="id_ticket" value="<?= $t['id'] ?>">
                                            <button type="submit" class="btn btn-outline-success btn-sm"
                                                    data-confirm="¿Validar y cerrar el folio <?= htmlspecialchars($t['folio']) ?>?">
                                                <i class="bi bi-check2-circle"></i>
                                                <span class="d-none d-sm-inline">Cerrar</span>
                                            </button>
                                        </form>
                                        <!-- RF_10 Flujo Alt. 2a: Rechazar y devolver a En Proceso -->
                                        <button type="button" class="btn btn-outline-danger btn-sm"
                                                title="Rechazar y devolver al técnico"
                                                data-id="<?= $t['id'] ?>"
                                                data-folio="<?= htmlspecialchars($t['folio']) ?>"
                                                onclick="abrirModalRechazar(this)">
                                            <i class="bi bi-arrow-counterclockwise"></i>
                                            <span class="d-none d-sm-inline">Rechazar</span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- ══ Modal: RF_10 Flujo Alt. 2a — Rechazar y Devolver (compartido) ═══════ -->
<div class="modal fade" id="modalRechazarPV" tabindex="-1" aria-labelledby="modalRechazarPVLabel">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalRechazarPVLabel">
                    <i class="bi bi-arrow-counterclockwise me-2 text-danger"></i>
                    Rechazar y Devolver a En Proceso
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST"
                  action="<?= BASE_URL ?>/index.php?controller=Ticket&action=rechazarValidacion"
                  id="formRechazarPV">
                <div class="modal-body">
                    <input type="hidden" name="id_ticket" id="rechazarIdTicket" value="">
                    <div class="alert alert-warning mb-3" style="font-size:0.82rem;">
                        <i class="bi bi-exclamation-triangle me-1"></i>
                        El folio <strong id="rechazarFolioLabel"></strong> regresará al estado
                        <strong>En Proceso</strong>. La razón quedará registrada como nota interna.
                    </div>
                    <div class="mb-0">
                        <label class="form-label" for="razon_rechazo_pv">
                            Razón del rechazo <span style="color:var(--danger);">*</span>
                        </label>
                        <textarea class="form-control" id="razon_rechazo_pv" name="razon_rechazo"
                                  rows="3" required
                                  placeholder="Describe qué falta o qué debe corregir el técnico..."></textarea>
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

<script>
function abrirModalRechazar(btn) {
    document.getElementById('rechazarIdTicket').value    = btn.dataset.id;
    document.getElementById('rechazarFolioLabel').textContent = btn.dataset.folio;
    document.getElementById('razon_rechazo_pv').value   = '';
    new bootstrap.Modal(document.getElementById('modalRechazarPV')).show();
}
</script>

<?php require BASE_PATH . '/app/views/layouts/footer.php'; ?>
