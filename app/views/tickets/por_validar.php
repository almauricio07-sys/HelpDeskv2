<?php
/**
 * Vista: Folios por Validar
 * RF_10 — Panel exclusivo de Mesa de Ayuda (Rol 3).
 * Muestra los folios asignados a este usuario (id_mesa_asignada) que el técnico
 * marcó como "Terminado" y están en estatus 5 (Pendiente de Validación).
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
            <div class="hd-table-wrapper" style="border:none; border-radius:0;">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Folio</th>
                            <th>Solicitante</th>
                            <th>Descripción</th>
                            <th>Fecha de creación</th>
                            <th class="text-end">Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tickets as $t): ?>
                            <tr>
                                <td><span class="folio-tag"><?= htmlspecialchars($t['folio']) ?></span></td>
                                <td>
                                    <div style="font-weight:500;"><?= htmlspecialchars($t['solicitante']) ?></div>
                                    <div style="font-size:0.74rem; color:var(--text-muted);"><?= htmlspecialchars($t['clave'] ?? '') ?></div>
                                </td>
                                <td style="font-size:0.8rem; max-width:280px; color:var(--text-secondary);">
                                    <?= htmlspecialchars(mb_strimwidth($t['descripcion'], 0, 70, '…')) ?>
                                </td>
                                <td style="font-size:0.78rem; color:var(--text-muted); white-space:nowrap;">
                                    <?= date('d/m/Y H:i', strtotime($t['fecha_creacion'])) ?>
                                </td>
                                <td class="text-end">
                                    <div class="d-inline-flex gap-2">
                                        <a href="<?= BASE_URL ?>/index.php?controller=Ticket&action=show&id=<?= $t['id'] ?>"
                                           class="btn btn-outline-primary btn-sm" title="Revisar el detalle del folio">
                                            <i class="bi bi-box-arrow-in-right"></i> Revisar
                                        </a>
                                        <form method="POST" action="<?= BASE_URL ?>/index.php?controller=Ticket&action=cerrar" class="d-inline">
                                            <input type="hidden" name="id_ticket" value="<?= $t['id'] ?>">
                                            <button type="submit" class="btn btn-outline-success btn-sm"
                                                    data-confirm="¿Validar y cerrar el folio <?= htmlspecialchars($t['folio']) ?>? Se registrará la nota 'Validado'.">
                                                <i class="bi bi-check2-circle"></i> Cerrar
                                            </button>
                                        </form>
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

<?php require BASE_PATH . '/app/views/layouts/footer.php'; ?>
