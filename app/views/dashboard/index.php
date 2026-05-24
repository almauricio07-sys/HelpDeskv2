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
        <div class="col-md-5 fade-in-up delay-1">
            <div class="hd-card h-100">
                <div class="hd-card-header">
                    <h2 class="hd-card-title">
                        <i class="bi bi-pie-chart-fill text-accent"></i>
                        Activos vs Cerrados
                    </h2>
                </div>
                <div class="hd-card-body chart-container" style="min-height:260px; display:flex; align-items:center; justify-content:center;">
                    <canvas id="chartActivosCerrados" style="max-height:240px;"></canvas>
                </div>
            </div>
        </div>

        <div class="col-md-7 fade-in-up delay-2">
            <div class="hd-card h-100">
                <div class="hd-card-header">
                    <h2 class="hd-card-title">
                        <i class="bi bi-bar-chart-fill text-accent"></i>
                        Tickets por Técnico
                    </h2>
                </div>
                <div class="hd-card-body chart-container" style="min-height:260px;">
                    <canvas id="chartTicketsUsuario" style="max-height:240px;"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="hd-card fade-in-up">
        <div class="hd-card-header">
            <h2 class="hd-card-title">
                <i class="bi bi-clock-history text-accent"></i>
                Últimos Tickets Registrados
            </h2>
            <a href="<?= BASE_URL ?>/index.php?controller=Ticket&action=index" class="btn btn-outline-primary btn-sm">
                <i class="bi bi-list-ul"></i> Ver todos
            </a>
        </div>
        <div class="hd-card-body p-0">
            <?php if (empty($ultimosTickets)): ?>
                <div class="empty-state">
                    <i class="bi bi-ticket-perforated d-block"></i>
                    <p>No hay tickets registrados aún.</p>
                </div>
            <?php else: ?>
                <div class="hd-table-wrapper" style="border:none; border-radius:0;">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Folio</th>
                                <th>Solicitante</th>
                                <th>Prioridad</th>
                                <th>Estatus</th>
                                <th>Técnico</th>
                                <th>Fecha</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($ultimosTickets as $t): ?>
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
                                    <td><?= $t['tecnico'] ? htmlspecialchars($t['tecnico']) : '<span class="text-muted-hd">Sin asignar</span>' ?></td>
                                    <td style="font-size:0.78rem; color:var(--text-muted);">
                                        <?= date('d/m/Y H:i', strtotime($t['fecha_creacion'])) ?>
                                    </td>
                                    <td>
                                        <div class="d-flex gap-2">
                                            <a href="<?= BASE_URL ?>/index.php?controller=Ticket&action=show&id=<?= $t['id'] ?>"
                                               class="btn btn-action-edit btn-sm" title="Ver / Editar">
                                                <i class="bi bi-pencil-fill"></i>
                                            </a>
                                            <button type="button" class="btn btn-action-delete btn-sm" title="Eliminar">
                                                <i class="bi bi-trash-fill"></i>
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
    </div>

    <div class="hd-card fade-in-up">
        <div class="hd-card-header">
            <h2 class="hd-card-title">
                <i class="bi bi-clock-history text-accent"></i>
                Últimos Tickets Registrados
            </h2>
        </div>
        <div class="hd-card-body p-0">
            <?php if (empty($ultimosTickets)): ?>
                <div class="empty-state">
                    <i class="bi bi-ticket-perforated d-block"></i>
                    <p>No hay tickets registrados aún.</p>
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
                            <?php foreach ($ultimosTickets as $t): ?>
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
                                           class="btn btn-action-edit btn-sm" title="Ver / Editar">
                                            <i class="bi bi-pencil-fill"></i>
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
    // Utilizamos ob_start() para inyectar correctamente PHP en el JS sin que se rompa el layout
    ob_start();
?>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Configuraciones globales para el Midnight Slate Dark Mode
        Chart.defaults.color = '#8b949e'; 
        Chart.defaults.borderColor = 'rgba(139, 148, 158, 0.1)';
        Chart.defaults.font.family = "'Inter', system-ui, -apple-system, sans-serif";

        // ─── Variables inyectadas desde PHP (Controlador) ───
        const dataAC = <?= $chartData1 ?? '{"activos":0,"cerrados":0}' ?>;
        const dataTU = <?= $chartData2 ?? '[]' ?>;

        // ─── 1. Gráfica RF_14: Activos vs Cerrados (Doughnut) ───
        const ctxE = document.getElementById('chartActivosCerrados');
        if (ctxE) {
            new Chart(ctxE.getContext('2d'), {
                type: 'doughnut',
                data: {
                    labels: ['Activos', 'Cerrados'],
                    datasets: [{
                        data: [dataAC.activos, dataAC.cerrados],
                        backgroundColor: ['#d29922', '#238636'], // Ocre/Amarillo y Verde Mate
                        borderColor: '#161b22', // Color de la tarjeta de fondo (Midnight Slate)
                        borderWidth: 3,
                        hoverOffset: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '70%',
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: { padding: 16, font: { size: 12 } }
                        },
                        tooltip: {
                            callbacks: {
                                label: ctx => ` ${ctx.label}: ${ctx.parsed} tickets`
                            }
                        }
                    }
                }
            });
        }

        // ─── 2. Gráfica RF_15: Tickets por Técnico (Bar) ───
        const nombresTecnicos = dataTU.map(item => item.tecnico);
        const totalesTecnicos = dataTU.map(item => item.total);

        const ctxT = document.getElementById('chartTicketsUsuario');
        if (ctxT) {
            new Chart(ctxT.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: nombresTecnicos.length > 0 ? nombresTecnicos : ['Sin asignar'],
                    datasets: [{
                        label: 'Tickets Asignados',
                        data: totalesTecnicos.length > 0 ? totalesTecnicos : [0],
                        backgroundColor: '#2f81f7', // Azul Accent de nuestro CSS
                        borderRadius: 4,
                        barPercentage: 0.6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { 
                        legend: { display: false } 
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: { stepSize: 1 }
                        },
                        x: {
                            grid: { display: false }
                        }
                    }
                }
            });
        }
    });
    </script>
<?php 
    // Capturamos el script y lo asignamos a extraJs para que el footer lo imprima abajo del todo
    $extraJs = ob_get_clean(); 
endif; 
?>

<?php require BASE_PATH . '/app/views/layouts/footer.php'; ?>
