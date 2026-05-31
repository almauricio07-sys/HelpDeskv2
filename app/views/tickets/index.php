<?php
/**
 * Vista: Tablero general de tickets
 * RF_04 — Visualización del listado completo de folios.
 * RF_05 — Búsqueda unificada por folio o nombre del solicitante.
 * Roles: Mesa de Ayuda (3), Coordinador (1)
 */
require BASE_PATH . '/app/views/layouts/header.php';

$flashSuccess = $_SESSION['flash_success'] ?? null;
$flashError   = $_SESSION['flash_error']   ?? null;
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

// Detectar si hay criterios de búsqueda activos (para distinguir los empty states)
$hayFiltros = ($buscar !== '' || $idEstatus > 0);

function badgeEstatusIdx(string $e): string {
    return match (strtolower(trim($e))) {
        'pendiente de asignación', 'pendiente de asignacion' => 'badge-pendiente',
        'en proceso'                                          => 'badge-proceso',
        'terminado'                                           => 'badge-media',
        'pendiente de validación', 'pendiente de validacion'  => 'badge-abierto',
        'cerrado'                                             => 'badge-cerrado',
        default                                               => 'badge-pendiente',
    };
}

function badgePrioridadIdx(string $p): string {
    return match (strtolower(trim($p))) {
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
    <span>Todos los Tickets</span>
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

<!-- Encabezado + botón nuevo ticket -->
<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2 fade-in-up">
    <div>
        <h1 class="h4 mb-1">
            <i class="bi bi-ticket-perforated me-2 text-accent"></i>Tablero de Tickets
        </h1>
        <p class="mb-0" style="font-size:0.82rem; color:var(--text-muted);">
            <?php if ($hayFiltros): ?>
                <span class="text-accent fw-semibold"><?= count($tickets) ?></span>
                resultado(s) para el criterio ingresado
                &mdash;
                <a href="<?= BASE_URL ?>/index.php?controller=Ticket&action=index"
                   style="color:var(--text-muted); text-decoration:none; font-size:0.8rem;">
                    <i class="bi bi-x-circle"></i> Limpiar filtros
                </a>
            <?php else: ?>
                <span class="text-accent fw-semibold"><?= count($tickets) ?></span>
                reporte(s) registrado(s) en el sistema
            <?php endif; ?>
        </p>
    </div>
    <?php if ($_SESSION['rol_id'] == 3): ?>
        <a href="<?= BASE_URL ?>/index.php?controller=Ticket&action=create"
           class="btn btn-primary">
            <i class="bi bi-plus-lg"></i> Nuevo Ticket
        </a>
    <?php endif; ?>
</div>

<!-- ═══ FILTROS (RF_05) ═══════════════════════════════════════════════════════ -->
<div class="hd-card mb-4 fade-in-up delay-1">
    <div class="hd-card-header">
        <h2 class="hd-card-title">
            <i class="bi bi-search text-accent"></i> Búsqueda y Filtros
        </h2>
        <?php if ($hayFiltros): ?>
            <a href="<?= BASE_URL ?>/index.php?controller=Ticket&action=index"
               class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-x-circle"></i> Limpiar
            </a>
        <?php endif; ?>
    </div>
    <div class="hd-card-body">
        <form method="GET" action="<?= BASE_URL ?>/index.php" id="formFiltros">
            <input type="hidden" name="controller" value="Ticket">
            <input type="hidden" name="action"     value="index">
            <div class="row g-3 align-items-end">

                <!-- Búsqueda unificada: folio O nombre del solicitante -->
                <div class="col-12 col-md-5">
                    <label class="form-label" for="buscar">
                        Número de folio o nombre del solicitante
                    </label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <input type="text" class="form-control" id="buscar" name="buscar"
                               placeholder="HD-20240101-0001 o Nombre del solicitante..."
                               value="<?= htmlspecialchars($buscar) ?>"
                               autocomplete="off">
                    </div>
                </div>

                <!-- Filtro por estatus -->
                <div class="col-12 col-md-4">
                    <label class="form-label" for="id_estatus">Estatus</label>
                    <select class="form-select" id="id_estatus" name="id_estatus">
                        <option value="">Todos los estatus</option>
                        <?php foreach ($estatus as $e): ?>
                            <option value="<?= $e['id'] ?>"
                                <?= $idEstatus === (int) $e['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($e['nombre_estatus']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Botón buscar -->
                <div class="col-12 col-md-auto d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-search"></i> Buscar
                    </button>
                </div>

            </div>
        </form>
    </div>
</div>

<!-- ═══ TABLA DE TICKETS (RF_04) ═════════════════════════════════════════════ -->
<div class="hd-card fade-in-up delay-2">
    <div class="hd-card-body p-0">

        <?php if (empty($tickets)): ?>
            <!-- ── Empty states diferenciados (RF_04 / RF_05) ─────────────── -->
            <div class="empty-state py-5">
                <i class="bi bi-ticket-perforated d-block mb-3"
                   style="font-size:2.5rem; color:var(--text-muted);"></i>

                <?php if ($hayFiltros): ?>
                    <!-- Flujo alternativo RF_05 -->
                    <p class="mb-3">No se encontraron resultados para el criterio ingresado.</p>
                    <a href="<?= BASE_URL ?>/index.php?controller=Ticket&action=index"
                       class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-x-circle"></i> Limpiar filtros
                    </a>
                <?php else: ?>
                    <!-- Flujo alternativo RF_04 -->
                    <p class="mb-3">No hay reportes registrados para mostrar.</p>
                    <?php if ($_SESSION['rol_id'] == 3): ?>
                        <a href="<?= BASE_URL ?>/index.php?controller=Ticket&action=create"
                           class="btn btn-primary btn-sm">
                            <i class="bi bi-plus-lg"></i> Registrar primer ticket
                        </a>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

        <?php else: ?>
            <!-- ── Tabla responsive (RF_04) ───────────────────────────────── -->
            <div class="table-responsive" style="border-radius:0;">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="d-none d-md-table-cell ps-3" style="width:2.5rem;">#</th>
                            <th>Folio</th>
                            <th>Solicitante</th>
                            <th class="d-none d-md-table-cell">Medio de Captura</th>
                            <th class="d-none d-lg-table-cell">Descripción</th>
                            <th class="d-none d-sm-table-cell">Fecha</th>
                            <th>Estatus</th>
                            <th class="d-none d-md-table-cell">Técnico Responsable</th>
                            <th class="text-end pe-3">Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tickets as $i => $t): ?>
                            <tr>

                                <!-- # contador -->
                                <td class="d-none d-md-table-cell ps-3"
                                    style="color:var(--text-muted); font-size:0.8rem;">
                                    <?= $i + 1 ?>
                                </td>

                                <!-- Folio -->
                                <td>
                                    <span class="folio-tag">
                                        <?= htmlspecialchars($t['folio']) ?>
                                    </span>
                                </td>

                                <!-- Solicitante + clave -->
                                <td>
                                    <div class="text-truncate"
                                         style="font-weight:500; max-width:150px;">
                                        <?= htmlspecialchars($t['solicitante']) ?>
                                    </div>
                                    <div style="font-size:0.73rem; color:var(--text-muted);">
                                        <?= htmlspecialchars($t['clave'] ?? '') ?>
                                    </div>
                                </td>

                                <!-- Medio de captura (canal) -->
                                <td class="d-none d-md-table-cell"
                                    style="font-size:0.82rem; color:var(--text-secondary);">
                                    <?= htmlspecialchars($t['canal'] ?? '—') ?>
                                </td>

                                <!-- Descripción truncada a 40 caracteres -->
                                <td class="d-none d-lg-table-cell" style="max-width:240px;">
                                    <span class="text-truncate d-block"
                                          style="font-size:0.8rem; color:var(--text-muted);"
                                          title="<?= htmlspecialchars($t['descripcion']) ?>">
                                        <?= htmlspecialchars(
                                            mb_strimwidth(strip_tags($t['descripcion']), 0, 40, '…')
                                        ) ?>
                                    </span>
                                </td>

                                <!-- Fecha -->
                                <td class="d-none d-sm-table-cell"
                                    style="font-size:0.78rem; color:var(--text-muted); white-space:nowrap;">
                                    <?= date('d/m/Y', strtotime($t['fecha_creacion'])) ?>
                                    <div style="font-size:0.72rem;">
                                        <?= date('H:i', strtotime($t['fecha_creacion'])) ?> hrs
                                    </div>
                                </td>

                                <!-- Estatus -->
                                <td>
                                    <span class="hd-badge <?= badgeEstatusIdx($t['estatus']) ?>">
                                        <?= htmlspecialchars($t['estatus']) ?>
                                    </span>
                                </td>

                                <!-- Técnico responsable -->
                                <td class="d-none d-md-table-cell" style="font-size:0.82rem;">
                                    <?php if ($t['tecnico']): ?>
                                        <div class="text-truncate" style="max-width:130px;">
                                            <?= htmlspecialchars($t['tecnico']) ?>
                                        </div>
                                    <?php else: ?>
                                        <span style="color:var(--text-muted);
                                                     font-style:italic;
                                                     font-size:0.78rem;">
                                            <i class="bi bi-dash"></i> Sin asignar
                                        </span>
                                    <?php endif; ?>
                                </td>

                                <!-- Acciones -->
                                <td class="text-end pe-3">
                                    <div class="d-flex gap-2 justify-content-end">
                                        <a href="<?= BASE_URL ?>/index.php?controller=Ticket&action=show&id=<?= $t['id'] ?>"
                                           class="btn btn-outline-primary btn-sm"
                                           title="Ver / Editar folio">
                                            <i class="bi bi-eye"></i>
                                            <span class="d-none d-sm-inline ms-1">Ver</span>
                                        </a>
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
