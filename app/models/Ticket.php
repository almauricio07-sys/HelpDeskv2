<?php
/**
 * Modelo: Ticket
 * * Gestiona todas las operaciones de base de datos relacionadas con tickets,
 * solicitantes, notas internas y estatus.
 * * Sistema de Mesa de Ayuda - Los Bélicos
 */
class Ticket {
    private PDO $db;

    public function __construct() {
        // Aseguramos la obtención de la conexión desde el Singleton
        $dbInstance = Database::getInstance();
        $this->db = $dbInstance->getConnection();
        
        if (!$this->db) {
            throw new Exception("Error crítico: La conexión a la base de datos no está disponible.");
        }
    }

    // ══════════════════════════════════════════════════════════════════════
    //  SOLICITANTES
    // ══════════════════════════════════════════════════════════════════════

    public function buscarSolicitantePorClave(string $clave): array|false {
        $stmt = $this->db->prepare("SELECT * FROM solicitantes WHERE clave_reportante = ? LIMIT 1");
        $stmt->execute([$clave]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function crearSolicitante(string $claveReportante, string $nombreCompleto, string $correo, int $idDepartamento): int {
        $stmt = $this->db->prepare(
            "INSERT INTO solicitantes (clave_reportante, nombre_completo, correo, id_departamento) VALUES (?, ?, ?, ?)"
        );
        $stmt->execute([$claveReportante, $nombreCompleto, $correo, $idDepartamento]);
        return (int) $this->db->lastInsertId();
    }

    // ══════════════════════════════════════════════════════════════════════
    //  TICKETS
    // ══════════════════════════════════════════════════════════════════════

    public function generarFolioUnico(): string {
        $fecha   = date('Ymd');
        $prefijo = "HD-{$fecha}-";
        $stmt = $this->db->prepare("SELECT folio FROM tickets WHERE folio LIKE ? ORDER BY id DESC LIMIT 1");
        $stmt->execute([$prefijo . '%']);
        $ultimo = $stmt->fetchColumn();
        $consecutivo = $ultimo ? (int) substr($ultimo, -4) + 1 : 1;
        return $prefijo . str_pad($consecutivo, 4, '0', STR_PAD_LEFT);
    }

    public function crearTicket(string $folio, int $idSolicitante, int $idCanal, string $descripcion, string $prioridad, int $idEstatus = 1): int {
        $stmt = $this->db->prepare(
            "INSERT INTO tickets (folio, id_solicitante, id_canal, descripcion, prioridad, id_estatus, fecha_creacion) VALUES (?, ?, ?, ?, ?, ?, NOW())"
        );
        $stmt->execute([$folio, $idSolicitante, $idCanal, $descripcion, $prioridad, $idEstatus]);
        return (int) $this->db->lastInsertId();
    }

    /**
     * RF_01 + RF_03: Registra el ticket aplicando "Upsert" del solicitante en
     * una sola transacción.
     *
     * - Si la clave_reportante ya existe, reutiliza ese solicitante.
     * - Si no existe, lo inserta primero y luego crea el ticket.
     *
     * Todo ocurre dentro de beginTransaction/commit para garantizar que nunca
     * quede un solicitante huérfano si la inserción del ticket falla.
     *
     * @return string El folio generado para el nuevo ticket.
     * @throws Exception si la operación no puede completarse (se hace rollBack).
     */
    public function registrarTicketConSolicitante(
        string $claveReportante, string $nombreCompleto, string $correo, int $idDepartamento,
        int $idCanal, string $descripcion, string $prioridad
    ): string {
        try {
            $this->db->beginTransaction();

            $solicitante = $this->buscarSolicitantePorClave($claveReportante);
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

    /**
     * Flujo de validación (RF_10) — acción del Técnico al marcar "Terminado".
     *
     * NO cierra el ticket. Lo pasa al estatus intermedio 5 ("Pendiente de
     * Validación") para que Mesa de Ayuda lo revise y realice el cierre
     * definitivo. Se conserva:
     *   - id_tecnico:        para mantener el historial de quién atendió.
     *   - id_mesa_asignada:  el usuario de Mesa original que validará el folio.
     */
    public function enviarAValidacion(int $idTicket): bool {
        $stmt = $this->db->prepare("UPDATE tickets SET id_estatus = 5 WHERE id = ?");
        return $stmt->execute([$idTicket]);
    }

    // ── Control de transacciones (orquestado desde el controlador) ──────────
    public function beginTransaction(): bool { return $this->db->beginTransaction(); }
    public function commit(): bool          { return $this->db->commit(); }
    public function rollBack(): bool        { return $this->db->rollBack(); }
    public function inTransaction(): bool   { return $this->db->inTransaction(); }

    public function obtenerTodosLosTickets(array $filtros = []): array {
        $sql = "SELECT t.id, t.folio, t.descripcion, t.prioridad, t.fecha_creacion, t.fecha_cierre,
                       s.nombre_completo AS solicitante, cc.nombre_canal AS canal, 
                       es.nombre_estatus AS estatus, u.nombre_completo AS tecnico
                FROM tickets t
                LEFT JOIN solicitantes s ON t.id_solicitante = s.id
                LEFT JOIN canales_contacto cc ON t.id_canal = cc.id
                LEFT JOIN estatus_tickets es ON t.id_estatus = es.id
                LEFT JOIN usuarios u ON t.id_tecnico = u.id
                WHERE 1=1";
        
        $params = [];
        if (!empty($filtros['folio'])) { $sql .= " AND t.folio LIKE ?"; $params[] = '%' . $filtros['folio'] . '%'; }
        if (isset($filtros['id_estatus']) && $filtros['id_estatus'] !== '') { $sql .= " AND t.id_estatus = ?"; $params[] = (int) $filtros['id_estatus']; }

        $sql .= " ORDER BY t.fecha_creacion DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * RF_06: Folios del técnico. Ahora incluye TODOS sus folios (incluidos los
     * Cerrados) para que pueda consultar su historial. Acepta un filtro opcional
     * por estatus.
     */
    public function obtenerTicketsPorTecnico(int $idTecnico, array $filtros = []): array {
        $sql = "SELECT t.id, t.folio, t.descripcion, t.prioridad, t.fecha_creacion, t.id_estatus,
                       s.nombre_completo AS solicitante, s.clave_reportante AS clave,
                       cc.nombre_canal AS canal, es.nombre_estatus AS estatus
                FROM tickets t
                LEFT JOIN solicitantes s ON t.id_solicitante = s.id
                LEFT JOIN canales_contacto cc ON t.id_canal = cc.id
                LEFT JOIN estatus_tickets es ON t.id_estatus = es.id
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
     * Panel de Validación (RF_10) — folios asignados a un usuario de Mesa de
     * Ayuda que están en estatus 5 (Pendiente de Validación), listos para que
     * los revise y cierre.
     *
     * @param int $idMesa usuarios.id del usuario de Mesa logueado.
     */
    public function obtenerTicketsPorValidar(int $idMesa): array {
        $stmt = $this->db->prepare(
            "SELECT t.id, t.folio, t.descripcion, t.prioridad, t.fecha_creacion,
                    s.nombre_completo AS solicitante, s.clave_reportante AS clave,
                    u.nombre_completo AS tecnico
             FROM tickets t
             LEFT JOIN solicitantes s ON t.id_solicitante = s.id
             LEFT JOIN usuarios u ON t.id_tecnico = u.id
             WHERE t.id_mesa_asignada = ? AND t.id_estatus = 5
             ORDER BY t.fecha_creacion ASC"
        );
        $stmt->execute([$idMesa]);
        return $stmt->fetchAll();
    }

    public function obtenerTicketPorId(int $id): array|false {
        $stmt = $this->db->prepare(
            "SELECT t.*, s.nombre_completo AS solicitante, s.clave_reportante AS clave,
                    s.correo AS correo_solicitante,
                    d.nombre_departamento AS departamento, cc.nombre_canal AS canal,
                    es.nombre_estatus AS estatus, u.nombre_completo AS tecnico
             FROM tickets t
             LEFT JOIN solicitantes s ON t.id_solicitante = s.id
             LEFT JOIN departamentos d ON s.id_departamento = d.id
             LEFT JOIN canales_contacto cc ON t.id_canal = cc.id
             LEFT JOIN estatus_tickets es ON t.id_estatus = es.id
             LEFT JOIN usuarios u ON t.id_tecnico = u.id
             WHERE t.id = ? LIMIT 1"
        );
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    /**
     * RF_07: Asigna un técnico y pone el ticket "En Proceso" (estatus 2).
     * Si se pasa $idMesa, registra al usuario de Mesa que deberá validar al
     * cierre (poblando id_mesa_asignada), para la automatización de "Terminado".
     */
    public function asignarTecnico(int $idTicket, int $idTecnico, ?int $idMesa = null): bool {
        if ($idMesa !== null) {
            $stmt = $this->db->prepare(
                "UPDATE tickets SET id_tecnico = ?, id_estatus = 2, id_mesa_asignada = ? WHERE id = ?"
            );
            return $stmt->execute([$idTecnico, $idMesa, $idTicket]);
        }
        $stmt = $this->db->prepare("UPDATE tickets SET id_tecnico = ?, id_estatus = 2 WHERE id = ?");
        return $stmt->execute([$idTecnico, $idTicket]);
    }

    public function actualizarEstatus(int $idTicket, int $idEstatus): bool {
        $fechaCierre = ($idEstatus == 3) ? ', fecha_cierre = NOW()' : '';
        $stmt = $this->db->prepare("UPDATE tickets SET id_estatus = ? {$fechaCierre} WHERE id = ?");
        return $stmt->execute([$idEstatus, $idTicket]);
    }


    public function agregarNota(int $idTicket, int $idTecnico, string $nota): bool {
        $stmt = $this->db->prepare(
            "INSERT INTO notas_internas (id_ticket, id_tecnico, nota, fecha_registro) VALUES (?, ?, ?, NOW())"
        );
        return $stmt->execute([$idTicket, $idTecnico, $nota]);
    }

    public function obtenerNotasPorTicket(int $idTicket): array {
        $stmt = $this->db->prepare(
            "SELECT ni.nota, ni.fecha_registro, u.nombre_completo AS tecnico
             FROM notas_internas ni
             LEFT JOIN usuarios u ON ni.id_tecnico = u.id
             WHERE ni.id_ticket = ? ORDER BY ni.fecha_registro ASC"
        );
        $stmt->execute([$idTicket]);
        return $stmt->fetchAll();
    }

    /**
     * Retorna todos los usuarios de Mesa de Ayuda (id_rol = 3)
     */
    public function obtenerPersonalMesa(): array {
        $stmt = $this->db->prepare(
            "SELECT id, nombre_completo FROM usuarios 
            WHERE id_rol = 3 AND estado = 'activo' 
            ORDER BY nombre_completo"
        );
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // ══════════════════════════════════════════════════════════════════════
    //  CATÁLOGOS Y ESTADÍSTICAS
    // ══════════════════════════════════════════════════════════════════════

    public function obtenerCanales(): array { return $this->db->query("SELECT * FROM canales_contacto ORDER BY id")->fetchAll(); }
    public function obtenerEstatus(): array { return $this->db->query("SELECT * FROM estatus_tickets ORDER BY id")->fetchAll(); }
    public function obtenerTecnicos(): array { return $this->db->query("SELECT id, nombre_completo FROM usuarios WHERE id_rol = 2 AND estado = 'activo' ORDER BY nombre_completo")->fetchAll(); }
    public function obtenerDepartamentos(): array { return $this->db->query("SELECT * FROM departamentos ORDER BY nombre_departamento")->fetchAll(); }

    public function contarPorEstatus(): array {
        return $this->db->query("SELECT es.nombre_estatus, COUNT(t.id) AS total FROM estatus_tickets es LEFT JOIN tickets t ON t.id_estatus = es.id GROUP BY es.id")->fetchAll();
    }

    public function contarPorTecnico(): array {
        return $this->db->query("SELECT COALESCE(u.nombre_completo, 'Sin asignar') AS tecnico, COUNT(t.id) AS total FROM tickets t LEFT JOIN usuarios u ON t.id_tecnico = u.id GROUP BY t.id_tecnico ORDER BY total DESC")->fetchAll();
    }

    public function contarActivos(): int { return (int) $this->db->query("SELECT COUNT(*) FROM tickets WHERE id_estatus != 3")->fetchColumn(); }
    public function contarCerrados(): int { return (int) $this->db->query("SELECT COUNT(*) FROM tickets WHERE id_estatus = 3")->fetchColumn(); }
    public function contarTotal(): int { return (int) $this->db->query("SELECT COUNT(*) FROM tickets")->fetchColumn(); }

    /**
     * RF_15: Obtiene la carga de trabajo de Soporte y Mesa de Ayuda.
     * Incluye a los técnicos que tienen 0 tickets asignados.
     */
    public function contarCargaPorUsuario(): array {
        // 1. Carga real por usuario. Cada ticket NO cerrado cuenta una sola vez y
        //    se atribuye a su responsable ACTUAL:
        //      - estatus 5 (Pendiente de Validación) → usuario de Mesa (id_mesa_asignada)
        //      - resto de activos                     → técnico (id_tecnico)
        //    Así la Mesa de Ayuda ve reflejado su trabajo de validación (RF_15).
        $sqlConCarga = "
            SELECT COALESCE(u.nombre_completo, 'Sin asignar') AS tecnico,
                   COALESCE(r.nombre_rol, 'General') AS rol,
                   COUNT(c.id) AS total
            FROM (
                SELECT t.id,
                       CASE WHEN t.id_estatus = 5 THEN t.id_mesa_asignada ELSE t.id_tecnico END AS id_owner
                FROM tickets t
                WHERE t.id_estatus <> 3 -- Omitimos los cerrados
            ) c
            LEFT JOIN usuarios u ON c.id_owner = u.id
            LEFT JOIN roles r ON u.id_rol = r.id
            GROUP BY c.id_owner, u.nombre_completo, r.nombre_rol
        ";
        $conCarga = $this->db->query($sqlConCarga)->fetchAll(PDO::FETCH_ASSOC);

        // 2. Usuarios de Soporte (2) y Mesa de Ayuda (3) activos sin carga (total = 0)
        $sqlSinCarga = "
            SELECT u.nombre_completo AS tecnico,
                   r.nombre_rol AS rol,
                   0 AS total
            FROM usuarios u
            INNER JOIN roles r ON u.id_rol = r.id
            WHERE u.id_rol IN (2, 3) AND u.estado = 'activo'
            AND u.id NOT IN (
                SELECT id_owner FROM (
                    SELECT CASE WHEN id_estatus = 5 THEN id_mesa_asignada ELSE id_tecnico END AS id_owner
                    FROM tickets
                    WHERE id_estatus <> 3
                ) act
                WHERE id_owner IS NOT NULL
            )
        ";
        $sinCarga = $this->db->query($sqlSinCarga)->fetchAll(PDO::FETCH_ASSOC);

        // Combinamos ambos resultados y ordenamos de mayor a menor carga
        $resultado = array_merge($conCarga, $sinCarga);
        usort($resultado, fn($a, $b) => $b['total'] <=> $a['total']);

        return $resultado;
    }

    
}
