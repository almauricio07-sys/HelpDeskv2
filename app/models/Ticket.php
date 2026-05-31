<?php
/**
 * Modelo: Ticket
 *
 * Gestiona todas las operaciones de base de datos relacionadas con tickets,
 * solicitantes, notas internas y estatus.
 *
 * IDs de estatus (helpdesk_db):
 *   1 = Pendiente de Asignación
 *   2 = En Proceso
 *   3 = Terminado
 *   4 = Pendiente de Validación
 *   5 = Cerrado
 *
 * IDs de rol (helpdesk_db):
 *   1 = Coordinador
 *   2 = Soporte Técnico
 *   3 = Mesa de Ayuda
 *
 * Sistema de Mesa de Ayuda - Los Bélicos
 */
class Ticket {
    private PDO $db;

    public function __construct() {
        $dbInstance = Database::getInstance();
        $this->db   = $dbInstance->getConnection();

        if (!$this->db) {
            throw new Exception("Error crítico: la conexión a la base de datos no está disponible.");
        }
    }

    // ══════════════════════════════════════════════════════════════════════
    //  SOLICITANTES
    // ══════════════════════════════════════════════════════════════════════

    public function buscarSolicitantePorClave(string $clave): array|false {
        $stmt = $this->db->prepare(
            "SELECT * FROM solicitantes WHERE clave_reportante = ? LIMIT 1"
        );
        $stmt->execute([$clave]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

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
    //  TICKETS — CREACIÓN
    // ══════════════════════════════════════════════════════════════════════

    public function generarFolioUnico(): string {
        $fecha   = date('Ymd');
        $prefijo = "HD-{$fecha}-";
        $stmt    = $this->db->prepare(
            "SELECT folio FROM tickets WHERE folio LIKE ? ORDER BY id DESC LIMIT 1"
        );
        $stmt->execute([$prefijo . '%']);
        $ultimo      = $stmt->fetchColumn();
        $consecutivo = $ultimo ? (int) substr($ultimo, -4) + 1 : 1;
        return $prefijo . str_pad($consecutivo, 4, '0', STR_PAD_LEFT);
    }

    public function crearTicket(
        string $folio,
        int    $idSolicitante,
        int    $idCanal,
        string $descripcion,
        string $prioridad,
        int    $idEstatus = 1
    ): int {
        $stmt = $this->db->prepare(
            "INSERT INTO tickets
                (folio, id_solicitante, id_canal, descripcion, prioridad, id_estatus, fecha_creacion)
             VALUES (?, ?, ?, ?, ?, ?, NOW())"
        );
        $stmt->execute([$folio, $idSolicitante, $idCanal, $descripcion, $prioridad, $idEstatus]);
        return (int) $this->db->lastInsertId();
    }

    /**
     * RF_01 + RF_03: Upsert del solicitante + alta del ticket en una sola transacción.
     */
    public function registrarTicketConSolicitante(
        string $claveReportante,
        string $nombreCompleto,
        string $correo,
        int    $idDepartamento,
        int    $idCanal,
        string $descripcion,
        string $prioridad
    ): string {
        try {
            $this->db->beginTransaction();

            $solicitante   = $this->buscarSolicitantePorClave($claveReportante);
            $idSolicitante = $solicitante
                ? (int) $solicitante['id']
                : $this->crearSolicitante($claveReportante, $nombreCompleto, $correo, $idDepartamento);

            $folio = $this->generarFolioUnico();
            $this->crearTicket($folio, $idSolicitante, $idCanal, $descripcion, $prioridad, 1);

            $this->db->commit();
            return $folio;

        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    // ══════════════════════════════════════════════════════════════════════
    //  TICKETS — FLUJO DE VALIDACIÓN (RF_09 / RF_10)
    // ══════════════════════════════════════════════════════════════════════

    /**
     * RF_09: Técnico marca "Terminado" → pasa a "Pendiente de Validación" (id=4).
     *
     * Reasignación automática garantizada:
     *   - COALESCE preserva id_mesa_asignada si ya tiene valor.
     *   - Si es NULL (asignación directa por Coordinador), usa $idMesaFallback.
     * Esto asegura que siempre haya un responsable de Mesa para el cierre (RF_10)
     * y que RF_15 contabilice correctamente la carga de validación.
     */
    public function enviarAValidacion(int $idTicket, ?int $idMesaFallback = null): bool {
        $stmt = $this->db->prepare(
            "UPDATE tickets
             SET id_estatus       = 4,
                 id_mesa_asignada = COALESCE(id_mesa_asignada, ?)
             WHERE id = ?"
        );
        return $stmt->execute([$idMesaFallback, $idTicket]);
    }

    /**
     * RF_15: Retorna el id del primer usuario de Mesa de Ayuda (id_rol=3) activo.
     * Fallback cuando ningún usuario de Mesa fue asignado explícitamente.
     */
    public function obtenerPrimerMesaActiva(): ?int {
        $result = $this->db->query(
            "SELECT id FROM usuarios
             WHERE id_rol = 3 AND estado = 'Activo'
             ORDER BY id ASC LIMIT 1"
        )->fetchColumn();
        return $result ? (int) $result : null;
    }

    // ══════════════════════════════════════════════════════════════════════
    //  TRANSACCIONES (orquestadas desde el controlador)
    // ══════════════════════════════════════════════════════════════════════

    public function beginTransaction(): bool { return $this->db->beginTransaction(); }
    public function commit(): bool           { return $this->db->commit(); }
    public function rollBack(): bool         { return $this->db->rollBack(); }
    public function inTransaction(): bool    { return $this->db->inTransaction(); }

    // ══════════════════════════════════════════════════════════════════════
    //  TICKETS — CONSULTAS
    // ══════════════════════════════════════════════════════════════════════

    public function obtenerTodosLosTickets(array $filtros = []): array {
        $sql = "SELECT t.id, t.folio, t.descripcion, t.prioridad,
                       t.fecha_creacion, t.fecha_cierre,
                       s.nombre_completo AS solicitante,
                       cc.nombre_canal   AS canal,
                       es.nombre_estatus AS estatus,
                       u.nombre_completo AS tecnico
                FROM tickets t
                LEFT JOIN solicitantes    s  ON t.id_solicitante = s.id
                LEFT JOIN canales_contacto cc ON t.id_canal      = cc.id
                LEFT JOIN estatus_tickets  es ON t.id_estatus    = es.id
                LEFT JOIN usuarios         u  ON t.id_tecnico    = u.id
                WHERE 1=1";
        $params = [];

        if (!empty($filtros['folio'])) {
            $sql     .= " AND t.folio LIKE ?";
            $params[] = '%' . $filtros['folio'] . '%';
        }
        if (isset($filtros['id_estatus']) && $filtros['id_estatus'] !== '') {
            $sql     .= " AND t.id_estatus = ?";
            $params[] = (int) $filtros['id_estatus'];
        }

        $sql .= " ORDER BY t.fecha_creacion DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * RF_06: Folios del técnico filtrados por su propio id.
     * Por defecto, SOLO muestra tickets activos (Pendiente o En Proceso).
     * Si se requiere el historial completo, se debe pasar un filtro específico.
     */
    public function obtenerTicketsPorTecnico(int $idTecnico, array $filtros = []): array {
        $sql = "SELECT t.id, t.folio, t.descripcion, t.prioridad,
                       t.fecha_creacion, t.id_estatus,
                       s.nombre_completo  AS solicitante,
                       s.clave_reportante AS clave,
                       cc.nombre_canal    AS canal,
                       es.nombre_estatus  AS estatus
                FROM tickets t
                LEFT JOIN solicitantes    s  ON t.id_solicitante = s.id
                LEFT JOIN canales_contacto cc ON t.id_canal      = cc.id
                LEFT JOIN estatus_tickets  es ON t.id_estatus    = es.id
                WHERE t.id_tecnico = ?";
        
        $params = [$idTecnico];

        // LÓGICA DE FILTRADO (PROBLEMA 2 RESUELTO)
        if (isset($filtros['id_estatus']) && $filtros['id_estatus'] !== '') {
            // Si el usuario pidió ver un estatus específico (ej. usando un buscador)
            $sql     .= " AND t.id_estatus = ?";
            $params[] = (int) $filtros['id_estatus'];
        } else {
            // COMPORTAMIENTO POR DEFECTO: Solo mostrar Pendiente (1) y En Proceso (2)
            // Ajusta estos IDs si en tu BD son diferentes.
            $sql .= " AND t.id_estatus IN (1, 2)";
        }

        $sql .= " ORDER BY t.fecha_creacion DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

/**
     * Obtiene las estadísticas de carga de trabajo de un técnico específico.
     * Sirve para llenar las tarjetas (cards) del Dashboard del técnico.
     */
    public function obtenerEstadisticasTecnico(int $idTecnico): array {
        // Se ajustan los IDs de estatus:
        // 1, 2 -> Pendiente / En proceso
        // 3, 4 -> Terminado / Pendiente de validación por Mesa
        // 5    -> Cerrado
        $sql = "SELECT 
                    COUNT(*) AS total_asignados,
                    SUM(CASE WHEN id_estatus IN (1, 2) THEN 1 ELSE 0 END) AS por_hacer,
                    SUM(CASE WHEN id_estatus IN (3, 4) THEN 1 ELSE 0 END) AS en_validacion,
                    SUM(CASE WHEN id_estatus = 5 THEN 1 ELSE 0 END) AS cerrados
                FROM tickets 
                WHERE id_tecnico = :id_tecnico";
                
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id_tecnico' => $idTecnico]);
            
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            if (!$result || !isset($result['total_asignados'])) {
                return ['total_asignados' => 0, 'por_hacer' => 0, 'en_validacion' => 0, 'cerrados' => 0];
            }
            
            return [
                'total_asignados' => (int)$result['total_asignados'],
                'por_hacer'       => (int)($result['por_hacer'] ?? 0),
                'en_validacion'   => (int)($result['en_validacion'] ?? 0),
                'cerrados'        => (int)($result['cerrados'] ?? 0),
            ];
            
        } catch (\PDOException $e) {
            error_log('Error en obtenerEstadisticasTecnico: ' . $e->getMessage());
            return ['total_asignados' => 0, 'por_hacer' => 0, 'en_validacion' => 0, 'cerrados' => 0];
        }
    }

    

    /**
     * RF_10: Folios en "Pendiente de Validación" (id=4) asignados a este usuario de Mesa.
     */
    public function obtenerTicketsPorValidar(int $idMesa): array {
        $stmt = $this->db->prepare(
            "SELECT t.id, t.folio, t.descripcion, t.prioridad, t.fecha_creacion,
                    s.nombre_completo  AS solicitante,
                    s.clave_reportante AS clave,
                    u.nombre_completo  AS tecnico
             FROM tickets t
             LEFT JOIN solicitantes s ON t.id_solicitante = s.id
             LEFT JOIN usuarios     u ON t.id_tecnico     = u.id
             WHERE t.id_mesa_asignada = ? AND t.id_estatus = 4
             ORDER BY t.fecha_creacion ASC"
        );
        $stmt->execute([$idMesa]);
        return $stmt->fetchAll();
    }

    public function obtenerTicketPorId(int $id): array|false {
        $stmt = $this->db->prepare(
            "SELECT t.*,
                    s.nombre_completo  AS solicitante,
                    s.clave_reportante AS clave,
                    s.correo           AS correo_solicitante,
                    d.nombre_departamento AS departamento,
                    cc.nombre_canal    AS canal,
                    es.nombre_estatus  AS estatus,
                    u.nombre_completo  AS tecnico
             FROM tickets t
             LEFT JOIN solicitantes     s  ON t.id_solicitante = s.id
             LEFT JOIN departamentos    d  ON s.id_departamento = d.id
             LEFT JOIN canales_contacto cc ON t.id_canal        = cc.id
             LEFT JOIN estatus_tickets  es ON t.id_estatus      = es.id
             LEFT JOIN usuarios         u  ON t.id_tecnico      = u.id
             WHERE t.id = ? LIMIT 1"
        );
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    // ══════════════════════════════════════════════════════════════════════
    //  TICKETS — MODIFICACIONES
    // ══════════════════════════════════════════════════════════════════════

    /**
     * RF_07: Asigna técnico y pone ticket "En Proceso" (id=2).
     */
    public function asignarTecnico(int $idTicket, int $idTecnico, ?int $idMesa = null): bool {
        if ($idMesa !== null) {
            $stmt = $this->db->prepare(
                "UPDATE tickets SET id_tecnico = ?, id_estatus = 2, id_mesa_asignada = ? WHERE id = ?"
            );
            return $stmt->execute([$idTecnico, $idMesa, $idTicket]);
        }
        $stmt = $this->db->prepare(
            "UPDATE tickets SET id_tecnico = ?, id_estatus = 2 WHERE id = ?"
        );
        return $stmt->execute([$idTecnico, $idTicket]);
    }

    /**
     * Actualiza el estatus de un ticket.
     * Registra fecha_cierre solo cuando el ticket llega a "Cerrado" (id=5).
     */
    public function actualizarEstatus(int $idTicket, int $idEstatus): bool {
        $fechaCierre = ($idEstatus === 5) ? ', fecha_cierre = NOW()' : '';
        $stmt = $this->db->prepare(
            "UPDATE tickets SET id_estatus = ? {$fechaCierre} WHERE id = ?"
        );
        return $stmt->execute([$idEstatus, $idTicket]);
    }

    // ══════════════════════════════════════════════════════════════════════
    //  NOTAS INTERNAS (RF_08)
    // ══════════════════════════════════════════════════════════════════════

    public function agregarNota(int $idTicket, int $idUsuario, string $nota): bool {
        $stmt = $this->db->prepare(
            "INSERT INTO notas_internas (id_ticket, id_usuario, nota, fecha_registro)
             VALUES (?, ?, ?, NOW())"
        );
        return $stmt->execute([$idTicket, $idUsuario, $nota]);
    }

    public function obtenerNotasPorTicket(int $idTicket): array {
        $stmt = $this->db->prepare(
            "SELECT ni.nota, ni.fecha_registro, u.nombre_completo AS tecnico, r.nombre_rol
             FROM notas_internas ni
             LEFT JOIN usuarios u ON ni.id_usuario = u.id
             LEFT JOIN roles    r ON u.id_rol      = r.id
             WHERE ni.id_ticket = ?
             ORDER BY ni.fecha_registro ASC"
        );
        $stmt->execute([$idTicket]);
        return $stmt->fetchAll();
    }

    // ══════════════════════════════════════════════════════════════════════
    //  CATÁLOGOS
    // ══════════════════════════════════════════════════════════════════════

    public function obtenerCanales(): array {
        return $this->db->query("SELECT * FROM canales_contacto ORDER BY id")->fetchAll();
    }
    public function obtenerEstatus(): array {
        return $this->db->query("SELECT * FROM estatus_tickets ORDER BY id")->fetchAll();
    }
    public function obtenerDepartamentos(): array {
        return $this->db->query("SELECT * FROM departamentos ORDER BY nombre_departamento")->fetchAll();
    }

    /**
     * Técnicos de Soporte (id_rol = 2) para dropdown de asignación (RF_07).
     */
    public function obtenerTecnicos(): array {
        return $this->db->query(
            "SELECT id, nombre_completo FROM usuarios
             WHERE id_rol = 2 AND estado = 'Activo'
             ORDER BY nombre_completo"
        )->fetchAll();
    }

    /**
     * Personal de Mesa de Ayuda (id_rol = 3) para dropdown de validación.
     */
    public function obtenerPersonalMesa(): array {
        $stmt = $this->db->prepare(
            "SELECT id, nombre_completo FROM usuarios
             WHERE id_rol = 3 AND estado = 'Activo'
             ORDER BY nombre_completo"
        );
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // ══════════════════════════════════════════════════════════════════════
    //  ESTADÍSTICAS (RF_14 / RF_15)
    // ══════════════════════════════════════════════════════════════════════

    public function contarPorEstatus(): array {
        return $this->db->query(
            "SELECT es.nombre_estatus, COALESCE(COUNT(t.id), 0) AS total
             FROM estatus_tickets es
             LEFT JOIN tickets t ON t.id_estatus = es.id
             GROUP BY es.id, es.nombre_estatus
             ORDER BY es.id"
        )->fetchAll(PDO::FETCH_ASSOC);
    }

    public function contarPorTecnico(): array {
        return $this->db->query(
            "SELECT COALESCE(u.nombre_completo, 'Sin asignar') AS tecnico,
                    COUNT(t.id) AS total
             FROM tickets t
             LEFT JOIN usuarios u ON t.id_tecnico = u.id
             GROUP BY t.id_tecnico
             ORDER BY total DESC"
        )->fetchAll();
    }

    /** RF_14: Tickets activos = todo lo que NO está "Cerrado" (id=5). */
    public function contarActivos(): int {
        return (int) $this->db->query(
            "SELECT COUNT(*) FROM tickets WHERE id_estatus != 5"
        )->fetchColumn();
    }

    /** RF_14: Tickets cerrados definitivamente (id=5). */
    public function contarCerrados(): int {
        return (int) $this->db->query(
            "SELECT COUNT(*) FROM tickets WHERE id_estatus = 5"
        )->fetchColumn();
    }

    public function contarTotal(): int {
        return (int) $this->db->query("SELECT COUNT(*) FROM tickets")->fetchColumn();
    }

    /**
     * RF_15: Carga de trabajo por usuario de Soporte Técnico y Mesa de Ayuda.
     *
     * Regla de atribución:
     *   - Ticket en "Pendiente de Validación" (id=4) → se atribuye a Mesa (id_mesa_asignada).
     *   - Resto de tickets activos               → se atribuye al técnico (id_tecnico).
     * Excluye los tickets "Cerrados" (id=5).
     * Incluye usuarios con 0 tickets para mostrar capacidad disponible.
     */
    public function contarCargaPorUsuario(): array {
        $sqlConCarga = "
            SELECT COALESCE(u.nombre_completo, 'Sin asignar') AS tecnico,
                   COALESCE(r.nombre_rol, 'General')          AS rol,
                   COUNT(c.id)                                AS total
            FROM (
                SELECT t.id,
                       CASE WHEN t.id_estatus = 4
                            THEN t.id_mesa_asignada
                            ELSE t.id_tecnico
                       END AS id_owner
                FROM tickets t
                WHERE t.id_estatus <> 5
            ) c
            LEFT JOIN usuarios u ON c.id_owner = u.id
            LEFT JOIN roles    r ON u.id_rol   = r.id
            GROUP BY c.id_owner, u.nombre_completo, r.nombre_rol
        ";
        $conCarga = $this->db->query($sqlConCarga)->fetchAll(PDO::FETCH_ASSOC);

        $sqlSinCarga = "
            SELECT u.nombre_completo AS tecnico,
                   r.nombre_rol      AS rol,
                   0                 AS total
            FROM usuarios u
            INNER JOIN roles r ON u.id_rol = r.id
            WHERE u.id_rol IN (2, 3) AND u.estado = 'Activo'
              AND u.id NOT IN (
                  SELECT id_owner
                  FROM (
                      SELECT CASE WHEN id_estatus = 4
                                  THEN id_mesa_asignada
                                  ELSE id_tecnico
                             END AS id_owner
                      FROM tickets
                      WHERE id_estatus <> 5
                  ) act
                  WHERE id_owner IS NOT NULL
              )
        ";
        $sinCarga = $this->db->query($sqlSinCarga)->fetchAll(PDO::FETCH_ASSOC);

        $resultado = array_merge($conCarga, $sinCarga);
        usort($resultado, fn($a, $b) => $b['total'] <=> $a['total']);
        return $resultado;
    }
}
