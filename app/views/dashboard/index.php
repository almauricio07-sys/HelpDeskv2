<?php
/**
 * Vista: Dashboard
 * Panel principal diferenciado por rol.
 * Roles: 1=Coordinador | 2=Técnico | 3=Mesa de Ayuda
 */

// Helper para obtener clase de badge según estatus
function badgeEstatus(string $estatus): string {
    return match (strtolower($estatus)) {
        'abierto'    => 'badge-abierto',
        'en proceso' => 'badge-proceso',
        'cerrado'    => 'badge-cerrado',
        default      => 'badge-pendiente',
    };
}

function badgePrioridad(string $prioridad): string {
    return match (strtolower($prioridad)) {
        'alta'  => 'badge-alta',
        'media' => 'badge-media',
        'baja'  => 'badge-baja',
        default => 'badge-pendiente',
    };
}

require BASE_PATH . '/app/views/layouts/header.php';

// Flash messages
$flashSuccess = $_SESSION['flash_success'] ?? null;
$flashError   = $_SESSION['flash_error']   ?? null;
unset($_SESSION['flash_success'], $_SESSION['flash_error']);
?>

<div class="hd-breadcrumb">
    <i class="bi bi-house-fill"></i>
    <span>Dashboard</span>
</div>

<?php if ($flashSuccess): ?>
    <div class="alert alert-success alert-dismissible d-flex align-items-center gap-2 mb-4 fade-in-up" role="alert">
        <i class="bi bi-check-circle-fill"></i>
        <span><?= $flashSuccess ?></span>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>
<?php if ($flashError): ?>
    <div class="alert alert-danger alert-dismissible d-flex align-items-center gap-2 mb-4 fade-in-up" role="alert">
        <i class="bi bi-exclamation-circle-fill"></i>
        <span><?= htmlspecialchars($flashError) ?></span>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="mb-4 fade-in-up">
    <h1 class="h4 mb-1">
        <?php
        $hora = (int) date('H');
        $saludo = $hora < 12 ? 'Buenos días' : ($hora < 19 ? 'Buenas tardes' : 'Buenas noches');
        echo $saludo . ', <span style="color:var(--accent)">' . htmlspecialchars($_SESSION['nombre']) . '</span> 👋';
        ?>
    </h1>
    <p class="mb-0" style="font-size:0.85rem;">
        <?= date('l, j \d\e F \d\e Y', strtotime('today')) ?> &mdash;
        <span class="role-badge"><?= htmlspecialchars($_SESSION['rol_nombre']) ?></span>
    </p>
</div>

<?php /* ═══════════════════════════════════════════════════════════════════
        COORDINADOR (Rol 1): Dashboard con estadísticas y gráficas
       ═══════════════════════════════════════════════════════════════════ */
if ($rolId == 1): ?>

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
                    <div class="stat-label">Tickets Activos</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3 fade-in-up delay-3">
            <div class="stat-card">
                <div class="stat-icon green"><i class="bi bi-check-circle-fill"></i></div>
                <div>
                    <div class="stat-value"><?= $ticketsCerrados ?></div>
                    <div class="stat-label">Tickets Cerrados</div>
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

    <div class="row g-3 mb-4">
        <div class="col-md-4 fade-in-up delay-1">
            <div class="hd-card h-100">
                <div class="hd-card-header">
                    <h2 class="hd-card-title">
                        <i class="bi bi-pie-chart-fill text-accent"></i>
                        Proporción Global
                    </h2>
                </div>
                <div class="hd-card-body chart-container d-flex align-items-center justify-content-center" style="min-height:300px;">
                    <canvas id="chartActivosCerrados" style="max-height:260px;"></canvas>
                </div>
            </div>
        </div>

        <div class="col-md-8 fade-in-up delay-2">
            <div class="hd-card h-100">
                <div class="hd-card-header d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                    <h2 class="hd-card-title mb-0">
                        <i class="bi bi-bar-chart-line-fill text-accent"></i>
                        Carga de Trabajo por Usuario
                    </h2>
                    <div class="d-flex flex-wrap gap-2">
                        <input type="text" id="filterName" class="form-control form-control-sm" placeholder="🔍 Buscar nombre..." style="min-width: 160px; max-width: 200px;">
                        <select id="filterRole" class="form-select form-select-sm" style="min-width: 140px; max-width: 180px;">
                            <option value="">Todos los roles</option>
                            <option value="Técnico">Equipo Soporte (Técnicos)</option>
                            <option value="Mesa de Ayuda">Mesa de Ayuda</option>
                            <option value="General">Sin Asignar</option>
                        </select>
                    </div>
                </div>
                <div class="hd-card-body chart-container" style="min-height:300px;">
                    <canvas id="chartTicketsUsuario" style="max-height:260px;"></canvas>
                </div>
            </div>
        </div>
    </div>

    <?php /* ═══════════════════════════════════════════════════════════════════ */ ?>

<?php /* ═══════════════════════════════════════════════════════════════════
        TÉCNICO (Rol 2): Sus tickets asignados
       ═══════════════════════════════════════════════════════════════════ */
elseif ($rolId == 2): ?>

    <div class="row g-3 mb-4">
        <?php
        $miTotal   = count($misTickets);
        
        // ─── MAGIA AQUÍ: Filtramos solo los tickets que NO están cerrados ───
        $misTicketsActivos = array_filter($misTickets, fn($t) => strtolower($t['estatus']) !== 'cerrado');
        
        $miActivos = count($misTicketsActivos);
        $miCerrad  = $miTotal - $miActivos;
        ?>
        <div class="col-6 col-md-4 fade-in-up delay-1">
            <div class="stat-card">
                <div class="stat-icon blue"><i class="bi bi-ticket-perforated-fill"></i></div>
                <div>
                    <div class="stat-value"><?= $miTotal ?></div>
                    <div class="stat-label">Histórico Total</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 fade-in-up delay-2">
            <div class="stat-card">
                <div class="stat-icon amber"><i class="bi bi-clock-history"></i></div>
                <div>
                    <div class="stat-value"><?= $miActivos ?></div>
                    <div class="stat-label">Por Atender</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 fade-in-up delay-3">
            <div class="stat-card">
                <div class="stat-icon green"><i class="bi bi-check-circle-fill"></i></div>
                <div>
                    <div class="stat-value"><?= $miCerrad ?></div>
                    <div class="stat-label">Cerrados</div>
                </div>
            </div>
        </div>
    </div>

    <div class="hd-card fade-in-up">
        <div class="hd-card-header">
            <h2 class="hd-card-title">
                <i class="bi bi-person-lines-fill text-accent"></i>
                Mis Folios Pendientes de Atención
            </h2>
            <a href="<?= BASE_URL ?>/index.php?controller=Ticket&action=misTickets" class="btn btn-outline-primary btn-sm">
                <i class="bi bi-list-ul"></i> Ver histórico
            </a>
        </div>
        <div class="hd-card-body p-0">
            <?php if (empty($misTicketsActivos)): ?>
                <div class="empty-state">
                    <i class="bi bi-emoji-smile d-block text-success mb-2" style="font-size: 2rem;"></i>
                    <p>¡Excelente! No tienes tickets pendientes de atención en este momento.</p>
                </div>
            <?php else: ?>
                <div class="hd-table-wrapper" style="border:none; border-radius:0;">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Folio</th>
                                <th>Solicitante</th>
                                <th>Prioridad</th>
                                <th>Estatus</th>
                                <th>Fecha</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (array_slice($misTicketsActivos, 0, 6) as $t): ?>
                                <tr>
                                    <td><span class="folio-tag"><?= htmlspecialchars($t['folio']) ?></span></td>
                                    <td><?= htmlspecialchars($t['solicitante']) ?></td>
                                    <td>
                                        <span class="hd-badge <?= badgePrioridad($t['prioridad']) ?>">
                                            <?= ucfirst($t['prioridad']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="hd-badge <?= badgeEstatus($t['estatus']) ?>">
                                            <?= htmlspecialchars($t['estatus']) ?>
                                        </span>
                                    </td>
                                    <td style="font-size:0.78rem; color:var(--text-muted);">
                                        <?= date('d/m/Y H:i', strtotime($t['fecha_creacion'])) ?>
                                    </td>
                                    <td>
                                        <a href="<?= BASE_URL ?>/index.php?controller=Ticket&action=show&id=<?= $t['id'] ?>"
                                           class="btn btn-action-edit btn-sm" title="Atender">
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

<?php /* ═══════════════════════════════════════════════════════════════════
        MESA DE AYUDA (Rol 3): Resumen y acciones rápidas
       ═══════════════════════════════════════════════════════════════════ */
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
                    <div class="stat-label">Activos</div>
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
               class="hd-card d-flex align-items-center gap-3 p-3 text-decoration-none" style="cursor:pointer;">
                <div class="stat-icon blue flex-shrink-0"><i class="bi bi-plus-circle-fill"></i></div>
                <div>
                    <div class="fw-600" style="color:var(--text-primary); font-weight:600;">Registrar Nuevo Ticket</div>
                    <div style="font-size:0.8rem; color:var(--text-muted);">Captura un nuevo caso de soporte</div>
                </div>
                <i class="bi bi-chevron-right ms-auto text-muted-hd"></i>
            </a>
        </div>
        <div class="col-12 col-md-6">
            <a href="<?= BASE_URL ?>/index.php?controller=Ticket&action=index"
               class="hd-card d-flex align-items-center gap-3 p-3 text-decoration-none" style="cursor:pointer;">
                <div class="stat-icon amber flex-shrink-0"><i class="bi bi-list-ul"></i></div>
                <div>
                    <div class="fw-600" style="color:var(--text-primary); font-weight:600;">Ver Todos los Tickets</div>
                    <div style="font-size:0.8rem; color:var(--text-muted);">Consulta y filtra el tablero general</div>
                </div>
                <i class="bi bi-chevron-right ms-auto text-muted-hd"></i>
            </a>
        </div>
        <div class="col-12">
            <a href="<?= BASE_URL ?>/index.php?controller=Ticket&action=porValidar"
               class="hd-card d-flex align-items-center gap-3 p-3 text-decoration-none" style="cursor:pointer;">
                <div class="stat-icon green flex-shrink-0"><i class="bi bi-clipboard-check"></i></div>
                <div>
                    <div class="fw-600" style="color:var(--text-primary); font-weight:600;">Folios por Validar</div>
                    <div style="font-size:0.8rem; color:var(--text-muted);">Folios terminados por el técnico, en espera de tu validación y cierre</div>
                </div>
                <span class="hd-badge <?= count($porValidar) > 0 ? 'badge-pendiente' : 'badge-cerrado' ?> ms-auto">
                    <?= count($porValidar) ?> pendiente(s)
                </span>
            </a>
        </div>
    </div>

    <div class="hd-card fade-in-up delay-2 mt-4">
        <div class="hd-card-header d-flex justify-content-between align-items-center">
            <h2 class="hd-card-title">
                <i class="bi bi-clock-history text-warning"></i> 
                Pendiente de asignación
            </h2>
            <span class="hd-badge badge-pendiente">Requiere atención</span>
        </div>
        <div class="hd-card-body p-0">
            <?php if (empty($ticketsPendientes)): ?>
                <div class="empty-state py-4">
                    <i class="bi bi-check2-circle text-success d-block mb-2" style="font-size: 2rem;"></i>
                    <p class="mb-0 text-muted">No hay tickets pendientes de asignación en este momento.</p>
                </div>
            <?php else: ?>
                <div class="hd-table-wrapper" style="border:none; border-radius:0;">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th scope="col" style="width: 25%;">Folio / Problema</th>
                                <th scope="col">Solicitante</th>
                                <th scope="col">Fecha de Creación</th>
                                <th scope="col">Estatus</th>
                                <th scope="col" class="text-end">Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($ticketsPendientes as $t): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex flex-column gap-1">
                                            <span class="folio-tag d-inline-block" style="width: fit-content;">
                                                <?= htmlspecialchars($t['folio']) ?>
                                            </span>
                                            <!-- Aquí cambiamos 'text-muted' por 'text-white' -->
                                            <div class="text-truncate text-white" style="max-width: 250px; font-size: 0.82rem;" title="<?= htmlspecialchars($t['descripcion']) ?>">
                                                <?= htmlspecialchars($t['descripcion']) ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="fw-semibold text-primary-hd">
                                            <?= htmlspecialchars($t['solicitante']) ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex flex-column text-muted" style="font-size: 0.85rem;">
                                            <span><i class="bi bi-calendar3 me-1"></i><?= date('d/m/Y', strtotime($t['fecha_creacion'])) ?></span>
                                            <span style="font-size: 0.75rem;"><i class="bi bi-clock me-1"></i><?= date('H:i', strtotime($t['fecha_creacion'])) ?> hrs</span>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="hd-badge badge-pendiente">
                                            <i class="bi bi-hourglass-split me-1"></i> Pendiente de asignación
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <a href="<?= BASE_URL ?>/index.php?controller=Ticket&action=show&id=<?= $t['id'] ?>" class="btn btn-outline-primary btn-sm">
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

<?php /* ─── Chart.js solo para Coordinador ─────────────────────────────────── */
if ($rolId == 1): 
    ob_start();
?>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        Chart.defaults.color = '#8b949e'; 
        Chart.defaults.borderColor = 'rgba(139, 148, 158, 0.08)';
        Chart.defaults.font.family = "'Inter', system-ui, -apple-system, sans-serif";

        const dataAC = <?= $chartData1 ?? '{"activos":0,"cerrados":0}' ?>;
        let originalDataTU = <?= $chartData2 ?? '[]' ?>; // Almacenamos el dataset íntegro

        // 1. Gráfica Doughnut (Activos vs Cerrados)
        const ctxE = document.getElementById('chartActivosCerrados');
        if (ctxE) {
            new Chart(ctxE.getContext('2d'), {
                type: 'doughnut',
                data: {
                    labels: ['Activos', 'Cerrados'],
                    datasets: [{
                        data: [dataAC.activos, dataAC.cerrados],
                        backgroundColor: ['#2f81f7', '#238636'], 
                        borderColor: '#161b22', 
                        borderWidth: 4,
                        hoverOffset: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '75%',
                    plugins: { legend: { position: 'bottom', labels: { padding: 20 } } }
                }
            });
        }

        // 2. Gráfica Bar (Carga de Trabajo) con instanciación dinámica
        const ctxT = document.getElementById('chartTicketsUsuario');
        if (ctxT) {
            let chartTU = new Chart(ctxT.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: originalDataTU.map(item => item.tecnico),
                    datasets: [{
                        label: 'Tickets Activos',
                        data: originalDataTU.map(item => item.total),
                        backgroundColor: '#2f81f7',
                        hoverBackgroundColor: '#388bfd',
                        borderRadius: 6,
                        barPercentage: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { 
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                // Muestra el rol en el hover de la barra
                                afterLabel: (ctx) => `Rol: ${originalDataTU[ctx.dataIndex].rol}`
                            }
                        }
                    },
                    scales: {
                        y: { beginAtZero: true, ticks: { stepSize: 1 } },
                        x: { grid: { display: false } }
                    }
                }
            });

            // 3. Lógica de Filtrado en Tiempo Real (Buscador y Selector)
            function updateChart() {
                const nameFilter = document.getElementById('filterName').value.toLowerCase();
                const roleFilter = document.getElementById('filterRole').value;

                // Filtramos el arreglo original
                const filteredData = originalDataTU.filter(item => {
                    const matchName = item.tecnico.toLowerCase().includes(nameFilter);
                    const matchRole = roleFilter === '' || item.rol === roleFilter;
                    return matchName && matchRole;
                });

                // Actualizamos los datos del objeto Chart y re-dibujamos
                chartTU.data.labels = filteredData.map(i => i.tecnico);
                chartTU.data.datasets[0].data = filteredData.map(i => i.total);
                // Actualizar tooltip reference
                chartTU.options.plugins.tooltip.callbacks.afterLabel = (ctx) => `Rol: ${filteredData[ctx.dataIndex].rol}`;
                
                chartTU.update();
            }

            // Escuchadores de eventos
            document.getElementById('filterName').addEventListener('input', updateChart);
            document.getElementById('filterRole').addEventListener('change', updateChart);
        }
    });
    </script>
<?php 
    $extraJs = ob_get_clean(); 
endif; 
?>
<?php require BASE_PATH . '/app/views/layouts/footer.php'; ?>
