<?php
/**
 * Controlador: Ticket
 * Administra el ciclo de vida completo de los folios.
 *
 * RF_01: Registro de solicitudes.
 * RF_04: Visualización general.
 * RF_05: Filtros dinámicos.
 * RF_06: Mis folios asignados (Técnico).
 * RF_07: Asignación de técnico (Mesa/Coordinador).
 * RF_08: Notas internas / Comentarios Técnicos.
 * RF_09: Actualización de estatus + reasignación automática a Mesa.
 * RF_10: Cierre definitivo (Mesa/Coordinador).
 *
 * Roles:   1=Coordinador | 2=Soporte Técnico | 3=Mesa de Ayuda
 * Estatus: 1=Pendiente de Asignación | 2=En Proceso | 3=Terminado
 *          4=Pendiente de Validación  | 5=Cerrado
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
        $buscar    = trim($_GET['buscar']    ?? '');
        $idEstatus = (int) ($_GET['id_estatus'] ?? 0);

        $tickets   = $this->modelTicket->obtenerTodosLosTickets($buscar, $idEstatus);
        $estatus   = $this->modelTicket->obtenerEstatus();
        $pageTitle = 'Tablero General de Tickets';

        require BASE_PATH . '/app/views/tickets/index.php';
    }

    // ══════════════════════════════════════════════════════════════════════
    //  RF_06 — Mis Folios Asignados (Técnico)
    //
    //  La consulta filtra estrictamente por id_tecnico = id del usuario en
    //  sesión, garantizando que cada técnico solo vea sus propios tickets.
    // ══════════════════════════════════════════════════════════════════════

    public function misTickets(): void {
        $idTecnico = (int) ($_SESSION['user_id'] ?? 0);
        
        // Filtros del buscador (si aplican)
        $filtros   = ['id_estatus' => $_GET['id_estatus'] ?? ''];
        
        // 1. Obtenemos los tickets (para la tabla)
        $tickets   = $this->modelTicket->obtenerTicketsPorTecnico($idTecnico, $filtros);
        
        // 2. Obtenemos los estatus (para el select del filtro)
        $estatus   = $this->modelTicket->obtenerEstatus();
        
        // 3. NUEVO: Obtenemos las estadísticas reales (para las 4 tarjetas superiores)
        $stats     = $this->modelTicket->obtenerEstadisticasTecnico($idTecnico);
        
        $pageTitle = 'Mis Folios Asignados';

        // 4. Inyectamos la variable $stats hacia la vista
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
    //  RF_01 — Formulario de creación (Mesa/Coordinador)
    // ══════════════════════════════════════════════════════════════════════

    public function create(): void {
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
    //  RF_01 y RF_03 — Almacenamiento
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
        $prioridad         = ucfirst(strtolower($_POST['prioridad'] ?? 'Media'));
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

        if (!in_array($prioridad, ['Alta', 'Media', 'Baja'], true)) {
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
    //  RF_07, RF_08, RF_09, RF_10 — Detalle del ticket
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
     * El usuario que asigna queda como responsable de validación (id_mesa_asignada).
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
     * RF_08: Agregar comentario técnico (nota interna) al ticket.
     * Visible solo para el equipo interno (roles 1, 2, 3).
     * La nota queda vinculada al id_usuario de la sesión activa.
     */
    public function agregarNota(): void {
        // 1. Forzar a que la respuesta siempre sea JSON para que SweetAlert2 la entienda
        header('Content-Type: application/json');

        try {
            $idTicket  = (int) ($_POST['id_ticket'] ?? 0);
            $nota      = trim($_POST['nota'] ?? '');
            $idUsuario = (int) ($_SESSION['user_id'] ?? 0);

            if ($idTicket <= 0 || $idUsuario <= 0) {
                echo json_encode(['success' => false, 'message' => 'Faltan datos de sesión o ticket.']);
                exit;
            }

            if ($nota === '') {
                echo json_encode(['success' => false, 'message' => 'El comentario no puede estar vacío.']);
                exit;
            }

            // 3. Insertar a través del modelo
            $resultado = $this->modelTicket->agregarNota($idTicket, (int) $idUsuario, $nota);

            if ($resultado) {
                echo json_encode(['success' => true, 'message' => 'Comentario técnico publicado exitosamente.']);
            } else {
                throw new \Exception('No se pudo guardar el comentario en la base de datos.');
            }
            exit;

        } catch (\PDOException $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error BD: ' . $e->getMessage()]);
            exit;
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error Sistema: ' . $e->getMessage()]);
            exit;
        }
    }

    /**
     * RF_09: Actualizar el estatus del ticket.
     *
     * Reglas de negocio:
     *   - Soporte Técnico (2): "En Proceso" (2) o "Terminado" (3).
     *       Al marcar "Terminado":
     *         a) RF_09 Flujo Alt. 4a: bloquear si no hay técnico asignado.
     *         b) Reasignación automática: si id_mesa_asignada es NULL, se
     *            asigna al primer usuario de Mesa activo (con COALESCE).
     *         c) Transacción BEGIN/COMMIT para garantizar atomicidad.
     *   - Mesa de Ayuda (3): solo "En Proceso" (2).
     *       El cierre definitivo usa la acción cerrar() (RF_10).
     */
    public function actualizarEstatus(): void {
    // 1. Forzar a que la respuesta siempre sea JSON para evitar errores 500 feos
    header('Content-Type: application/json');

    try {
        $idTicket  = (int) ($_POST['id_ticket'] ?? 0);
        $idEstatus = (int) ($_POST['id_estatus'] ?? 0);
        $rol       = (int) ($_SESSION['rol_id'] ?? 0);

        if ($idTicket <= 0 || $idEstatus <= 0) {
            echo json_encode(['success' => false, 'message' => 'Faltan datos obligatorios.']);
            exit;
        }

        // 3. Validar permisos: Técnico(2) puede 2 o 3. Mesa(3) puede 2 o 4 (Cerrar).
        $permitidos = ($rol === 2) ? [2, 3] : [2, 4];

        if (!in_array($idEstatus, $permitidos, true)) {
            echo json_encode(['success' => false, 'message' => 'El estatus seleccionado no está permitido para tu rol.']);
            exit;
        }

        // ── RF_09: Técnico marca "Terminado" (id=3) ──────────────────────
        if ($rol === 2 && $idEstatus === 3) {
            $ticketActual = $this->modelTicket->obtenerTicketPorId($idTicket);

            // Flujo alternativo 4a: sin técnico asignado, bloquear cambio (RF_07)
            if (empty($ticketActual['id_tecnico'])) {
                echo json_encode(['success' => false, 'message' => 'Error: Debe asignar un especialista antes de cambiar el estado.']);
                exit;
            }

            // Iniciar Transacción SQL
            $this->modelTicket->beginTransaction();

            // Reasignación automática: obtener Mesa de fallback si id_mesa_asignada es NULL
            $idMesaFallback = empty($ticketActual['id_mesa_asignada'])
                ? $this->modelTicket->obtenerPrimerMesaActiva() 
                : $ticketActual['id_mesa_asignada'];

            // Ejecutar envío a validación
            $ok = $this->modelTicket->enviarAValidacion($idTicket, $idMesaFallback);
            
            if (!$ok) {
                throw new \Exception('La actualización en la base de datos falló.');
            }

            $this->modelTicket->commit();
            
            echo json_encode([
                'success' => true, 
                'message' => 'Ticket marcado como Terminado y devuelto a Mesa de Ayuda.'
            ]);
            exit;

        // ── Cambio de estatus normal (Ej: "En Proceso") ────────────
        } else {
            $ok = $this->modelTicket->actualizarEstatus($idTicket, $idEstatus);
            
            if ($ok) {
                echo json_encode(['success' => true, 'message' => 'Estatus actualizado correctamente.']);
            } else {
                throw new \Exception('No se pudo actualizar el estatus.');
            }
            exit;
        }

        // 4. ATrapar los errores para que no colapse el servidor (Adiós Error 500)
        } catch (\PDOException $e) {
            if ($this->modelTicket->inTransaction()) {
                $this->modelTicket->rollBack();
            }
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error BD: ' . $e->getMessage()]);
            exit;

        } catch (\Exception $e) {
            if ($this->modelTicket->inTransaction()) {
                $this->modelTicket->rollBack();
            }
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error Sistema: ' . $e->getMessage()]);
            exit;
        }
    }

    /**
     * RF_10: Cierre definitivo. Exclusivo de Mesa de Ayuda (3) y Coordinador (1).
     *
     * Transacción: marca "Cerrado" (id=5) + registra nota automática de cierre.
     */
    public function cerrar(): void {
        $idTicket = $this->requirePostTicket([1, 3]);
        $idAutor  = (int) $_SESSION['user_id'];

        try {
            $this->modelTicket->beginTransaction();

            $this->modelTicket->actualizarEstatus($idTicket, 5);
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

    /**
     * RF_10 Flujo Alt. 2a: Mesa/Coordinador rechaza el cierre y devuelve el ticket a "En Proceso".
     * La razón queda registrada automáticamente como nota interna (RF_08).
     */
    public function rechazarValidacion(): void {
        $idTicket = $this->requirePostTicket([1, 3]);
        $razon    = trim($_POST['razon_rechazo'] ?? '');
        $idAutor  = (int) $_SESSION['user_id'];

        if ($razon === '') {
            $_SESSION['flash_error'] = 'Debes ingresar la razón del rechazo para continuar.';
            $this->redirectToShow($idTicket);
            return;
        }

        try {
            $this->modelTicket->rechazarValidacion($idTicket, $idAutor, $razon);
            $_SESSION['flash_success'] = 'Folio devuelto a <strong>En Proceso</strong>. La razón quedó registrada como nota interna.';
        } catch (\Exception $e) {
            error_log('Error al rechazar validación: ' . $e->getMessage());
            $_SESSION['flash_error'] = 'No se pudo procesar el rechazo. Intenta nuevamente.';
        }
        $this->redirectToShow($idTicket);
    }

    // ══════════════════════════════════════════════════════════════════════
    //  API — Autocompletar solicitante (AJAX / RF_02)
    // ══════════════════════════════════════════════════════════════════════

    public function buscarSolicitanteJson(): void {
        header('Content-Type: application/json; charset=utf-8');

        $clave = trim($_GET['clave'] ?? '');
        if ($clave === '') {
            echo json_encode(['existe' => false]);
            return;
        }

        try {
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
        } catch (\PDOException $e) {
            http_response_code(500);
            echo json_encode(['existe' => false]);
        }
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
