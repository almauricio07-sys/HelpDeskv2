<?php
/**
 * Controlador: Ticket
 * Maneja la creación, listado, asignación y gestión de tickets.
 * 
 * RF_01-RF_10 | Roles: Mesa de Ayuda (3), Técnico (2), Coordinador (1)
 * 
 * Sistema de Mesa de Ayuda - Los Bélicos
 */
class TicketController {

    private Ticket  $modelTicket;
    private Usuario $modelUsuario;

    public function __construct() {
        $this->requireAuth();
        require_once BASE_PATH . '/app/models/Ticket.php';
        require_once BASE_PATH . '/app/models/Usuario.php';
        $this->modelTicket  = new Ticket();
        $this->modelUsuario = new Usuario();
    }

    // ══════════════════════════════════════════════════════════════════════
    //  RF_04 / RF_05 — Tablero general de tickets (Mesa y Coordinador)
    // ══════════════════════════════════════════════════════════════════════
    public function index(): void {
        $this->requireRol([1, 3]);

        $filtros = [
            'folio'       => trim($_GET['folio']       ?? ''),
            'solicitante' => trim($_GET['solicitante']  ?? ''),
            'id_estatus'  => $_GET['id_estatus']        ?? '',
        ];

        $tickets   = $this->modelTicket->obtenerTodosLosTickets($filtros);
        $estatus   = $this->modelTicket->obtenerEstatus();
        $pageTitle = 'Todos los Tickets';

        require BASE_PATH . '/app/views/tickets/index.php';
    }

    // ══════════════════════════════════════════════════════════════════════
    //  RF_01 / RF_02 / RF_03 — Crear ticket (Mesa de Ayuda)
    // ══════════════════════════════════════════════════════════════════════
    public function create(): void {
        $this->requireRol([3]);

        $canales      = $this->modelTicket->obtenerCanales();
        $departamentos = $this->modelTicket->obtenerDepartamentos();
        $pageTitle    = 'Nuevo Ticket';
        $errors       = [];
        $success      = null;

        require BASE_PATH . '/app/views/tickets/create.php';
    }

    public function store(): void {
        $this->requireRol([3]);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '/index.php?controller=Ticket&action=create');
            exit;
        }

        // ─── Recoger y sanitizar datos ────────────────────────────────
        $claveReportante = trim($_POST['clave_reportante'] ?? '');
        $nombreSolicitante = trim($_POST['nombre_solicitante'] ?? '');
        $correo          = trim($_POST['correo']          ?? '');
        $idDepartamento  = (int) ($_POST['id_departamento'] ?? 0);
        $idCanal         = (int) ($_POST['id_canal']        ?? 0);
        $descripcion     = trim($_POST['descripcion']      ?? '');
        $prioridad       = $_POST['prioridad']             ?? 'media';

        // ─── Validaciones ─────────────────────────────────────────────
        $errors = [];

        if (empty($claveReportante))    $errors[] = 'La clave del reportante es requerida.';
        if (empty($nombreSolicitante))  $errors[] = 'El nombre del solicitante es requerido.';
        if (empty($correo) || !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'El correo es inválido.';
        }
        if ($idDepartamento <= 0)  $errors[] = 'Selecciona un departamento.';
        if ($idCanal <= 0)         $errors[] = 'Selecciona un canal de contacto.';
        if (empty($descripcion))   $errors[] = 'La descripción del problema es requerida.';
        if (!in_array($prioridad, ['alta', 'media', 'baja'])) $errors[] = 'Prioridad no válida.';

        if (!empty($errors)) {
            $canales       = $this->modelTicket->obtenerCanales();
            $departamentos = $this->modelTicket->obtenerDepartamentos();
            $pageTitle     = 'Nuevo Ticket';
            $success       = null;
            require BASE_PATH . '/app/views/tickets/create.php';
            return;
        }

        // ─── Insertar/obtener solicitante ─────────────────────────────
        $solicitante = $this->modelTicket->buscarSolicitantePorClave($claveReportante);

        if (!$solicitante) {
            $idSolicitante = $this->modelTicket->crearSolicitante(
                $claveReportante, $nombreSolicitante, $correo, $idDepartamento
            );
        } else {
            $idSolicitante = $solicitante['id'];
        }

        // ─── Generar folio y crear ticket ─────────────────────────────
        $folio    = $this->modelTicket->generarFolioUnico();
        $idTicket = $this->modelTicket->crearTicket($folio, $idSolicitante, $idCanal, $descripcion, $prioridad);

        $_SESSION['flash_success'] = "Ticket creado exitosamente con folio: <strong>{$folio}</strong>";
        header('Location: ' . BASE_URL . '/index.php?controller=Ticket&action=index');
        exit;
    }

    // ══════════════════════════════════════════════════════════════════════
    //  RF_06 — Mis folios asignados (Técnico)
    // ══════════════════════════════════════════════════════════════════════
    public function misTickets(): void {
        $this->requireRol([2]);

        $filtros = [
            'id_estatus' => $_GET['id_estatus'] ?? '',
        ];

        $tickets   = $this->modelTicket->obtenerTicketsPorTecnico($_SESSION['user_id'], $filtros);
        $estatus   = $this->modelTicket->obtenerEstatus();
        $pageTitle = 'Mis Folios Asignados';

        require BASE_PATH . '/app/views/tickets/mis_tickets.php';
    }

    // ══════════════════════════════════════════════════════════════════════
    //  RF_07 / RF_08 / RF_09 / RF_10 — Detalle del ticket
    // ══════════════════════════════════════════════════════════════════════
    public function show(): void {
        $this->requireRol([1, 2, 3]);

        $id     = (int) ($_GET['id'] ?? 0);
        $ticket = $this->modelTicket->obtenerTicketPorId($id);

        if (!$ticket) {
            http_response_code(404);
            require BASE_PATH . '/app/views/errors/404.php';
            return;
        }

        // Técnico solo puede ver sus propios tickets
        if ($_SESSION['rol_id'] == 2 && $ticket['id_tecnico'] != $_SESSION['user_id']) {
            http_response_code(403);
            require BASE_PATH . '/app/views/errors/403.php';
            return;
        }

        $notas    = $this->modelTicket->obtenerNotasPorTicket($id);
        $tecnicos = $this->modelTicket->obtenerTecnicos();
        $estatus  = $this->modelTicket->obtenerEstatus();
        $pageTitle = 'Detalle Ticket #' . htmlspecialchars($ticket['folio']);

        require BASE_PATH . '/app/views/tickets/show.php';
    }

    // ══════════════════════════════════════════════════════════════════════
    //  RF_07 — Asignar técnico (Mesa de Ayuda)
    // ══════════════════════════════════════════════════════════════════════
    public function asignar(): void {
        $this->requireRol([1, 3]);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '/index.php?controller=Ticket&action=index');
            exit;
        }

        $idTicket  = (int) ($_POST['id_ticket']  ?? 0);
        $idTecnico = (int) ($_POST['id_tecnico'] ?? 0);

        if ($idTicket > 0 && $idTecnico > 0) {
            $this->modelTicket->asignarTecnico($idTicket, $idTecnico);
            $_SESSION['flash_success'] = 'Técnico asignado correctamente.';
        } else {
            $_SESSION['flash_error'] = 'Datos inválidos para asignación.';
        }

        header('Location: ' . BASE_URL . '/index.php?controller=Ticket&action=show&id=' . $idTicket);
        exit;
    }

    // ══════════════════════════════════════════════════════════════════════
    //  RF_08 — Agregar nota interna (Técnico)
    // ══════════════════════════════════════════════════════════════════════
    public function agregarNota(): void {
        $this->requireRol([2]);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '/index.php?controller=Ticket&action=misTickets');
            exit;
        }

        $idTicket = (int) ($_POST['id_ticket'] ?? 0);
        $nota     = trim($_POST['nota'] ?? '');

        if ($idTicket > 0 && !empty($nota)) {
            $this->modelTicket->agregarNota($idTicket, $_SESSION['user_id'], $nota);
            $_SESSION['flash_success'] = 'Nota interna agregada.';
        } else {
            $_SESSION['flash_error'] = 'La nota no puede estar vacía.';
        }

        header('Location: ' . BASE_URL . '/index.php?controller=Ticket&action=show&id=' . $idTicket);
        exit;
    }

    // ══════════════════════════════════════════════════════════════════════
    //  RF_09 — Actualizar estatus (Técnico)
    // ══════════════════════════════════════════════════════════════════════
    public function actualizarEstatus(): void {
        $this->requireRol([2]);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '/index.php?controller=Ticket&action=misTickets');
            exit;
        }

        $idTicket  = (int) ($_POST['id_ticket']  ?? 0);
        $idEstatus = (int) ($_POST['id_estatus'] ?? 0);

        if ($idTicket > 0 && $idEstatus > 0) {
            // Verificar que el técnico sea dueño del ticket
            $ticket = $this->modelTicket->obtenerTicketPorId($idTicket);
            if ($ticket && $ticket['id_tecnico'] == $_SESSION['user_id']) {
                $this->modelTicket->actualizarEstatus($idTicket, $idEstatus);
                $_SESSION['flash_success'] = 'Estatus actualizado correctamente.';
            } else {
                $_SESSION['flash_error'] = 'No tienes permiso para modificar este ticket.';
            }
        }

        header('Location: ' . BASE_URL . '/index.php?controller=Ticket&action=show&id=' . $idTicket);
        exit;
    }

    // ══════════════════════════════════════════════════════════════════════
    //  RF_10 — Cerrar ticket (Mesa de Ayuda)
    // ══════════════════════════════════════════════════════════════════════
    public function cerrar(): void {
        $this->requireRol([1, 3]);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '/index.php?controller=Ticket&action=index');
            exit;
        }

        $idTicket = (int) ($_POST['id_ticket'] ?? 0);

        if ($idTicket > 0) {
            $this->modelTicket->cerrarTicket($idTicket);
            $_SESSION['flash_success'] = 'Ticket cerrado exitosamente.';
        }

        header('Location: ' . BASE_URL . '/index.php?controller=Ticket&action=show&id=' . $idTicket);
        exit;
    }

    // ──────────────────────────────────────────────────────────────────────
    //  Helpers de autorización
    // ──────────────────────────────────────────────────────────────────────

    private function requireAuth(): void {
        if (!isset($_SESSION['user_id'])) {
            header('Location: ' . BASE_URL . '/index.php?controller=Auth&action=loginForm');
            exit;
        }
    }

    private function requireRol(array $rolesPermitidos): void {
        if (!in_array($_SESSION['rol_id'], $rolesPermitidos)) {
            http_response_code(403);
            require BASE_PATH . '/app/views/errors/403.php';
            exit;
        }
    }
}
