<?php
/**
 * Modelo: Ticket
 * 
 * Gestiona todas las operaciones de base de datos relacionadas con tickets,
 * solicitantes, notas internas y estatus.
 * 
 * Sistema de Mesa de Ayuda - Los Bélicos
 */
class Ticket {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    // ══════════════════════════════════════════════════════════════════════
    //  SOLICITANTES
    // ══════════════════════════════════════════════════════════════════════

    /**
     * Busca un solicitante por clave_reportante.
     * Retorna el registro o false si no existe.
     */
    public function buscarSolicitantePorClave(string $clave): array|false {
        $stmt = $this->db->prepare(
            "SELECT * FROM solicitantes WHERE clave_reportante = ? LIMIT 1"
        );
        $stmt->execute([$clave]);
        return $stmt->fetch();
    }

    /**
     * Busca un solicitante por correo.
     */
    public function buscarSolicitantePorCorreo(string $correo): array|false {
        $stmt = $this->db->prepare(
            "SELECT * FROM solicitantes WHERE correo = ? LIMIT 1"
        );
        $stmt->execute([$correo]);
        return $stmt->fetch();
    }

    /**
     * Inserta un nuevo solicitante.
     * Retorna el ID insertado.
     */
    public function crearSolicitante(
        string $claveReportante,
        string $nombreCompleto,
        string $correo,
        int    $idDepartamento
    ): int {
        $stmt = $this->db->prepare(
            "INSERT INTO solicitantes (clave_reportante, nombre_completo, correo, id_departamento)
             VALUES (?, ?, ?, ?)"
        );
        $stmt->execute([$claveReportante, $nombreCompleto, $correo, $idDepartamento]);
        return (int) $this->db->lastInsertId();
    }

    // ══════════════════════════════════════════════════════════════════════
    //  TICKETS
    // ══════════════════════════════════════════════════════════════════════

    /**
     * Genera un folio único con formato: HD-YYYYMMDD-XXXX
     * Se busca el último folio del día para incrementar el consecutivo.
     */
    public function generarFolioUnico(): string {
        $fecha   = date('Ymd');
        $prefijo = "HD-{$fecha}-";

        $stmt = $this->db->prepare(
            "SELECT folio FROM tickets
             WHERE folio LIKE ?
             ORDER BY id DESC LIMIT 1"
        );
        $stmt->execute([$prefijo . '%']);
        $ultimo = $stmt->fetchColumn();

        if ($ultimo) {
            $consecutivo = (int) substr($ultimo, -4) + 1;
        } else {
            $consecutivo = 1;
        }

        return $prefijo . str_pad($consecutivo, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Crea un nuevo ticket.
     * Retorna el ID insertado.
     */
    public function crearTicket(
        string  $folio,
        int     $idSolicitante,
        int     $idCanal,
        string  $descripcion,
        string  $prioridad,
        int     $idEstatus = 1
    ): int {
        $stmt = $this->db->prepare(
            "INSERT INTO tickets (folio, id_solicitante, id_canal, descripcion, prioridad, id_estatus, fecha_creacion)
             VALUES (?, ?, ?, ?, ?, ?, NOW())"
        );
        $stmt->execute([$folio, $idSolicitante, $idCanal, $descripcion, $prioridad, $idEstatus]);
        return (int) $this->db->lastInsertId();
    }

    /**
     * Obtiene todos los tickets con joins de tablas relacionadas.
     * Soporta filtros opcionales: folio, nombre del solicitante.
     */
    public function obtenerTodosLosTickets(array $filtros = []): array {
        $sql = "SELECT
                    t.id, t.folio, t.descripcion, t.prioridad, t.fecha_creacion, t.fecha_cierre,
                    s.nombre_completo  AS solicitante,
                    s.clave_reportante AS clave,
                    cc.nombre_canal    AS canal,
                    es.nombre_estatus  AS estatus,
                    u.nombre_completo  AS tecnico
                FROM tickets t
                LEFT JOIN solicitantes   s  ON t.id_solicitante = s.id
                LEFT JOIN canales_contacto cc ON t.id_canal = cc.id
                LEFT JOIN estatus_tickets es ON t.id_estatus = es.id
                LEFT JOIN usuarios       u  ON t.id_tecnico = u.id
                WHERE 1=1";

        $params = [];

        if (!empty($filtros['folio'])) {
            $sql .= " AND t.folio LIKE ?";
            $params[] = '%' . $filtros['folio'] . '%';
        }

        if (!empty($filtros['solicitante'])) {
            $sql .= " AND s.nombre_completo LIKE ?";
            $params[] = '%' . $filtros['solicitante'] . '%';
        }

        if (isset($filtros['id_estatus']) && $filtros['id_estatus'] !== '') {
            $sql .= " AND t.id_estatus = ?";
            $params[] = (int) $filtros['id_estatus'];
        }

        $sql .= " ORDER BY t.fecha_creacion DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Obtiene tickets asignados a un técnico específico.
     */
    public function obtenerTicketsPorTecnico(int $idTecnico, array $filtros = []): array {
        $sql = "SELECT
                    t.id, t.folio, t.descripcion, t.prioridad, t.fecha_creacion, t.fecha_cierre,
                    s.nombre_completo  AS solicitante,
                    s.clave_reportante AS clave,
                    cc.nombre_canal    AS canal,
                    es.nombre_estatus  AS estatus
                FROM tickets t
                LEFT JOIN solicitantes    s  ON t.id_solicitante = s.id
                LEFT JOIN canales_contacto cc ON t.id_canal = cc.id
                LEFT JOIN estatus_tickets  es ON t.id_estatus = es.id
                WHERE t.id_tecnico = ?";

        $params = [$idTecnico];

        if (isset($filtros['id_estatus']) && $filtros['id_estatus'] !== '') {
            $sql .= " AND t.id_estatus = ?";
            $params[] = (int) $filtros['id_estatus'];
        }

        $sql .= " ORDER BY t.fecha_creacion DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Obtiene el detalle completo de un ticket por ID.
     */
    public function obtenerTicketPorId(int $id): array|false {
        $stmt = $this->db->prepare(
            "SELECT
                t.id, t.folio, t.descripcion, t.prioridad, t.fecha_creacion, t.fecha_cierre,
                t.id_tecnico, t.id_estatus, t.id_solicitante,
                s.nombre_completo  AS solicitante,
                s.clave_reportante AS clave,
                s.correo           AS correo_solicitante,
                d.nombre_departamento AS departamento,
                cc.nombre_canal    AS canal,
                es.nombre_estatus  AS estatus,
                u.nombre_completo  AS tecnico
            FROM tickets t
            LEFT JOIN solicitantes    s  ON t.id_solicitante = s.id
            LEFT JOIN departamentos   d  ON s.id_departamento = d.id
            LEFT JOIN canales_contacto cc ON t.id_canal = cc.id
            LEFT JOIN estatus_tickets  es ON t.id_estatus = es.id
            LEFT JOIN usuarios         u  ON t.id_tecnico = u.id
            WHERE t.id = ? LIMIT 1"
        );
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    /**
     * Asigna un técnico a un ticket.
     */
    public function asignarTecnico(int $idTicket, int $idTecnico): bool {
        $stmt = $this->db->prepare(
            "UPDATE tickets SET id_tecnico = ?, id_estatus = 2 WHERE id = ?"
        );
        return $stmt->execute([$idTecnico, $idTicket]);
    }

    /**
     * Actualiza el estatus de un ticket.
     */
    public function actualizarEstatus(int $idTicket, int $idEstatus): bool {
        $fechaCierre = ($idEstatus == 3) ? ', fecha_cierre = NOW()' : '';
        $stmt = $this->db->prepare(
            "UPDATE tickets SET id_estatus = ? {$fechaCierre} WHERE id = ?"
        );
        return $stmt->execute([$idEstatus, $idTicket]);
    }

    /**
     * Cierra un ticket (estatus=3) y registra fecha de cierre.
     */
    public function cerrarTicket(int $idTicket): bool {
        $stmt = $this->db->prepare(
            "UPDATE tickets SET id_estatus = 3, fecha_cierre = NOW() WHERE id = ?"
        );
        return $stmt->execute([$idTicket]);
    }

    // ══════════════════════════════════════════════════════════════════════
    //  NOTAS INTERNAS
    // ══════════════════════════════════════════════════════════════════════

    /**
     * Agrega una nota interna a un ticket.
     */
    public function agregarNota(int $idTicket, int $idTecnico, string $nota): bool {
        $stmt = $this->db->prepare(
            "INSERT INTO notas_internas (id_ticket, id_tecnico, nota, fecha_registro)
             VALUES (?, ?, ?, NOW())"
        );
        return $stmt->execute([$idTicket, $idTecnico, $nota]);
    }

    /**
     * Obtiene todas las notas de un ticket.
     */
    public function obtenerNotasPorTicket(int $idTicket): array {
        $stmt = $this->db->prepare(
            "SELECT ni.nota, ni.fecha_registro, u.nombre_completo AS tecnico
             FROM notas_internas ni
             LEFT JOIN usuarios u ON ni.id_tecnico = u.id
             WHERE ni.id_ticket = ?
             ORDER BY ni.fecha_registro ASC"
        );
        $stmt->execute([$idTicket]);
        return $stmt->fetchAll();
    }

    // ══════════════════════════════════════════════════════════════════════
    //  CATÁLOGOS
    // ══════════════════════════════════════════════════════════════════════

    /** Retorna todos los canales de contacto */
    public function obtenerCanales(): array {
        return $this->db->query("SELECT * FROM canales_contacto ORDER BY id")->fetchAll();
    }

    /** Retorna todos los estatus de tickets */
    public function obtenerEstatus(): array {
        return $this->db->query("SELECT * FROM estatus_tickets ORDER BY id")->fetchAll();
    }

    /** Retorna todos los técnicos activos (id_rol = 2) */
    public function obtenerTecnicos(): array {
        $stmt = $this->db->prepare(
            "SELECT id, nombre_completo FROM usuarios
             WHERE id_rol = 2 AND estado = 'activo'
             ORDER BY nombre_completo"
        );
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /** Retorna todos los departamentos */
    public function obtenerDepartamentos(): array {
        return $this->db->query("SELECT * FROM departamentos ORDER BY nombre_departamento")->fetchAll();
    }

    // ══════════════════════════════════════════════════════════════════════
    //  ESTADÍSTICAS (Dashboard)
    // ══════════════════════════════════════════════════════════════════════

    /**
     * Resumen de tickets por estatus para el Dashboard.
     */
    public function contarPorEstatus(): array {
        $stmt = $this->db->query(
            "SELECT es.nombre_estatus, COUNT(t.id) AS total
             FROM estatus_tickets es
             LEFT JOIN tickets t ON t.id_estatus = es.id
             GROUP BY es.id, es.nombre_estatus
             ORDER BY es.id"
        );
        return $stmt->fetchAll();
    }

    /**
     * Tickets por técnico (para gráfica del coordinador).
     */
    public function contarPorTecnico(): array {
        $stmt = $this->db->query(
            "SELECT
                COALESCE(u.nombre_completo, 'Sin asignar') AS tecnico,
                COUNT(t.id) AS total
             FROM tickets t
             LEFT JOIN usuarios u ON t.id_tecnico = u.id
             GROUP BY t.id_tecnico, u.nombre_completo
             ORDER BY total DESC
             LIMIT 10"
        );
        return $stmt->fetchAll();
    }

    /**
     * Total de tickets activos (no cerrados).
     */
    public function contarActivos(): int {
        return (int) $this->db->query(
            "SELECT COUNT(*) FROM tickets WHERE id_estatus != 3"
        )->fetchColumn();
    }

    /**
     * Total de tickets cerrados.
     */
    public function contarCerrados(): int {
        return (int) $this->db->query(
            "SELECT COUNT(*) FROM tickets WHERE id_estatus = 3"
        )->fetchColumn();
    }

    /**
     * Total general de tickets.
     */
    public function contarTotal(): int {
        return (int) $this->db->query(
            "SELECT COUNT(*) FROM tickets"
        )->fetchColumn();
    }
}
