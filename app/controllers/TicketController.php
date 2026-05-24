<?php
/**
 * Controlador: Ticket
 * Administra el ciclo de vida completo de los folios (Tickets)
 * Sistema de Mesa de Ayuda - Los Bélicos
 */
class TicketController {

    private Ticket $modelTicket;

    public function __construct() {
        $this->requireAuth();
        require_once BASE_PATH . '/app/models/Ticket.php';
        $this->modelTicket = new Ticket();
    }

    /**
     * Tablero general con listado de todos los tickets y filtros
     */
    public function index(): void {
        $filtros = [
            'folio'       => $_GET['folio'] ?? '',
            'solicitante' => $_GET['solicitante'] ?? '',
            'id_estatus'   => $_GET['id_estatus'] ?? ''
        ];

        $tickets = $this->modelTicket->obtenerTodosLosTickets($filtros);
        $estatusList = $this->modelTicket->obtenerEstatus();
        
        $pageTitle = 'Tablero General de Tickets';
        require BASE_PATH . '/app/views/tickets/index.php';
    }

    /**
     * Muestra el formulario de registro de un nuevo ticket (Llamada/WhatsApp)
     */
    public function create(): void {
        // Restricción: Solo Mesa de Ayuda (Rol 3) o Coordinador (Rol 1) pueden crear tickets
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
     * Procesa la inserción del ticket y su solicitante (Lógica transaccional 3FN)
     */
    public function store(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '/index.php?controller=Ticket&action=create');
            exit;
        }    elseif ($accion === 'enviar_validacion') {
            // RF_10: El técnico envía a mesa para validación
            $this->modelTicket->enviarAValidacion($id);
            $_SESSION['flash_success'] = "El ticket ha sido enviado a Mesa de Ayuda para su validación.";
        }

        // Recibir y sanitizar campos
        $claveReportante   = trim($_POST['clave_reportante'] ?? '');
        $nombreSolicitante = trim($_POST['nombre_solicitante'] ?? '');
        $correo            = trim($_POST['correo'] ?? '');
        $idDepartamento    = $_POST['id_departamento'] ?? '';
        $idCanal           = $_POST['id_canal'] ?? '';
        $prioridad         = $_POST['prioridad'] ?? 'media';
        $descripcion       = trim($_POST['descripcion'] ?? '');

        $errors = [];

        // Validaciones en Backend (RF_01 / RF_02)
        if (empty($claveReportante))   $errors[] = "La clave del reportante es obligatoria.";
        if (empty($nombreSolicitante)) $errors[] = "El nombre completo del solicitante es obligatorio.";
        if (empty($correo))            $errors[] = "El correo electrónico es obligatorio.";
        if (empty($idDepartamento))    $errors[] = "Debe seleccionar un departamento válido.";
        if (empty($idCanal))           $errors[] = "Debe especificar el canal de entrada (Llamada/WhatsApp).";
        if (empty($descripcion))       $errors[] = "La descripción detallada del problema es obligatoria.";

        if (!empty($errors)) {
            $departamentos = $this->modelTicket->obtenerDepartamentos();
            $canales = $this->modelTicket->obtenerCanales();
            require BASE_PATH . '/app/views/tickets/create.php';
            return;
        }

        try {
            // ─── PASO 1: Mantener la Integridad Referencial de Solicitantes (3FN) ───
            $solicitante = $this->modelTicket->buscarSolicitantePorClave($claveReportante);
            
            if ($solicitante) {
                // Si el solicitante ya existe, reutilizamos su ID primario
                $idSolicitante = (int)$solicitante['id'];
            } else {
                // Si es nuevo, lo registramos primero en la tabla independiente
                $idSolicitante = $this->modelTicket->crearSolicitante(
                    $claveReportante,
                    $nombreSolicitante,
                    $correo,
                    (int)$idDepartamento
                );
            }

            // ─── PASO 2: Generación Automática del Folio Único (RF_03) ───
            $folio = $this->modelTicket->generarFolioUnico();

            // ─── PASO 3: Registrar el Ticket de Soporte ───
            // El estatus inicial por defecto siempre es 1 (Abierto / Pendiente de asignación)
            $this->modelTicket->crearTicket(
                $folio,
                $idSolicitante,
                (int)$idCanal,
                $descripcion,
                $prioridad,
                1
            );

            // Guardar mensaje de éxito en la sesión
            $_SESSION['flash_success'] = "Ticket registrado exitosamente. Se asignó el Folio: <strong>{$folio}</strong>";
            
            // Redirigir al usuario al Panel Principal (Dashboard)
            header('Location: ' . BASE_URL . '/index.php?controller=Dashboard&action=index');
            exit;

        } catch (Exception $e) {
            $errors[] = "Error crítico al guardar en la Base de Datos: " . $e->getMessage();
            $departamentos = $this->modelTicket->obtenerDepartamentos();
            $canales = $this->modelTicket->obtenerCanales();
            require BASE_PATH . '/app/views/tickets/create.php';
        }
    }

    /**
     * Muestra el detalle completo de un ticket, notas internas y opciones de gestión (RF_10)
     */
   public function show(): void {
        $id = (int)($_GET['id'] ?? 0);
        $ticket = $this->modelTicket->obtenerTicketPorId($id);

        if (!$ticket) {
            require BASE_PATH . '/app/views/errors/404.php';
            return;
        }

        // Procesar acciones de seguimiento vía POST
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $accion = $_POST['accion'] ?? '';

            if ($accion === 'asignar' && isset($_POST['id_tecnico'])) {
                $idTecnico = (int)$_POST['id_tecnico'];
                $this->modelTicket->asignarTecnico($id, $idTecnico);
                $_SESSION['flash_success'] = "Técnico asignado correctamente al folio.";
            } 
            elseif ($accion === 'nota' && isset($_POST['nota'])) {
                $nota = trim($_POST['nota']);
                if (!empty($nota)) {
                    $this->modelTicket->agregarNota($id, $_SESSION['user_id'], $nota);
                    $_SESSION['flash_success'] = "Nota de seguimiento añadida con éxito.";
                }
            } 
            elseif ($accion === 'estatus' && isset($_POST['id_estatus'])) {
                $idEstatus = (int)$_POST['id_estatus'];
                $this->modelTicket->actualizarEstatus($id, $idEstatus);
                $_SESSION['flash_success'] = "El estatus del ticket ha sido actualizado.";
            }
            // CORRECCIÓN: Esta acción AHORA SÍ está dentro del IF del POST
            elseif ($accion === 'enviar_validacion' && isset($_POST['id_mesa'])) {
                $idMesa = (int)$_POST['id_mesa'];
                // Usamos el nuevo método de reasignarAMesa que creamos
                $this->modelTicket->reasignarAMesa($id, $idMesa);
                $_SESSION['flash_success'] = "El ticket ha sido enviado a Mesa de Ayuda para su validación.";
            }

            header('Location: ' . BASE_URL . '/index.php?controller=Ticket&action=show&id=' . $id);
            exit;
        }

        // Obtener historial y catálogos
        $notas = $this->modelTicket->obtenerNotasPorTicket($id);
        $tecnicos = $this->modelTicket->obtenerTecnicos();
        $personalMesa = $this->modelTicket->obtenerPersonalMesa(); // Obtenemos quiénes son mesa
        $estatusList = $this->modelTicket->obtenerEstatus();
        
        $pageTitle = "Seguimiento de Folio - " . $ticket['folio'];
        require BASE_PATH . '/app/views/tickets/show.php';
    }
    /**
     * Vista personalizada para los Técnicos: Muestra solo lo asignado a su ID (RF_06)
     */
    public function misTickets(): void {
        if ($_SESSION['rol_id'] != 2) {
            require BASE_PATH . '/app/views/errors/403.php';
            return;
        }

        $filtros = ['id_estatus' => $_GET['id_estatus'] ?? ''];
        $tickets = $this->modelTicket->obtenerTicketsPorTecnico($_SESSION['user_id'], $filtros);
        $estatusList = $this->modelTicket->obtenerEstatus();

        $pageTitle = 'Mis Folios Asignados';
        require BASE_PATH . '/app/views/tickets/mis_tickets.php';
    }

    /**
     * Endpoint AJAX para el autocompletado en vivo del Formulario "Nuevo Ticket"
     */
    public function buscarSolicitanteJson(): void {
        header('Content-Type: application/json');
        $clave = $_GET['clave'] ?? '';

        if (empty($clave)) {
            echo json_encode(['existe' => false]);
            exit;
        }

        $solicitante = $this->modelTicket->buscarSolicitantePorClave($clave);

        if ($solicitante) {
            echo json_encode([
                'existe'          => true,
                'nombre_completo' => $solicitante['nombre_completo'],
                'correo'          => $solicitante['correo'],
                'id_departamento' => $solicitante['id_departamento']
            ]);
        } else {
            echo json_encode(['existe' => false]);
        }
        exit;
    }

    /**
     * Interceptor de Seguridad: Valida la sesión activa del usuario
     */
    private function requireAuth(): void {
        if (!isset($_SESSION['user_id'])) {
            header('Location: ' . BASE_URL . '/index.php?controller=Auth&action=loginForm');
            exit;
        }
    }


}
