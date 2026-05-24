<?php
/**
 * Controlador: Dashboard
 * Vista de inicio diferenciada por rol.
 * 
 * RF_14 / RF_15 — Coordinador: gráficas
 * Técnico / Mesa: resumen de sus tickets
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
        $rolId = $_SESSION['rol_id'];

        // ── Datos comunes ──────────────────────────────────────────────
        $totalTickets  = $this->modelTicket->contarTotal();
        $ticketsActivos = $this->modelTicket->contarActivos();
        $ticketsCerrados = $this->modelTicket->contarCerrados();
        $porEstatus    = $this->modelTicket->contarPorEstatus();

        // ── Datos del coordinador ──────────────────────────────────────
        $porTecnico  = [];
        $totalUsuarios = 0;

        if ($rolId == 1) {
            $porTecnico   = $this->modelTicket->contarPorTecnico();
            $totalUsuarios = count($this->modelUsuario->obtenerTodos());
        }

        // ── Datos del técnico ──────────────────────────────────────────
        $misTickets = [];
        if ($rolId == 2) {
            $misTickets = $this->modelTicket->obtenerTicketsPorTecnico($_SESSION['user_id']);
        }

        // ── Últimos tickets (Mesa y Coordinador) ───────────────────────
        $ultimosTickets = [];
        if (in_array($rolId, [1, 3])) {
            $ultimosTickets = array_slice($this->modelTicket->obtenerTodosLosTickets(), 0, 5);
        }

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
