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

    /**
     * Busca un usuario por su clave de acceso para autenticación.
     */
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

    /**
     * Busca usuario por correo institucional.
     */
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

    /**
     * Obtiene todos los usuarios con su rol.
     * Filtro opcional por rol.
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

        $sql .= " ORDER BY u.nombre_completo";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Obtiene un usuario por ID.
     */
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

    /**
     * Crea un nuevo usuario. La contraseña se hashea con bcrypt.
     */
    public function crear(
        string $claveAcceso,
        string $nombreCompleto,
        string $correo,
        string $password,
        int    $idRol
    ): int {
        $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

        $stmt = $this->db->prepare(
            "INSERT INTO usuarios (clave_acceso, nombre_completo, correo_institucional, password_hash, id_rol, estado)
             VALUES (?, ?, ?, ?, ?, 'activo')"
        );
        $stmt->execute([$claveAcceso, $nombreCompleto, $correo, $hash, $idRol]);
        return (int) $this->db->lastInsertId();
    }

    /**
     * Actualiza datos de un usuario (sin cambiar contraseña).
     */
    public function actualizar(
        int    $id,
        string $nombreCompleto,
        string $correo,
        int    $idRol,
        string $estado
    ): bool {
        $stmt = $this->db->prepare(
            "UPDATE usuarios
             SET nombre_completo = ?, correo_institucional = ?,
                 id_rol = ?, estado = ?
             WHERE id = ?"
        );
        return $stmt->execute([$nombreCompleto, $correo, $idRol, $estado, $id]);
    }

    /**
     * Cambia la contraseña de un usuario.
     */
    public function cambiarPassword(int $id, string $nuevaPassword): bool {
        $hash = password_hash($nuevaPassword, PASSWORD_BCRYPT, ['cost' => 12]);
        $stmt = $this->db->prepare(
            "UPDATE usuarios SET password_hash = ? WHERE id = ?"
        );
        return $stmt->execute([$hash, $id]);
    }

    /**
     * Cambia el estado de un usuario (activo/inactivo).
     */
    public function cambiarEstado(int $id, string $estado): bool {
        $stmt = $this->db->prepare(
            "UPDATE usuarios SET estado = ? WHERE id = ?"
        );
        return $stmt->execute([$estado, $id]);
    }

    /**
     * Verifica si una clave de acceso ya existe.
     */
    public function existeClave(string $clave, int $excludeId = 0): bool {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM usuarios WHERE clave_acceso = ? AND id != ?"
        );
        $stmt->execute([$clave, $excludeId]);
        return (int) $stmt->fetchColumn() > 0;
    }

    /**
     * Retorna todos los roles disponibles.
     */
    public function obtenerRoles(): array {
        return $this->db->query("SELECT * FROM roles ORDER BY id")->fetchAll();
    }

    /**
     * Retorna total de usuarios activos por rol.
     */
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
