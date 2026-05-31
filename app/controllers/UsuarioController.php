<?php
/**
 * Controlador: Usuario
 * CRUD completo de usuarios del sistema.
 *
 * RF_11 — Registrar nuevos usuarios
 * RF_12 — Modificar información de perfiles existentes
 * RF_13 — Consultar catálogo de usuarios activos
 *
 * Acceso restringido exclusivamente al rol Coordinador (Rol 1).
 *
 * Sistema de Mesa de Ayuda - Los Bélicos
 */
class UsuarioController {

    private Usuario $modelUsuario;

    public function __construct() {
        $this->requireAuth();
        $this->requireRol([1]);
        require_once BASE_PATH . '/app/models/Usuario.php';
        $this->modelUsuario = new Usuario();
    }

    // ══════════════════════════════════════════════════════════════════════
    //  RF_13 — Catálogo de usuarios
    // ══════════════════════════════════════════════════════════════════════

    public function index(): void {
        $filtros = [
            'nombre' => trim($_GET['nombre'] ?? ''),
            'id_rol' => $_GET['id_rol']      ?? '',
            'estado' => $_GET['estado']      ?? '',
        ];

        $usuarios  = $this->modelUsuario->obtenerTodos($filtros);
        $roles     = $this->modelUsuario->obtenerRoles();
        $pageTitle = 'Catálogo de Usuarios';

        require BASE_PATH . '/app/views/usuarios/index.php';
    }

    // ══════════════════════════════════════════════════════════════════════
    //  RF_11 — Registrar nuevo usuario
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

        // ── Bloque 1: validaciones de formato ──────────────────────────
        if (empty($claveAcceso)) {
            $errors[] = 'La clave de acceso es requerida.';
        }
        if (empty($nombre)) {
            $errors[] = 'El nombre completo es requerido.';
        }
        if (empty($correo) || !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'El correo institucional es inválido.';
        }
        if (strlen($password) < 8) {
            $errors[] = 'La contraseña debe tener al menos 8 caracteres.';
        }
        if ($password !== $password2) {
            $errors[] = 'Las contraseñas no coinciden.';
        }
        if ($idRol <= 0) {
            $errors[] = 'Selecciona un rol válido.';
        }

        // ── Bloque 2: unicidad en BD (solo si el campo pasó validación básica) ─
        if (!empty($claveAcceso) && $this->modelUsuario->existeClave($claveAcceso)) {
            $errors[] = 'Esa clave de acceso ya está en uso por otro usuario.';
        }
        // RF_11 pre-condición: verificar correo no duplicado.
        if (!empty($correo) && filter_var($correo, FILTER_VALIDATE_EMAIL)
            && $this->modelUsuario->existeCorreo($correo)) {
            $errors[] = 'Ese correo institucional ya está registrado en el sistema.';
        }

        if (!empty($errors)) {
            $roles     = $this->modelUsuario->obtenerRoles();
            $pageTitle = 'Nuevo Usuario';
            require BASE_PATH . '/app/views/usuarios/create.php';
            return;
        }

        $this->modelUsuario->crear($claveAcceso, $nombre, $correo, $password, $idRol);
        $_SESSION['flash_success'] = 'Usuario <strong>' . htmlspecialchars($nombre) . '</strong> creado exitosamente.';
        header('Location: ' . BASE_URL . '/index.php?controller=Usuario&action=index');
        exit;
    }

    // ══════════════════════════════════════════════════════════════════════
    //  RF_12 — Modificar información de perfil existente
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

        $id       = (int) ($_POST['id']             ?? 0);
        $nombre   = trim($_POST['nombre_completo']  ?? '');
        $correo   = trim($_POST['correo']           ?? '');
        $idRol    = (int) ($_POST['id_rol']         ?? 0);
        $estado   = in_array($_POST['estado'] ?? '', ['activo', 'inactivo'])
                    ? $_POST['estado'] : 'activo';
        $newPass  = $_POST['nueva_password']        ?? '';
        $newPass2 = $_POST['nueva_password2']       ?? '';

        $errors = [];

        // ── Bloque 1: validaciones de formato ──────────────────────────
        if (empty($nombre)) {
            $errors[] = 'El nombre completo es requerido.';
        }
        if (empty($correo) || !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'El correo institucional es inválido.';
        }
        if ($idRol <= 0) {
            $errors[] = 'Selecciona un rol válido.';
        }

        // ── Bloque 2: unicidad de correo, excluyendo al propio usuario ─
        if (!empty($correo) && filter_var($correo, FILTER_VALIDATE_EMAIL)
            && $this->modelUsuario->existeCorreo($correo, $id)) {
            $errors[] = 'Ese correo institucional ya está en uso por otro usuario.';
        }

        // ── Bloque 3: contraseña nueva (opcional) ──────────────────────
        if (!empty($newPass)) {
            if (strlen($newPass) < 8) {
                $errors[] = 'La nueva contraseña debe tener al menos 8 caracteres.';
            } elseif ($newPass !== $newPass2) {
                $errors[] = 'Las nuevas contraseñas no coinciden.';
            }
        }

        // ── Re-render con errores ───────────────────────────────────────
        if (!empty($errors)) {
            // Se restauran los valores del POST en $usuario para que el formulario
            // muestre exactamente lo que el usuario intentó guardar, no los datos originales.
            $usuario = $this->modelUsuario->obtenerPorId($id);
            $usuario['nombre_completo']      = $nombre;
            $usuario['correo_institucional'] = $correo;
            $usuario['id_rol']               = $idRol;
            $usuario['estado']               = $estado;

            $roles     = $this->modelUsuario->obtenerRoles();
            $pageTitle = 'Editar Usuario';
            require BASE_PATH . '/app/views/usuarios/edit.php';
            return;
        }

        // ── Persistencia ───────────────────────────────────────────────
        $this->modelUsuario->actualizar($id, $nombre, $correo, $idRol, $estado);

        if (!empty($newPass)) {
            $this->modelUsuario->cambiarPassword($id, $newPass);
        }

        // ── RF_12 post-condición RN_01: sincronizar sesión activa ──────
        // Si el perfil editado es el del propio Coordinador en sesión,
        // actualizamos $_SESSION de inmediato para reflejar los nuevos datos.
        if ($id === (int) $_SESSION['user_id']) {
            $rolActualizado         = $this->modelUsuario->obtenerPorId($id);
            $_SESSION['nombre']     = $nombre;
            $_SESSION['rol_id']     = $idRol;
            $_SESSION['rol_nombre'] = $rolActualizado['nombre_rol'] ?? $_SESSION['rol_nombre'];
        }

        $_SESSION['flash_success'] = 'Perfil actualizado con éxito.';
        header('Location: ' . BASE_URL . '/index.php?controller=Usuario&action=index');
        exit;
    }

    // ──────────────────────────────────────────────────────────────────────
    //  Helpers de seguridad
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
