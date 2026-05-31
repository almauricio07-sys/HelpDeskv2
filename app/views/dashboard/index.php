<?php
/**
 * Vista: Dashboard
 * Panel principal diferenciado por rol.
 *
 * Roles: 1=Coordinador | 2=Soporte Técnico | 3=Mesa de Ayuda
 * Estatus: 1=Pendiente Asignación | 2=En Proceso | 3=Terminado
 *          4=Pendiente Validación | 5=Cerrado
 */

function badgeEstatus(string $estatus): string {
    return match (strtolower(trim($estatus))) {
        'pendiente de asignación' => 'badge-pendiente',
        'en proceso'              => 'badge-proceso',
        'terminado'               => 'badge-media',
        'pendiente de validación' => 'badge-abierto',
        'cerrado'                 => 'badge-cerrado',
        default                   => 'badge-pendiente',
    };
}

function badgePrioridad(string $prioridad): string {
    return match (strtolower(trim($prioridad))) {
        'alta'  => 'badge-alta',
        'media' => 'badge-media',
        'baja'  => 'badge-baja',
        default => 'badge-pendiente',
    };
}

require BASE_PATH . '/app/views/layouts/header.php';

$flashSuccess = $_SESSION['flash_success'] ?? null;
$flashError   = $_SESSION['flash_error']   ?? null;
unset($_SESSION['flash_success'], $_SESSION['flash_error']);
?>

<div class="hd-breadcrumb">
    <i class="bi bi-house-fill"></i>
    <span>Dashboard</span>
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

<!-- Saludo -->
<div class="mb-4 fade-in-up">
    <h1 class="h4 mb-1">
        <?php
        $hora    = (int) date('H');
        $saludo  = $hora < 12 ? 'Buenos días' : ($hora < 19 ? 'Buenas tardes' : 'Buenas noches');
        echo $saludo . ', <span style="color:var(--accent)">' . htmlspecialchars($_SESSION['nombre']) . '</span>';
        ?>
    </h1>
    <p class="mb-0" style="font-size:0.85rem; color:var(--text-muted);">
        <?= date('l, j \d\e F \d\e Y', strtotime('today')) ?>
        &mdash; <span class="role-badge"><?= htmlspecialchars($_SESSION['rol_nombre']) ?></span>
    </p>
</div>

<?php /* ═══════════════════════════════════════════════════════════════
        COORDINADOR (Rol 1): RF_14 y RF_15
       ═══════════════════════════════════════════════════════════════ */
if ($rolId == 1): ?>

    <!-- Tarjetas de resumen -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3 fade-in-up delay-1">
            <div class="stat-card">
                <div class="stat-icon blue"><i class="bi bi-ticket-perforated-fill"></i></div>
                <div>
                    <div class="stat-value"><?= $totalTickets ?></div>
                    <div class="stat-label">Total Tickets</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3 fade-in-up delay-2">
            <div class="stat-card">
                <div class="stat-icon amber"><i class="bi bi-clock-history"></i></div>
                <div>
                    <div class="stat-value"><?= $ticketsActivos ?></div>
                    <div class="stat-label">En Gestión</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3 fade-in-up delay-3">
            <div class="stat-card">
                <div class="stat-icon green"><i class="bi bi-check-circle-fill"></i></div>
                <div>
                    <div class="stat-value"><?= $ticketsCerrados ?></div>
                    <div class="stat-label">Cerrados</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3 fade-in-up delay-4">
            <div class="stat-card">
                <div class="stat-icon purple"><i class="bi bi-people-fill"></i></div>
                <div>
                    <div class="stat-value"><?= $totalUsuarios ?></div>
                    <div class="stat-label">Usuarios del Sistema</div>
                </div>
            </div>
        </div>
    </div>

    <!-- RF_14 y RF_15 -->
    <div class="row g-3 mb-4">

        <!-- RF_14: Gráfica Activos vs Cerrados -->
        <div class="col-md-4 fade-in-up delay-1">
            <div class="hd-card h-100">
                <div class="hd-card-header">
                    <h2 class="hd-card-title">
                        <i class="bi bi-pie-chart-fill text-accent"></i> Proporción Global
                    </h2>
                </div>
                <div class="hd-card-body">
                    <?php if ($totalTickets > 0): ?>
                        <div class="chart-container d-flex align-items-center justify-content-center"
                             style="min-height:220px;">
                            <canvas id="chartActivosCerrados" style="max-height:210px;"></canvas>
                        </div>

                        <!-- Desglose por estatus (RF_14 — tabla detallada) -->
                        <div class="mt-3" style="border-top: 1px solid rgba(255,255,255,0.06); padding-top:12px;">
                            <p style="font-size:0.72rem; color:var(--text-muted); margin-bottom:8px; text-transform:uppercase; letter-spacing:.05em;">
                                Desglose por estatus
                            </p>
                            <div class="d-flex flex-column gap-2">
                                <?php foreach ($statsPorEstatus as $s): ?>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="hd-badge <?= badgeEstatus($s['nombre_estatus']) ?>"
                                              style="font-size:0.67rem;">
                                            <?= htmlspecialchars($s['nombre_estatus']) ?>
                                        </span>
                                        <span style="font-size:0.85rem; font-weight:600; color:var(--text-primary);">
                                            <?= (int) $s['total'] ?>
                                        </span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <!-- RF_14 Flujo alternativo: sin datos -->
                        <div class="empty-state py-4">
                            <i class="bi bi-bar-chart-line d-block mb-2"
                               style="font-size:2rem; color:var(--text-muted);"></i>
                            <p class="mb-0" style="font-size:0.82rem;">
                                No hay datos suficientes para generar indicadores visuales.
                            </p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- RF_15: Carga de Trabajo por Usuario -->
        <div class="col-md-8 fade-in-up delay-2">
            <div class="hd-card h-100">
                <div class="hd-card-header d-flex flex-column flex-md-row
                            justify-content-between align-items-md-center gap-2">
                    <h2 class="hd-card-title mb-0">
                        <i class="bi bi-bar-chart-line-fill text-accent"></i>
                        Carga de Trabajo por Usuario
                    </h2>
                    <div class="d-flex flex-wrap gap-2">
                        <input type="text" id="filterName" class="form-control form-control-sm"
                               placeholder="🔍 Buscar nombre..."
                               style="min-width:150px; max-width:190px;">
                        <select id="filterRole" class="form-select form-select-sm"
                                style="min-width:140px; max-width:175px;">
                            <option value="">Todos los roles</option>
                            <option value="Soporte Técnico">Soporte Técnico</option>
                            <option value="Mesa de Ayuda">Mesa de Ayuda</option>
                        </select>
                    </div>
                </div>
                <div class="hd-card-body">
                    <!-- El contenedor del gráfico y el empty-state se alternan via JS -->
                    <div id="chartWrapper" class="chart-container" style="min-height:260px;">
                        <canvas id="chartTicketsUsuario" style="max-height:250px;"></canvas>
                    </div>
                    <div id="rf15EmptyState" class="empty-state py-4" style="display:none;">
                        <i class="bi bi-person-x d-block mb-2"
                           style="font-size:2rem; color:var(--text-muted);"></i>
                        <p class="mb-0" style="font-size:0.82rem;">
                            Sin datos de asignación disponibles para el reporte de carga.
                        </p>
                    </div>
                </div>
            </div>
        </div>

    </div>

<?php /* ═══════════════════════════════════════════════════════════════
        SOPORTE TÉCNICO (Rol 2): sus tickets asignados
       ═══════════════════════════════════════════════════════════════ */
elseif ($rolId == 2): ?>

    <!-- Tarjetas de resumen — leen de $stats (obtenerEstadisticasTecnico) -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3 fade-in-up delay-1">
            <div class="stat-card">
                <div class="stat-icon blue"><i class="bi bi-ticket-perforated-fill"></i></div>
                <div>
                    <div class="stat-value"><?= $stats['total_asignados'] ?? 0 ?></div>
                    <div class="stat-label">Histórico Total</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3 fade-in-up delay-2">
            <div class="stat-card">
                <div class="stat-icon amber"><i class="bi bi-clock-history"></i></div>
                <div>
                    <div class="stat-value"><?= $stats['por_hacer'] ?? 0 ?></div>
                    <div class="stat-label">Por Atender</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3 fade-in-up delay-3">
            <div class="stat-card">
                <div class="stat-icon" style="background:rgba(14,165,233,.15);color:#0ea5e9;">
                    <i class="bi bi-send-check"></i>
                </div>
                <div>
                    <div class="stat-value"><?= $stats['en_validacion'] ?? 0 ?></div>
                    <div class="stat-label">En Validación</div>
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

    <!-- Tabla de pendientes — lee de $misTickets (obtenerTicketsPorTecnico) -->
    <div class="hd-card fade-in-up">
        <div class="hd-card-header">
            <h2 class="hd-card-title">
                <i class="bi bi-person-lines-fill text-accent"></i>
                Mis Folios Pendientes de Atención
            </h2>
            <a href="<?= BASE_URL ?>/index.php?controller=Ticket&action=misTickets"
               class="btn btn-outline-primary btn-sm">
                <i class="bi bi-list-ul"></i> Ver historial completo
            </a>
        </div>
        <div class="hd-card-body p-0">
            <?php if (empty($misTickets)): ?>
                <div class="empty-state py-4">
                    <i class="bi bi-emoji-smile d-block text-success mb-2" style="font-size:2rem;"></i>
                    <p class="mb-0">¡Sin tickets pendientes por atender en este momento!</p>
                </div>
            <?php else: ?>
                <div class="table-responsive" style="border-radius:0;">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Folio</th>
                                <th class="d-none d-md-table-cell">Descripción</th>
                                <th>Solicitante</th>
                                <th>Prioridad</th>
                                <th>Estatus</th>
                                <th class="d-none d-sm-table-cell">Fecha</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (array_slice($misTickets, 0, 6) as $t): ?>
                                <tr>
                                    <td>
                                        <span class="folio-tag"><?= htmlspecialchars($t['folio']) ?></span>
                                    </td>
                                    <td class="d-none d-md-table-cell" style="max-width:200px;">
                                        <span class="text-truncate d-block"
                                              style="font-size:0.8rem;color:var(--text-muted);"
                                              title="<?= htmlspecialchars(strip_tags($t['descripcion'])) ?>">
                                            <?= htmlspecialchars(mb_strimwidth(strip_tags($t['descripcion']), 0, 40, '...')) ?>
                                        </span>
                                    </td>
                                    <td class="text-truncate" style="max-width:130px;font-weight:500;">
                                        <?= htmlspecialchars($t['solicitante']) ?>
                                    </td>
                                    <td>
                                        <span class="hd-badge <?= badgePrioridad($t['prioridad']) ?>">
                                            <?= ucfirst(strtolower($t['prioridad'])) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="hd-badge <?= badgeEstatus($t['estatus']) ?>">
                                            <?= htmlspecialchars($t['estatus']) ?>
                                        </span>
                                    </td>
                                    <td class="d-none d-sm-table-cell"
                                        style="font-size:0.78rem;color:var(--text-muted);white-space:nowrap;">
                                        <?= date('d/m/Y', strtotime($t['fecha_creacion'])) ?>
                                        <div style="font-size:0.72rem;">
                                            <?= date('H:i', strtotime($t['fecha_creacion'])) ?> hrs
                                        </div>
                                    </td>
                                    <td>
                                        <a href="<?= BASE_URL ?>/index.php?controller=Ticket&action=show&id=<?= $t['id'] ?>"
                                           class="btn btn-action-edit btn-sm" title="Atender ticket">
                                            <i class="bi bi-wrench-adjustable"></i>
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

<?php /* ═══════════════════════════════════════════════════════════════
        MESA DE AYUDA (Rol 3): acciones rápidas y pendientes
       ═══════════════════════════════════════════════════════════════ */
else: ?>

    <div class="row g-3 mb-4">
        <div class="col-6 col-md-4 fade-in-up delay-1">
            <div class="stat-card">
                <div class="stat-icon blue"><i class="bi bi-ticket-perforated-fill"></i></div>
                <div>
                    <div class="stat-value"><?= $totalTickets ?></div>
                    <div class="stat-label">Total Tickets</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 fade-in-up delay-2">
            <div class="stat-card">
                <div class="stat-icon amber"><i class="bi bi-clock-history"></i></div>
                <div>
                    <div class="stat-value"><?= $ticketsActivos ?></div>
                    <div class="stat-label">En Gestión</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 fade-in-up delay-3">
            <div class="stat-card">
                <div class="stat-icon green"><i class="bi bi-check-circle-fill"></i></div>
                <div>
                    <div class="stat-value"><?= $ticketsCerrados ?></div>
                    <div class="stat-label">Cerrados</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4 fade-in-up delay-2">
        <div class="col-12 col-md-6">
            <a href="<?= BASE_URL ?>/index.php?controller=Ticket&action=create"
               class="hd-card d-flex align-items-center gap-3 p-3 text-decoration-none">
                <div class="stat-icon blue flex-shrink-0"><i class="bi bi-plus-circle-fill"></i></div>
                <div>
                    <div style="font-weight:600; color:var(--text-primary);">Registrar Nuevo Ticket</div>
                    <div style="font-size:0.8rem; color:var(--text-muted);">Captura un nuevo caso de soporte</div>
                </div>
                <i class="bi bi-chevron-right ms-auto" style="color:var(--text-muted);"></i>
            </a>
        </div>
        <div class="col-12 col-md-6">
            <a href="<?= BASE_URL ?>/index.php?controller=Ticket&action=index"
               class="hd-card d-flex align-items-center gap-3 p-3 text-decoration-none">
                <div class="stat-icon amber flex-shrink-0"><i class="bi bi-list-ul"></i></div>
                <div>
                    <div style="font-weight:600; color:var(--text-primary);">Ver Todos los Tickets</div>
                    <div style="font-size:0.8rem; color:var(--text-muted);">Consulta y filtra el tablero general</div>
                </div>
                <i class="bi bi-chevron-right ms-auto" style="color:var(--text-muted);"></i>
            </a>
        </div>
        <div class="col-12">
            <a href="<?= BASE_URL ?>/index.php?controller=Ticket&action=porValidar"
               class="hd-card d-flex align-items-center gap-3 p-3 text-decoration-none">
                <div class="stat-icon green flex-shrink-0"><i class="bi bi-clipboard-check"></i></div>
                <div>
                    <div style="font-weight:600; color:var(--text-primary);">Folios por Validar</div>
                    <div style="font-size:0.8rem; color:var(--text-muted);">
                        Terminados por el técnico, en espera de tu cierre definitivo
                    </div>
                </div>
                <span class="hd-badge <?= count($porValidar) > 0 ? 'badge-pendiente' : 'badge-cerrado' ?> ms-auto">
                    <?= count($porValidar) ?> pendiente(s)
                </span>
            </a>
        </div>
    </div>

    <div class="hd-card fade-in-up delay-3">
        <div class="hd-card-header">
            <h2 class="hd-card-title">
                <i class="bi bi-clock-history text-warning"></i>
                Pendiente de asignación
            </h2>
            <span class="hd-badge badge-pendiente">Requiere atención</span>
        </div>
        <div class="hd-card-body p-0">
            <?php if (empty($ticketsPendientes)): ?>
                <div class="empty-state py-4">
                    <i class="bi bi-check2-circle text-success d-block mb-2" style="font-size:2rem;"></i>
                    <p class="mb-0" style="color:var(--text-muted); font-size:0.85rem;">
                        No hay tickets pendientes de asignación en este momento.
                    </p>
                </div>
            <?php else: ?>
                <div class="hd-table-wrapper" style="border:none; border-radius:0;">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th style="width:30%;">Folio / Problema</th>
                                <th>Solicitante</th>
                                <th>Fecha</th>
                                <th>Estatus</th>
                                <th class="text-end">Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($ticketsPendientes as $t): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex flex-column gap-1">
                                            <span class="folio-tag d-inline-block" style="width:fit-content;">
                                                <?= htmlspecialchars($t['folio']) ?>
                                            </span>
                                            <div class="text-truncate" style="max-width:240px; font-size:0.8rem; color:var(--text-muted);"
                                                 title="<?= htmlspecialchars($t['descripcion']) ?>">
                                                <?= htmlspecialchars($t['descripcion']) ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td style="font-weight:500;">
                                        <?= htmlspecialchars($t['solicitante']) ?>
                                    </td>
                                    <td style="font-size:0.82rem; color:var(--text-muted);">
                                        <div><?= date('d/m/Y', strtotime($t['fecha_creacion'])) ?></div>
                                        <div style="font-size:0.75rem;"><?= date('H:i', strtotime($t['fecha_creacion'])) ?> hrs</div>
                                    </td>
                                    <td>
                                        <span class="hd-badge badge-pendiente">
                                            <i class="bi bi-hourglass-split me-1"></i> Pendiente
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <a href="<?= BASE_URL ?>/index.php?controller=Ticket&action=show&id=<?= $t['id'] ?>"
                                           class="btn btn-outline-primary btn-sm">
                                            <i class="bi bi-box-arrow-in-right"></i> Atender
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

<?php endif; ?>

<?php /* ─── Chart.js — solo para Coordinador (RF_14 y RF_15) ──────────── */
if ($rolId == 1):
    ob_start(); ?>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        Chart.defaults.color       = '#8b949e';
        Chart.defaults.borderColor = 'rgba(139,148,158,0.08)';
        Chart.defaults.font.family = "'Inter', system-ui, sans-serif";

        const dataAC        = <?= $chartData1 ?? '{"activos":0,"cerrados":0}' ?>;
        let   originalDataTU = <?= $chartData2 ?? '[]' ?>;

        // ── RF_14: Doughnut Activos vs Cerrados ──────────────────────────────
        const ctxE = document.getElementById('chartActivosCerrados');
        if (ctxE) {
            new Chart(ctxE.getContext('2d'), {
                type: 'doughnut',
                data: {
                    labels: ['En Gestión', 'Cerrados'],
                    datasets: [{
                        data: [dataAC.activos, dataAC.cerrados],
                        backgroundColor: ['#2f81f7', '#238636'],
                        borderColor: '#161b22',
                        borderWidth: 4,
                        hoverOffset: 6,
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '72%',
                    plugins: {
                        legend: { position: 'bottom', labels: { padding: 16 } },
                        tooltip: {
                            callbacks: {
                                label: ctx => ` ${ctx.parsed} ticket(s)`,
                            },
                        },
                    },
                },
            });
        }

        // ── RF_15: Barra Carga de Trabajo ────────────────────────────────────
        const ctxT   = document.getElementById('chartTicketsUsuario');
        const wrapper     = document.getElementById('chartWrapper');
        const emptyState  = document.getElementById('rf15EmptyState');

        if (ctxT) {
            // RF_15 Flujo alternativo: sin asignaciones
            const hayDatos = originalDataTU.some(item => item.total > 0);
            if (!hayDatos && originalDataTU.length > 0) {
                wrapper.style.display    = 'none';
                emptyState.style.display = 'block';
            }

            let chartTU = new Chart(ctxT.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: originalDataTU.map(i => i.tecnico),
                    datasets: [{
                        label: 'Tickets activos',
                        data: originalDataTU.map(i => i.total),
                        backgroundColor: originalDataTU.map(i =>
                            i.rol === 'Mesa de Ayuda' ? '#7c3aed' : '#2f81f7'
                        ),
                        hoverBackgroundColor: originalDataTU.map(i =>
                            i.rol === 'Mesa de Ayuda' ? '#9333ea' : '#388bfd'
                        ),
                        borderRadius: 6,
                        barPercentage: 0.5,
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                afterLabel: ctx => `Rol: ${originalDataTU[ctx.dataIndex]?.rol ?? '—'}`,
                            },
                        },
                    },
                    scales: {
                        y: { beginAtZero: true, ticks: { stepSize: 1 } },
                        x: { grid: { display: false } },
                    },
                },
            });

            // ── Filtrado en tiempo real ───────────────────────────────────────
            function updateChart() {
                const nameFilter = document.getElementById('filterName').value.toLowerCase();
                const roleFilter = document.getElementById('filterRole').value;

                const filtered = originalDataTU.filter(item => {
                    const matchName = item.tecnico.toLowerCase().includes(nameFilter);
                    const matchRole = roleFilter === '' || item.rol === roleFilter;
                    return matchName && matchRole;
                });

                // RF_15 alternativo: mostrar/ocultar empty state según resultado
                const sinCarga = filtered.every(i => i.total === 0);
                if (filtered.length === 0 || sinCarga) {
                    wrapper.style.display    = 'none';
                    emptyState.style.display = 'block';
                } else {
                    wrapper.style.display    = '';
                    emptyState.style.display = 'none';
                }

                chartTU.data.labels                    = filtered.map(i => i.tecnico);
                chartTU.data.datasets[0].data          = filtered.map(i => i.total);
                chartTU.data.datasets[0].backgroundColor = filtered.map(i =>
                    i.rol === 'Mesa de Ayuda' ? '#7c3aed' : '#2f81f7'
                );
                chartTU.data.datasets[0].hoverBackgroundColor = filtered.map(i =>
                    i.rol === 'Mesa de Ayuda' ? '#9333ea' : '#388bfd'
                );
                chartTU.options.plugins.tooltip.callbacks.afterLabel =
                    ctx => `Rol: ${filtered[ctx.dataIndex]?.rol ?? '—'}`;

                chartTU.update();
            }

            document.getElementById('filterName').addEventListener('input',  updateChart);
            document.getElementById('filterRole').addEventListener('change', updateChart);
        }
    });
    </script>
<?php
    $extraJs = ob_get_clean();
endif;
?>

<?php require BASE_PATH . '/app/views/layouts/footer.php'; ?>
