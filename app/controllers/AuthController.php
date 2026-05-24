<?php
/**
 * Controlador: Auth
 * Maneja login y logout del sistema.
 * 
 * Sistema de Mesa de Ayuda - Los Bélicos
 */
class AuthController {

    private Usuario $modelUsuario;

    public function __construct() {
        require_once BASE_PATH . '/app/models/Usuario.php';
        $this->modelUsuario = new Usuario();
    }

    // ─────────────────────────────────────────────────────────────────────
    //  GET: Mostrar formulario de login
    // ─────────────────────────────────────────────────────────────────────
    public function loginForm(): void {
        // Si ya hay sesión activa, redirigir al dashboard
        if (isset($_SESSION['user_id'])) {
            $this->redirigirPorRol($_SESSION['rol_id']);
        }

        $error = $_SESSION['login_error'] ?? null;
        unset($_SESSION['login_error']);

        require BASE_PATH . '/app/views/auth/login.php';
    }

    // ─────────────────────────────────────────────────────────────────────
    //  POST: Procesar login
    // ─────────────────────────────────────────────────────────────────────
    public function login(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '/index.php?controller=Auth&action=loginForm');
            exit;
        }

        $clave    = trim($_POST['clave_acceso'] ?? '');
        $password = $_POST['password']          ?? '';

        // Validación básica
        if (empty($clave) || empty($password)) {
            $_SESSION['login_error'] = 'Por favor completa todos los campos.';
            header('Location: ' . BASE_URL . '/index.php?controller=Auth&action=loginForm');
            exit;
        }

        $usuario = $this->modelUsuario->buscarPorClave($clave);

        if (!$usuario || !password_verify($password, $usuario['password_hash'])) {
            // Simular tiempo para evitar timing attacks
            usleep(random_int(100000, 300000));
            $_SESSION['login_error'] = 'Credenciales incorrectas. Verifica tu clave y contraseña.';
            header('Location: ' . BASE_URL . '/index.php?controller=Auth&action=loginForm');
            exit;
        }

        // ── Sesión exitosa ─────────────────────────────────────────────
        // Regenerar ID de sesión para prevenir Session Fixation
        session_regenerate_id(true);

        $_SESSION['user_id']   = $usuario['id'];
        $_SESSION['clave']     = $usuario['clave_acceso'];
        $_SESSION['nombre']    = $usuario['nombre_completo'];
        $_SESSION['rol_id']    = (int) $usuario['id_rol'];
        $_SESSION['rol_nombre'] = $usuario['nombre_rol'];
        $_SESSION['login_time'] = time();

        $this->redirigirPorRol((int) $usuario['id_rol']);
    }

    // ─────────────────────────────────────────────────────────────────────
    //  GET: Logout
    // ─────────────────────────────────────────────────────────────────────
    public function logout(): void {
        $_SESSION = [];

        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(), '', time() - 42000,
                $params["path"],   $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }

        session_destroy();
        header('Location: ' . BASE_URL . '/index.php?controller=Auth&action=loginForm');
        exit;
    }

    // ─────────────────────────────────────────────────────────────────────
    //  Helpers
    // ─────────────────────────────────────────────────────────────────────
    private function redirigirPorRol(int $rolId): void {
        header('Location: ' . BASE_URL . '/index.php?controller=Dashboard&action=index');
        exit;
    }
}
