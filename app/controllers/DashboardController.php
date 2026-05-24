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

// ── Últimos tickets (Diferenciado por Rol) ───────────────────────
        $ultimosTickets = [];
        if ($rolId == 1) {
            // El Coordinador (Rol 1) ve los últimos 5 tickets generales del sistema
            $ultimosTickets = array_slice($this->modelTicket->obtenerTodosLosTickets(), 0, 5);
        } elseif ($rolId == 3) {
            // La Mesa de Ayuda (Rol 3) solo ve los últimos 5 que están "Sin asignar"
            $todosLosTickets = $this->modelTicket->obtenerTodosLosTickets();
            
            // Filtramos el array buscando los que no tienen un técnico asignado
            $ticketsSinAsignar = array_filter($todosLosTickets, function($t) {
                return empty($t['tecnico']); 
            });
            
            // Tomamos los primeros 5 de esa lista filtrada
            $ultimosTickets = array_slice($ticketsSinAsignar, 0, 5);
        }

        // ── PREPARAR DATOS PARA LAS GRÁFICAS (RF_14 y RF_15) ───────────
        // Esto es necesario para que el código Javascript (Chart.js) en la vista pueda leerlos
        $chartData1 = json_encode([
            'activos' => $ticketsActivos, 
            'cerrados' => $ticketsCerrados
        ]);
        
        // Si no hay datos de técnicos (porque no es coordinador), enviamos un array vacío
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
