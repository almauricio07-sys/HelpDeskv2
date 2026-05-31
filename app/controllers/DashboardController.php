<?php
/**
 * Controlador: Dashboard
 * Vista de inicio diferenciada por rol.
 *
 * RF_14 — Gráfica Activos vs Cerrados (Coordinador)
 * RF_15 — Carga de trabajo por usuario (Coordinador)
 *
 * Roles: 1=Coordinador | 2=Soporte Técnico | 3=Mesa de Ayuda
 *
 * Sistema de Mesa de Ayuda - Los Bélicos
 */
class DashboardController {

    private Ticket  $modelTicket;
    private Usuario $modelUsuario;

    public function __construct() {
        $this->requireAuth();
        require_once BASE_PATH . '/app/models/Ticket.php';
        require_once BASE_PATH . '/app/models/Usuario.php';
        $this->modelTicket  = new Ticket();
        $this->modelUsuario = new Usuario();
    }

    public function index(): void {
        $rolId = (int) $_SESSION['rol_id'];

        // ── Contadores comunes ─────────────────────────────────────────
        $totalTickets    = $this->modelTicket->contarTotal();
        $ticketsActivos  = $this->modelTicket->contarActivos();
        $ticketsCerrados = $this->modelTicket->contarCerrados();

        // ── Coordinador (Rol 1): estadísticas completas para RF_14/RF_15 ─
        $porTecnico      = [];
        $statsPorEstatus = [];
        $totalUsuarios   = 0;

        if ($rolId === 1) {
            $porTecnico      = $this->modelTicket->contarCargaPorUsuario();
            $statsPorEstatus = $this->modelTicket->contarPorEstatus();
            $totalUsuarios   = count($this->modelUsuario->obtenerTodos(['estado' => 'Activo']));
        }

        // ── Soporte Técnico (Rol 2): sus tickets asignados + estadísticas ─
        $misTickets = [];
        $stats      = ['total_asignados' => 0, 'por_hacer' => 0, 'en_validacion' => 0, 'cerrados' => 0];
        if ($rolId === 2) {
            $idTecnico  = (int) $_SESSION['user_id'];
            $misTickets = $this->modelTicket->obtenerTicketsPorTecnico($idTecnico);
            $stats      = $this->modelTicket->obtenerEstadisticasTecnico($idTecnico);
        }

        // ── Mesa de Ayuda (Rol 3): tickets pendientes + por validar ─────
        $ticketsPendientes = [];
        $porValidar        = [];
        if ($rolId === 3) {
            $ticketsPendientes = array_slice(
                $this->modelTicket->obtenerTodosLosTickets('', 1),
                0, 15
            );
            $porValidar = $this->modelTicket->obtenerTicketsPorValidar((int) $_SESSION['user_id']);
        }

        // ── Datos serializados para Chart.js ──────────────────────────
        // RF_14: Activos vs Cerrados
        $chartData1 = json_encode([
            'activos'  => $ticketsActivos,
            'cerrados' => $ticketsCerrados,
        ]);
        // RF_15: Carga de trabajo por usuario
        $chartData2 = json_encode($porTecnico ?: []);
        // Desglose completo por estatus (tabla en dashboard)
        $chartData3 = json_encode($statsPorEstatus ?: []);

        $pageTitle = 'Dashboard';
        require BASE_PATH . '/app/views/dashboard/index.php';
    }

    private function requireAuth(): void {
        if (!isset($_SESSION['user_id'])) {
            header('Location: ' . BASE_URL . '/index.php?controller=Auth&action=loginForm');
            exit;
        }
    }
}
