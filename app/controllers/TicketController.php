<?php
/**
 * Controlador: Ticket
 * Administra el ciclo de vida completo de los folios (Tickets)
 * * Funcionalidades cubiertas:
 * RF_01: Registro de solicitudes.
 * RF_04: Visualización general.
 * RF_05: Filtros dinámicos.
 * RF_06: Mis folios asignados.
 * RF_07: Asignación de técnico.
 * RF_08: Notas internas.
 * RF_09: Actualización de estatus.
 * RF_10: Finalización de ticket.
 */
class TicketController {

    private Ticket $modelTicket;

    public function __construct() {
        $this->requireAuth();
        require_once BASE_PATH . '/app/models/Ticket.php';
        $this->modelTicket = new Ticket();
    }

    /**
     * RF_04 y RF_05: Tablero general con filtros
     */
    public function index(): void {
        $filtros = [
            'folio'       => $_GET['folio'] ?? '',
            'solicitante' => $_GET['solicitante'] ?? '',
            'id_estatus'  => $_GET['id_estatus'] ?? ''
        ];

        $tickets = $this->modelTicket->obtenerTodosLosTickets($filtros);
        $estatus = $this->modelTicket->obtenerEstatus();
        
        $pageTitle = 'Tablero General de Tickets';
        require BASE_PATH . '/app/views/tickets/index.php';
    }

    /**
     * RF_06: Visualización de folios específicos para el técnico
     */
    public function misTickets(): void {
        $idTecnico = (int)$_SESSION['user_id'];
        $filtros = ['id_estatus' => $_GET['id_estatus'] ?? ''];
        $tickets = $this->modelTicket->obtenerTicketsPorTecnico($idTecnico, $filtros);
        $estatus = $this->modelTicket->obtenerEstatus();

        $pageTitle = 'Mis Folios Asignados';
        require BASE_PATH . '/app/views/tickets/mis_tickets.php';
    }

    /**
     * Panel de Validación (RF_10) — exclusivo de Mesa de Ayuda (Rol 3).
     * Lista los folios asignados a este usuario de Mesa que están en estatus 5
     * (Pendiente de Validación), listos para revisar y cerrar.
     */
    public function porValidar(): void {
        if ((int) $_SESSION['rol_id'] !== 3) {
            http_response_code(403);
            require BASE_PATH . '/app/views/errors/403.php';
            return;
        }

        $idMesa  = (int) $_SESSION['user_id'];
        $tickets = $this->modelTicket->obtenerTicketsPorValidar($idMesa);

        $pageTitle = 'Folios por Validar';
        require BASE_PATH . '/app/views/tickets/por_validar.php';
    }

    /**
     * RF_01: Formulario de creación
     */
    public function create(): void {
        // Solo pueden registrar mesa de ayuda (3) o coordinadores (1)
        if (!in_array($_SESSION['rol_id'], [1, 3])) {
            require BASE_PATH . '/app/views/errors/403.php';
            return;
        }

        $departamentos = $this->modelTicket->obtenerDepartamentos();
        $canales = $this->modelTicket->obtenerCanales();
        $pageTitle = 'Registrar Nuevo Ticket';
        require BASE_PATH . '/app/views/tickets/create.php';
    }

    /**
     * RF_01 y RF_03: Almacenamiento inteligente de ticket y solicitante
     */
    public function store(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '/index.php?controller=Ticket&action=create');
            exit;
        }

        if (!in_array($_SESSION['rol_id'], [1, 3])) {
            require BASE_PATH . '/app/views/errors/403.php';
            return;
        }

        $claveReportante   = trim($_POST['clave_reportante'] ?? '');
        $nombreSolicitante = trim($_POST['nombre_solicitante'] ?? '');
        $correo            = trim($_POST['correo'] ?? '');
        $idDepartamento    = (int) ($_POST['id_departamento'] ?? 0);
        $idCanal           = (int) ($_POST['id_canal'] ?? 0);
        $prioridad         = $_POST['prioridad'] ?? 'media';
        $descripcion       = trim($_POST['descripcion'] ?? '');

        // ─── Validación de entrada ─────────────────────────────────────────────
        if ($claveReportante === '' || $nombreSolicitante === '' || $correo === ''
            || $descripcion === '' || $idDepartamento === 0 || $idCanal === 0) {
            $_SESSION['flash_error'] = "Por favor, completa todos los campos obligatorios.";
            header('Location: ' . BASE_URL . '/index.php?controller=Ticket&action=create');
            exit;
        }

        if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['flash_error'] = "El correo electrónico no tiene un formato válido.";
            header('Location: ' . BASE_URL . '/index.php?controller=Ticket&action=create');
            exit;
        }

        if (!in_array($prioridad, ['alta', 'media', 'baja'], true)) {
            $prioridad = 'media';
        }

        // ─── Upsert del solicitante + alta del ticket (atómico) ────────────────
        // Si algo falla a mitad, el rollback evita solicitantes huérfanos.
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
            $_SESSION['flash_error'] = "No se pudo registrar el ticket. Intenta nuevamente.";
            header('Location: ' . BASE_URL . '/index.php?controller=Ticket&action=create');
            exit;
        }
    }

    /**
     * RF_07, RF_08, RF_09, RF_10: Detalle y gestión técnica
     */
    public function show(): void {
        $id = (int)($_GET['id'] ?? 0);
        $ticket = $this->modelTicket->obtenerTicketPorId($id);

        if (!$ticket) {
            require BASE_PATH . '/app/views/errors/404.php';
            return;
        }

        $notas = $this->modelTicket->obtenerNotasPorTicket($id);
        $tecnicos = $this->modelTicket->obtenerTecnicos();
        $personalMesa = $this->modelTicket->obtenerPersonalMesa();
        $estatus = $this->modelTicket->obtenerEstatus();
        
        $pageTitle = "Seguimiento de Folio - " . $ticket['folio'];
        require BASE_PATH . '/app/views/tickets/show.php';
    }

    /**
     * RF_07: Asignar (o reasignar) un técnico al ticket.
     * Permitido a Mesa de Ayuda (3) y Coordinador (1).
     */
    public function asignar(): void {
        $idTicket = $this->requirePostTicket([1, 3]);
        $idTecnico = (int) ($_POST['id_tecnico'] ?? 0);
        // Registramos a quien asigna como el usuario de Mesa a validar al cierre.
        $idMesa = (int) $_SESSION['user_id'];

        if ($idTecnico > 0 && $this->modelTicket->asignarTecnico($idTicket, $idTecnico, $idMesa)) {
            $_SESSION['flash_success'] = "Técnico asignado correctamente.";
        } else {
            $_SESSION['flash_error'] = "Debes seleccionar un técnico válido.";
        }
        $this->redirectToShow($idTicket);
    }

    /**
     * RF_08: Agregar una nota interna al ticket.
     * Permitido a Coordinador (1), Técnico (2) y Mesa de Ayuda (3).
     */
    public function agregarNota(): void {
        $idTicket = $this->requirePostTicket([1, 2, 3]);
        $nota = trim($_POST['nota'] ?? '');

        if ($nota === '') {
            $_SESSION['flash_error'] = "La nota no puede estar vacía.";
        } elseif ($this->modelTicket->agregarNota($idTicket, (int) $_SESSION['user_id'], $nota)) {
            $_SESSION['flash_success'] = "Nota interna publicada.";
        } else {
            $_SESSION['flash_error'] = "No se pudo guardar la nota.";
        }
        $this->redirectToShow($idTicket);
    }

    /**
     * RF_09 / RF_10: Actualizar el estatus del ticket.
     *
     * Reglas de negocio (validadas en el servidor, no se confía en el cliente):
     *   - Técnico (2): puede elegir "En Proceso" (2) o "Terminado" (5).
     *       "Terminado" NO cierra el ticket: lo envía a "Pendiente de Validación"
     *       (estatus 5) y queda a cargo de Mesa de Ayuda para su cierre.
     *   - Mesa de Ayuda (3): solo puede elegir "En Proceso" (2). El cierre
     *       definitivo se hace con la acción cerrar() (RF_10).
     */
    public function actualizarEstatus(): void {
        $idTicket  = $this->requirePostTicket([2, 3]);
        $idEstatus = (int) ($_POST['id_estatus'] ?? 0);
        $rol       = (int) $_SESSION['rol_id'];

        // Estatus permitidos por rol.
        $permitidos = ($rol === 2) ? [2, 5] : [2];
        if (!in_array($idEstatus, $permitidos, true)) {
            $_SESSION['flash_error'] = "El estatus seleccionado no está permitido para tu rol.";
            $this->redirectToShow($idTicket);
        }

        // Técnico marca "Terminado" → envía a validación de Mesa (estatus 5),
        // sin cerrar el ticket.
        if ($rol === 2 && $idEstatus === 5) {
            $ok = $this->modelTicket->enviarAValidacion($idTicket);
            $_SESSION[$ok ? 'flash_success' : 'flash_error'] = $ok
                ? "Ticket marcado como Terminado y enviado a Mesa de Ayuda para validación."
                : "No se pudo enviar el ticket a validación.";
        } else {
            $ok = $this->modelTicket->actualizarEstatus($idTicket, $idEstatus);
            $_SESSION[$ok ? 'flash_success' : 'flash_error'] = $ok
                ? "Estatus actualizado."
                : "No se pudo actualizar el estatus.";
        }
        $this->redirectToShow($idTicket);
    }

    /**
     * RF_10: Cierre definitivo del ticket. Acción EXCLUSIVA de Mesa de Ayuda (3)
     * y Coordinador (1).
     *
     * Transacción (orquestada aquí, en el controlador): marca estatus 3 +
     * fecha_cierre e inserta la nota automática "Validado". Si cualquiera de las
     * dos operaciones falla, se revierte todo (rollBack) para no dejar el ticket
     * cerrado sin su nota de validación.
     */
    public function cerrar(): void {
        $idTicket = $this->requirePostTicket([1, 3]);
        $idAutor  = (int) $_SESSION['user_id'];

        try {
            $this->modelTicket->beginTransaction();

            $this->modelTicket->actualizarEstatus($idTicket, 3); // estatus 3 + fecha_cierre
            $this->modelTicket->agregarNota($idTicket, $idAutor, 'Validado');

            $this->modelTicket->commit();
            $_SESSION['flash_success'] = "Ticket cerrado y validado correctamente.";

        } catch (Exception $e) {
            if ($this->modelTicket->inTransaction()) {
                $this->modelTicket->rollBack();
            }
            error_log('Error al cerrar ticket: ' . $e->getMessage());
            $_SESSION['flash_error'] = "No se pudo cerrar el ticket.";
        }
        $this->redirectToShow($idTicket);
    }

    /**
     * API auxiliar para autocompletar datos del solicitante (AJAX / RF_02).
     *
     * Contrato JSON estable para el front:
     *   - Encontrado: { "existe": true, "nombre_completo", "correo", "id_departamento" }
     *   - No existe : { "existe": false }
     */
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

    private function requireAuth(): void {
        if (!isset($_SESSION['user_id'])) {
            header('Location: ' . BASE_URL . '/index.php?controller=Auth&action=loginForm');
            exit;
        }
    }

    /**
     * Guard común para las acciones de gestión del ticket:
     *   1. Exige método POST.
     *   2. Verifica que el rol del usuario esté autorizado.
     *   3. Devuelve el id_ticket saneado.
     *
     * @param int[] $rolesPermitidos
     * @return int  id del ticket recibido por POST.
     */
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

    /**
     * Redirige de vuelta al detalle del ticket (patrón PRG: Post-Redirect-Get).
     */
    private function redirectToShow(int $idTicket): void {
        header('Location: ' . BASE_URL . '/index.php?controller=Ticket&action=show&id=' . $idTicket);
        exit;
    }
}