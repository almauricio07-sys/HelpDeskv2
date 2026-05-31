<?php
/**
 * Controlador: Ticket
 * Administra el ciclo de vida completo de los folios.
 *
 * RF_01: Registro de solicitudes.
 * RF_04: Visualización general.
 * RF_05: Filtros dinámicos.
 * RF_06: Mis folios asignados.
 * RF_07: Asignación de técnico.
 * RF_08: Notas internas.
 * RF_09: Actualización de estatus.
 * RF_10: Finalización/cierre de ticket.
 *
 * Roles (helpdesk_db): 1=Coordinador | 2=Soporte Técnico | 3=Mesa de Ayuda
 * Estatus:  1=Pendiente de Asignación | 2=En Proceso | 3=Terminado
 *           4=Pendiente de Validación | 5=Cerrado
 */
class TicketController {

    private Ticket $modelTicket;

    public function __construct() {
        $this->requireAuth();
        require_once BASE_PATH . '/app/models/Ticket.php';
        $this->modelTicket = new Ticket();
    }

    // ══════════════════════════════════════════════════════════════════════
    //  RF_04 y RF_05 — Tablero general con filtros
    // ══════════════════════════════════════════════════════════════════════

    public function index(): void {
        $filtros = [
            'folio'      => $_GET['folio']      ?? '',
            'id_estatus' => $_GET['id_estatus'] ?? '',
        ];

        $tickets   = $this->modelTicket->obtenerTodosLosTickets($filtros);
        $estatus   = $this->modelTicket->obtenerEstatus();
        $pageTitle = 'Tablero General de Tickets';

        require BASE_PATH . '/app/views/tickets/index.php';
    }

    // ══════════════════════════════════════════════════════════════════════
    //  RF_06 — Mis folios (Técnico)
    // ══════════════════════════════════════════════════════════════════════

    public function misTickets(): void {
        $idTecnico = (int) $_SESSION['user_id'];
        $filtros   = ['id_estatus' => $_GET['id_estatus'] ?? ''];
        $tickets   = $this->modelTicket->obtenerTicketsPorTecnico($idTecnico, $filtros);
        $estatus   = $this->modelTicket->obtenerEstatus();

        $pageTitle = 'Mis Folios Asignados';
        require BASE_PATH . '/app/views/tickets/mis_tickets.php';
    }

    // ══════════════════════════════════════════════════════════════════════
    //  RF_10 — Panel de Validación (exclusivo Mesa de Ayuda, Rol 3)
    // ══════════════════════════════════════════════════════════════════════

    public function porValidar(): void {
        if ((int) $_SESSION['rol_id'] !== 3) {
            http_response_code(403);
            require BASE_PATH . '/app/views/errors/403.php';
            return;
        }

        $idMesa    = (int) $_SESSION['user_id'];
        $tickets   = $this->modelTicket->obtenerTicketsPorValidar($idMesa);
        $pageTitle = 'Folios por Validar';

        require BASE_PATH . '/app/views/tickets/por_validar.php';
    }

    // ══════════════════════════════════════════════════════════════════════
    //  RF_01 — Formulario de creación
    // ══════════════════════════════════════════════════════════════════════

    public function create(): void {
        // Mesa de Ayuda (3) y Coordinador (1) pueden registrar tickets
        if (!in_array($_SESSION['rol_id'], [1, 3])) {
            http_response_code(403);
            require BASE_PATH . '/app/views/errors/403.php';
            return;
        }

        $departamentos = $this->modelTicket->obtenerDepartamentos();
        $canales       = $this->modelTicket->obtenerCanales();
        $pageTitle     = 'Registrar Nuevo Ticket';

        require BASE_PATH . '/app/views/tickets/create.php';
    }

    // ══════════════════════════════════════════════════════════════════════
    //  RF_01 y RF_03 — Almacenamiento (Upsert solicitante + ticket)
    // ══════════════════════════════════════════════════════════════════════

    public function store(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '/index.php?controller=Ticket&action=create');
            exit;
        }

        if (!in_array($_SESSION['rol_id'], [1, 3])) {
            http_response_code(403);
            require BASE_PATH . '/app/views/errors/403.php';
            return;
        }

        $claveReportante   = trim($_POST['clave_reportante']   ?? '');
        $nombreSolicitante = trim($_POST['nombre_solicitante'] ?? '');
        $correo            = trim($_POST['correo']             ?? '');
        $idDepartamento    = (int) ($_POST['id_departamento']  ?? 0);
        $idCanal           = (int) ($_POST['id_canal']         ?? 0);
        $prioridad         = $_POST['prioridad']               ?? 'Media';
        $descripcion       = trim($_POST['descripcion']        ?? '');

        if ($claveReportante === '' || $nombreSolicitante === '' || $correo === ''
            || $descripcion === '' || $idDepartamento === 0 || $idCanal === 0) {
            $_SESSION['flash_error'] = 'Por favor, completa todos los campos obligatorios.';
            header('Location: ' . BASE_URL . '/index.php?controller=Ticket&action=create');
            exit;
        }

        if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['flash_error'] = 'El correo electrónico no tiene un formato válido.';
            header('Location: ' . BASE_URL . '/index.php?controller=Ticket&action=create');
            exit;
        }

        if (!in_array(ucfirst(strtolower($prioridad)), ['Alta', 'Media', 'Baja'], true)) {
            $prioridad = 'Media';
        }

        try {
            $folio = $this->modelTicket->registrarTicketConSolicitante(
                $claveReportante, $nombreSolicitante, $correo, $idDepartamento,
                $idCanal, $descripcion, $prioridad
            );
            $_SESSION['flash_success'] = "Ticket registrado exitosamente. Folio: <strong>{$folio}</strong>";
            header('Location: ' . BASE_URL . '/index.php?controller=Dashboard&action=index');
            exit;

        } catch (Exception $e) {
            error_log('Error al registrar ticket: ' . $e->getMessage());
            $_SESSION['flash_error'] = 'No se pudo registrar el ticket. Intenta nuevamente.';
            header('Location: ' . BASE_URL . '/index.php?controller=Ticket&action=create');
            exit;
        }
    }

    // ══════════════════════════════════════════════════════════════════════
    //  RF_07, RF_08, RF_09, RF_10 — Detalle y gestión
    // ══════════════════════════════════════════════════════════════════════

    public function show(): void {
        $id     = (int) ($_GET['id'] ?? 0);
        $ticket = $this->modelTicket->obtenerTicketPorId($id);

        if (!$ticket) {
            require BASE_PATH . '/app/views/errors/404.php';
            return;
        }

        $notas        = $this->modelTicket->obtenerNotasPorTicket($id);
        $tecnicos     = $this->modelTicket->obtenerTecnicos();
        $personalMesa = $this->modelTicket->obtenerPersonalMesa();
        $estatus      = $this->modelTicket->obtenerEstatus();
        $pageTitle    = 'Seguimiento de Folio — ' . $ticket['folio'];

        require BASE_PATH . '/app/views/tickets/show.php';
    }

    /**
     * RF_07: Asignar (o reasignar) un técnico.
     * Permitido a Mesa de Ayuda (3) y Coordinador (1).
     */
    public function asignar(): void {
        $idTicket  = $this->requirePostTicket([1, 3]);
        $idTecnico = (int) ($_POST['id_tecnico'] ?? 0);
        $idMesa    = (int) $_SESSION['user_id'];

        if ($idTecnico > 0 && $this->modelTicket->asignarTecnico($idTicket, $idTecnico, $idMesa)) {
            $_SESSION['flash_success'] = 'Técnico asignado correctamente.';
        } else {
            $_SESSION['flash_error'] = 'Debes seleccionar un técnico válido.';
        }
        $this->redirectToShow($idTicket);
    }

    /**
     * RF_08: Agregar nota interna.
     * Permitido a todos los roles.
     */
    public function agregarNota(): void {
        $idTicket = $this->requirePostTicket([1, 2, 3]);
        $nota     = trim($_POST['nota'] ?? '');

        if ($nota === '') {
            $_SESSION['flash_error'] = 'La nota no puede estar vacía.';
        } elseif ($this->modelTicket->agregarNota($idTicket, (int) $_SESSION['user_id'], $nota)) {
            $_SESSION['flash_success'] = 'Nota interna publicada.';
        } else {
            $_SESSION['flash_error'] = 'No se pudo guardar la nota.';
        }
        $this->redirectToShow($idTicket);
    }

    /**
     * RF_09 / RF_10: Actualizar el estatus del ticket.
     *
     * Reglas de negocio:
     *   - Soporte Técnico (2): puede marcar "En Proceso" (2) o "Terminado" (3).
     *       Al marcar "Terminado" NO cierra el ticket: lo envía a
     *       "Pendiente de Validación" (id=4) para que Mesa de Ayuda lo revise.
     *   - Mesa de Ayuda (3): solo puede marcar "En Proceso" (2).
     *       El cierre definitivo se hace con la acción cerrar() (RF_10).
     */
    public function actualizarEstatus(): void {
        $idTicket  = $this->requirePostTicket([2, 3]);
        $idEstatus = (int) ($_POST['id_estatus'] ?? 0);
        $rol       = (int) $_SESSION['rol_id'];

        // Soporte Técnico puede elegir En Proceso (2) o Terminado (3)
        // Mesa de Ayuda solo puede elegir En Proceso (2)
        $permitidos = ($rol === 2) ? [2, 3] : [2];

        if (!in_array($idEstatus, $permitidos, true)) {
            $_SESSION['flash_error'] = 'El estatus seleccionado no está permitido para tu rol.';
            $this->redirectToShow($idTicket);
            return;
        }

        // Técnico marca "Terminado" (id=3) → envía a Pendiente de Validación (id=4)
        if ($rol === 2 && $idEstatus === 3) {
            $ok = $this->modelTicket->enviarAValidacion($idTicket);
            $_SESSION[$ok ? 'flash_success' : 'flash_error'] = $ok
                ? 'Ticket marcado como Terminado y enviado a Mesa de Ayuda para validación.'
                : 'No se pudo enviar el ticket a validación.';
        } else {
            $ok = $this->modelTicket->actualizarEstatus($idTicket, $idEstatus);
            $_SESSION[$ok ? 'flash_success' : 'flash_error'] = $ok
                ? 'Estatus actualizado.'
                : 'No se pudo actualizar el estatus.';
        }
        $this->redirectToShow($idTicket);
    }

    /**
     * RF_10: Cierre definitivo. Exclusivo de Mesa de Ayuda (3) y Coordinador (1).
     *
     * Transacción: marca "Cerrado" (id=5) + registra nota automática "Validado".
     * Si cualquier operación falla, se revierte todo para no dejar inconsistencias.
     */
    public function cerrar(): void {
        $idTicket = $this->requirePostTicket([1, 3]);
        $idAutor  = (int) $_SESSION['user_id'];

        try {
            $this->modelTicket->beginTransaction();

            $this->modelTicket->actualizarEstatus($idTicket, 5); // Cerrado (id=5)
            $this->modelTicket->agregarNota($idTicket, $idAutor, 'Ticket validado y cerrado por Mesa de Ayuda.');

            $this->modelTicket->commit();
            $_SESSION['flash_success'] = 'Ticket cerrado y validado correctamente.';

        } catch (Exception $e) {
            if ($this->modelTicket->inTransaction()) {
                $this->modelTicket->rollBack();
            }
            error_log('Error al cerrar ticket: ' . $e->getMessage());
            $_SESSION['flash_error'] = 'No se pudo cerrar el ticket.';
        }
        $this->redirectToShow($idTicket);
    }

    // ══════════════════════════════════════════════════════════════════════
    //  API auxiliar — Autocompletar solicitante (AJAX / RF_02)
    // ══════════════════════════════════════════════════════════════════════

    public function buscarSolicitanteJson(): void {
        header('Content-Type: application/json; charset=utf-8');

        $clave = trim($_GET['clave'] ?? '');
        if ($clave === '') {
            echo json_encode(['existe' => false]);
            return;
        }

        $solicitante = $this->modelTicket->buscarSolicitantePorClave($clave);
        if (!$solicitante) {
            echo json_encode(['existe' => false]);
            return;
        }

        echo json_encode([
            'existe'          => true,
            'nombre_completo' => $solicitante['nombre_completo'],
            'correo'          => $solicitante['correo'],
            'id_departamento' => (int) $solicitante['id_departamento'],
        ]);
    }

    // ──────────────────────────────────────────────────────────────────────
    //  Helpers
    // ──────────────────────────────────────────────────────────────────────

    private function requireAuth(): void {
        if (!isset($_SESSION['user_id'])) {
            header('Location: ' . BASE_URL . '/index.php?controller=Auth&action=loginForm');
            exit;
        }
    }

    private function requirePostTicket(array $rolesPermitidos): int {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '/index.php?controller=Ticket&action=index');
            exit;
        }
        if (!in_array($_SESSION['rol_id'], $rolesPermitidos)) {
            http_response_code(403);
            require BASE_PATH . '/app/views/errors/403.php';
            exit;
        }
        return (int) ($_POST['id_ticket'] ?? 0);
    }

    private function redirectToShow(int $idTicket): void {
        header('Location: ' . BASE_URL . '/index.php?controller=Ticket&action=show&id=' . $idTicket);
        exit;
    }
}
