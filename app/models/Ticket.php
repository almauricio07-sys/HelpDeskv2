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
        return $stmt->fetch();
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
     * Envía el ticket a validación (RF_10)
     * Quita al técnico asignado y cambia el estatus a 4 (Validación).
     */
    public function enviarAValidacion(int $idTicket): bool {
        // Asegúrate de que el ID 4 corresponde al estatus de "Validación" en tu tabla estatus_tickets
        $stmt = $this->db->prepare(
            "UPDATE tickets SET id_tecnico = NULL, id_estatus = 4 WHERE id = ?"
        );
        return $stmt->execute([$idTicket]);
    }

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

    public function obtenerTicketsPorTecnico(int $idTecnico): array {
        // Excluimos estatus 3 (Cerrado) y 4 (Validación) para que el técnico no los vea
        $stmt = $this->db->prepare(
            "SELECT t.id, t.folio, t.descripcion, t.prioridad, t.fecha_creacion,
                    s.nombre_completo AS solicitante, es.nombre_estatus AS estatus
             FROM tickets t
             LEFT JOIN solicitantes s ON t.id_solicitante = s.id
             LEFT JOIN estatus_tickets es ON t.id_estatus = es.id
             WHERE t.id_tecnico = ? AND t.id_estatus NOT IN (3, 4)
             ORDER BY t.fecha_creacion DESC"
        );
        $stmt->execute([$idTecnico]);
        return $stmt->fetchAll();
    }

    public function obtenerTicketPorId(int $id): array|false {
        $stmt = $this->db->prepare(
            "SELECT t.*, s.nombre_completo AS solicitante, s.correo AS correo_solicitante,
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

    public function asignarTecnico(int $idTicket, int $idTecnico): bool {
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

    /**
     * Reasigna el ticket a un miembro de Mesa de Ayuda para validación
     */
    public function reasignarAMesa(int $idTicket, int $idMesa): bool {
        // Estatus 4 = Validación
        $stmt = $this->db->prepare(
            "UPDATE tickets SET id_tecnico = NULL, id_mesa_asignada = ?, id_estatus = 4 WHERE id = ?"
        );
        return $stmt->execute([$idMesa, $idTicket]);
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
        // 1. Usuarios con tickets activos asignados (incluye "Sin asignar")
        $sqlConCarga = "
            SELECT COALESCE(u.nombre_completo, 'Sin asignar') AS tecnico, 
                   COALESCE(r.nombre_rol, 'General') AS rol, 
                   COUNT(t.id) AS total 
            FROM tickets t 
            LEFT JOIN usuarios u ON t.id_tecnico = u.id 
            LEFT JOIN roles r ON u.id_rol = r.id 
            WHERE t.id_estatus != 3 -- Omitimos los cerrados
            GROUP BY t.id_tecnico, u.nombre_completo, r.nombre_rol
        ";
        $conCarga = $this->db->query($sqlConCarga)->fetchAll(PDO::FETCH_ASSOC);

        // 2. Usuarios de Soporte (2) y Mesa de Ayuda (3) sin tickets activos (carga = 0)
        $sqlSinCarga = "
            SELECT u.nombre_completo AS tecnico, 
                   r.nombre_rol AS rol, 
                   0 AS total
            FROM usuarios u
            INNER JOIN roles r ON u.id_rol = r.id
            WHERE u.id_rol IN (2, 3) AND u.estado = 'activo'
            AND u.id NOT IN (
                SELECT id_tecnico FROM tickets WHERE id_tecnico IS NOT NULL AND id_estatus != 3
            )
        ";
        $sinCarga = $this->db->query($sqlSinCarga)->fetchAll(PDO::FETCH_ASSOC);

        // Combinamos ambos resultados y ordenamos de mayor a menor carga
        $resultado = array_merge($conCarga, $sinCarga);
        usort($resultado, fn($a, $b) => $b['total'] <=> $a['total']);
        
        return $resultado;
    }
}
