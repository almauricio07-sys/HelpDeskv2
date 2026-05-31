<?php
/**
 * Modelo: Usuario
 *
 * Gestiona operaciones CRUD de usuarios del sistema,
 * incluyendo autenticación y gestión de roles.
 *
 * Sistema de Mesa de Ayuda - Los Bélicos
 */
class Usuario {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    // ══════════════════════════════════════════════════════════════════════
    //  AUTENTICACIÓN
    // ══════════════════════════════════════════════════════════════════════

    public function buscarPorClave(string $claveAcceso): array|false {
        $stmt = $this->db->prepare(
            "SELECT u.id, u.clave_acceso, u.nombre_completo, u.password_hash,
                    u.id_rol, u.estado,
                    r.nombre_rol
             FROM usuarios u
             INNER JOIN roles r ON u.id_rol = r.id
             WHERE u.clave_acceso = ? AND u.estado = 'activo'
             LIMIT 1"
        );
        $stmt->execute([$claveAcceso]);
        return $stmt->fetch();
    }

    public function buscarPorCorreo(string $correo): array|false {
        $stmt = $this->db->prepare(
            "SELECT u.*, r.nombre_rol
             FROM usuarios u
             INNER JOIN roles r ON u.id_rol = r.id
             WHERE u.correo_institucional = ?
             LIMIT 1"
        );
        $stmt->execute([$correo]);
        return $stmt->fetch();
    }

    // ══════════════════════════════════════════════════════════════════════
    //  RF_13 — CATÁLOGO DE USUARIOS
    // ══════════════════════════════════════════════════════════════════════

    /**
     * Obtiene todos los usuarios con filtros opcionales.
     * Soporta: nombre (LIKE), id_rol, estado (activo/inactivo).
     */
    public function obtenerTodos(array $filtros = []): array {
        $sql = "SELECT u.id, u.clave_acceso, u.nombre_completo,
                       u.correo_institucional, u.estado,
                       r.nombre_rol, r.id AS id_rol
                FROM usuarios u
                INNER JOIN roles r ON u.id_rol = r.id
                WHERE 1=1";
        $params = [];

        if (!empty($filtros['id_rol'])) {
            $sql .= " AND u.id_rol = ?";
            $params[] = (int) $filtros['id_rol'];
        }

        if (!empty($filtros['nombre'])) {
            $sql .= " AND u.nombre_completo LIKE ?";
            $params[] = '%' . $filtros['nombre'] . '%';
        }

        if (!empty($filtros['estado']) && in_array($filtros['estado'], ['activo', 'inactivo'])) {
            $sql .= " AND u.estado = ?";
            $params[] = $filtros['estado'];
        }

        $sql .= " ORDER BY u.nombre_completo ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function obtenerPorId(int $id): array|false {
        $stmt = $this->db->prepare(
            "SELECT u.*, r.nombre_rol
             FROM usuarios u
             INNER JOIN roles r ON u.id_rol = r.id
             WHERE u.id = ? LIMIT 1"
        );
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    // ══════════════════════════════════════════════════════════════════════
    //  RF_11 — CREAR USUARIO
    // ══════════════════════════════════════════════════════════════════════

    public function crear(
        string $claveAcceso,
        string $nombreCompleto,
        string $correo,
        string $password,
        int    $idRol
    ): int {
        $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

        $stmt = $this->db->prepare(
            "INSERT INTO usuarios
                (clave_acceso, nombre_completo, correo_institucional, password_hash, id_rol, estado)
             VALUES (?, ?, ?, ?, ?, 'activo')"
        );
        $stmt->execute([$claveAcceso, $nombreCompleto, $correo, $hash, $idRol]);
        return (int) $this->db->lastInsertId();
    }

    // ══════════════════════════════════════════════════════════════════════
    //  RF_12 — EDITAR USUARIO
    // ══════════════════════════════════════════════════════════════════════

    public function actualizar(
        int    $id,
        string $nombreCompleto,
        string $correo,
        int    $idRol,
        string $estado
    ): bool {
        $stmt = $this->db->prepare(
            "UPDATE usuarios
             SET nombre_completo      = ?,
                 correo_institucional = ?,
                 id_rol               = ?,
                 estado               = ?
             WHERE id = ?"
        );
        return $stmt->execute([$nombreCompleto, $correo, $idRol, $estado, $id]);
    }

    public function cambiarPassword(int $id, string $nuevaPassword): bool {
        $hash = password_hash($nuevaPassword, PASSWORD_BCRYPT, ['cost' => 12]);
        $stmt = $this->db->prepare(
            "UPDATE usuarios SET password_hash = ? WHERE id = ?"
        );
        return $stmt->execute([$hash, $id]);
    }

    public function cambiarEstado(int $id, string $estado): bool {
        $stmt = $this->db->prepare(
            "UPDATE usuarios SET estado = ? WHERE id = ?"
        );
        return $stmt->execute([$estado, $id]);
    }

    // ══════════════════════════════════════════════════════════════════════
    //  VALIDACIONES DE UNICIDAD
    // ══════════════════════════════════════════════════════════════════════

    /**
     * Verifica si una clave de acceso ya existe.
     * Acepta excludeId para no fallar al editar el mismo usuario.
     */
    public function existeClave(string $clave, int $excludeId = 0): bool {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM usuarios WHERE clave_acceso = ? AND id != ?"
        );
        $stmt->execute([$clave, $excludeId]);
        return (int) $stmt->fetchColumn() > 0;
    }

    /**
     * Verifica si un correo institucional ya existe.
     * RF_11 pre-condición: el correo institucional no debe estar duplicado.
     * Acepta excludeId para la edición (RF_12).
     */
    public function existeCorreo(string $correo, int $excludeId = 0): bool {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM usuarios WHERE correo_institucional = ? AND id != ?"
        );
        $stmt->execute([$correo, $excludeId]);
        return (int) $stmt->fetchColumn() > 0;
    }

    // ══════════════════════════════════════════════════════════════════════
    //  CATÁLOGOS Y ESTADÍSTICAS
    // ══════════════════════════════════════════════════════════════════════

    public function obtenerRoles(): array {
        return $this->db->query("SELECT * FROM roles ORDER BY id")->fetchAll();
    }

    public function contarPorRol(): array {
        $stmt = $this->db->query(
            "SELECT r.nombre_rol, COUNT(u.id) AS total
             FROM roles r
             LEFT JOIN usuarios u ON u.id_rol = r.id AND u.estado = 'activo'
             GROUP BY r.id, r.nombre_rol"
        );
        return $stmt->fetchAll();
    }
}
