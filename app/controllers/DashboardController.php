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
        $totalTickets    = $this->modelTicket->contarTotal();
        $ticketsActivos  = $this->modelTicket->contarActivos();
        $ticketsCerrados = $this->modelTicket->contarCerrados();

        // ── Datos del coordinador (Rol 1) ──────────────────────────────
        $porTecnico  = [];
        $totalUsuarios = 0;

        if ($rolId == 1) {
            // Usamos el nuevo método que incluye a Mesa de Ayuda y roles con 0 tickets
            $porTecnico    = $this->modelTicket->contarCargaPorUsuario();
            $totalUsuarios = count($this->modelUsuario->obtenerTodos());
        }

        // ── Datos del técnico (Rol 2) ──────────────────────────────────
        $misTickets = [];
        if ($rolId == 2) {
            $misTickets = $this->modelTicket->obtenerTicketsPorTecnico($_SESSION['user_id']);
        }

        // ── Datos de Mesa de Ayuda (Rol 3) ─────────────────────────────
        $ultimosTickets    = [];
        $ticketsPendientes = [];
        $porValidar        = [];
        if ($rolId == 3) {
            // La Mesa de Ayuda (Rol 3) solo ve los últimos 15 "Sin asignar"
            $filtrosPendientes = ['id_estatus' => 1];
            $ticketsPendientes = $this->modelTicket->obtenerTodosLosTickets($filtrosPendientes);
            $ticketsPendientes = array_slice($ticketsPendientes, 0, 15);

            // Folios que este usuario de Mesa debe validar (estatus 5) → RF_10
            $porValidar = $this->modelTicket->obtenerTicketsPorValidar((int) $_SESSION['user_id']);
        }

        // ── PREPARAR DATOS PARA LAS GRÁFICAS (RF_14 y RF_15) ───────────
        $chartData1 = json_encode([
            'activos'  => $ticketsActivos, 
            'cerrados' => $ticketsCerrados
        ]);
        
        // Enviamos la carga de trabajo estructurada a JS
        $chartData2 = json_encode($porTecnico ?: []);

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
