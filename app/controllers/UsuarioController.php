<?php
/**
 * Controlador: Usuario
 * CRUD completo de usuarios del sistema.
 * 
 * RF_11 / RF_12 / RF_13 — Solo Coordinador (Rol 1)
 * 
 * Sistema de Mesa de Ayuda - Los Bélicos
 */
class UsuarioController {

    private Usuario $modelUsuario;

    public function __construct() {
        $this->requireAuth();
        $this->requireRol([1]); // Solo coordinador
        require_once BASE_PATH . '/app/models/Usuario.php';
        $this->modelUsuario = new Usuario();
    }

    // ══════════════════════════════════════════════════════════════════════
    //  RF_13 — Catálogo de usuarios
    // ══════════════════════════════════════════════════════════════════════
    public function index(): void {
        $filtros = [
            'nombre'  => trim($_GET['nombre']  ?? ''),
            'id_rol'  => $_GET['id_rol']        ?? '',
        ];

        $usuarios  = $this->modelUsuario->obtenerTodos($filtros);
        $roles     = $this->modelUsuario->obtenerRoles();
        $pageTitle = 'Catálogo de Usuarios';

        require BASE_PATH . '/app/views/usuarios/index.php';
    }

    // ══════════════════════════════════════════════════════════════════════
    //  RF_11 — Formulario de nuevo usuario
    // ══════════════════════════════════════════════════════════════════════
    public function create(): void {
        $roles     = $this->modelUsuario->obtenerRoles();
        $pageTitle = 'Nuevo Usuario';
        $errors    = [];

        require BASE_PATH . '/app/views/usuarios/create.php';
    }

    public function store(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '/index.php?controller=Usuario&action=create');
            exit;
        }

        $claveAcceso = trim($_POST['clave_acceso']    ?? '');
        $nombre      = trim($_POST['nombre_completo'] ?? '');
        $correo      = trim($_POST['correo']          ?? '');
        $password    = $_POST['password']             ?? '';
        $password2   = $_POST['password2']            ?? '';
        $idRol       = (int) ($_POST['id_rol']        ?? 0);

        $errors = [];

        if (empty($claveAcceso))   $errors[] = 'La clave de acceso es requerida.';
        if (empty($nombre))        $errors[] = 'El nombre completo es requerido.';
        if (empty($correo) || !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'El correo institucional es inválido.';
        }
        if (strlen($password) < 8) $errors[] = 'La contraseña debe tener al menos 8 caracteres.';
        if ($password !== $password2) $errors[] = 'Las contraseñas no coinciden.';
        if ($idRol <= 0)           $errors[] = 'Selecciona un rol válido.';
        if ($this->modelUsuario->existeClave($claveAcceso)) {
            $errors[] = 'Esa clave de acceso ya está en uso.';
        }

        if (!empty($errors)) {
            $roles     = $this->modelUsuario->obtenerRoles();
            $pageTitle = 'Nuevo Usuario';
            require BASE_PATH . '/app/views/usuarios/create.php';
            return;
        }

        $this->modelUsuario->crear($claveAcceso, $nombre, $correo, $password, $idRol);
        $_SESSION['flash_success'] = "Usuario <strong>{$nombre}</strong> creado exitosamente.";
        header('Location: ' . BASE_URL . '/index.php?controller=Usuario&action=index');
        exit;
    }

    // ══════════════════════════════════════════════════════════════════════
    //  RF_12 — Editar usuario
    // ══════════════════════════════════════════════════════════════════════
    public function edit(): void {
        $id      = (int) ($_GET['id'] ?? 0);
        $usuario = $this->modelUsuario->obtenerPorId($id);

        if (!$usuario) {
            $_SESSION['flash_error'] = 'Usuario no encontrado.';
            header('Location: ' . BASE_URL . '/index.php?controller=Usuario&action=index');
            exit;
        }

        $roles     = $this->modelUsuario->obtenerRoles();
        $pageTitle = 'Editar Usuario';
        $errors    = [];

        require BASE_PATH . '/app/views/usuarios/edit.php';
    }

    public function update(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '/index.php?controller=Usuario&action=index');
            exit;
        }

        $id      = (int) ($_POST['id']              ?? 0);
        $nombre  = trim($_POST['nombre_completo']   ?? '');
        $correo  = trim($_POST['correo']            ?? '');
        $idRol   = (int) ($_POST['id_rol']          ?? 0);
        $estado  = in_array($_POST['estado'] ?? '', ['activo', 'inactivo']) ? $_POST['estado'] : 'activo';

        $errors = [];

        if (empty($nombre)) $errors[] = 'El nombre completo es requerido.';
        if (empty($correo) || !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'El correo es inválido.';
        }
        if ($idRol <= 0) $errors[] = 'Selecciona un rol válido.';

        if (!empty($errors)) {
            $usuario   = $this->modelUsuario->obtenerPorId($id);
            $roles     = $this->modelUsuario->obtenerRoles();
            $pageTitle = 'Editar Usuario';
            require BASE_PATH . '/app/views/usuarios/edit.php';
            return;
        }

        $this->modelUsuario->actualizar($id, $nombre, $correo, $idRol, $estado);

        // Cambiar contraseña si se proveyó
        $newPass = $_POST['nueva_password'] ?? '';
        if (!empty($newPass) && strlen($newPass) >= 8) {
            $this->modelUsuario->cambiarPassword($id, $newPass);
        }

        $_SESSION['flash_success'] = 'Usuario actualizado correctamente.';
        header('Location: ' . BASE_URL . '/index.php?controller=Usuario&action=index');
        exit;
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

    private function requireRol(array $roles): void {
        if (!in_array($_SESSION['rol_id'], $roles)) {
            http_response_code(403);
            require BASE_PATH . '/app/views/errors/403.php';
            exit;
        }
    }
}
