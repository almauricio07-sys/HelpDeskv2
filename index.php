<?php
/**
 * Front Controller - Enrutador Principal
 * Sistema de Mesa de Ayuda - Los Bélicos
 * 
 * Maneja todas las rutas de la aplicación.
 * URL Format: ?controller=NombreControlador&action=NombreAccion
 */

// ─── Configuración de errores ─────────────────────────────────────────────────
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// ─── Iniciar sesión segura ─────────────────────────────────────────────────────
session_start();

// ─── Definir constantes base ──────────────────────────────────────────────────
define('BASE_PATH', __DIR__);
define('BASE_URL',  '/Mesa');

// ─── Autoloader de clases ─────────────────────────────────────────────────────
spl_autoload_register(function (string $className): void {
    $paths = [
        BASE_PATH . '/config/',
        BASE_PATH . '/app/models/',
        BASE_PATH . '/app/controllers/',
    ];
    foreach ($paths as $path) {
        $file = $path . $className . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

// ─── Obtener controlador y acción de la URL ───────────────────────────────────
$controllerName = $_GET['controller'] ?? 'Auth';
$actionName     = $_GET['action']     ?? 'loginForm';

// Sanitizar parámetros para evitar inclusión de archivos maliciosos
$controllerName = preg_replace('/[^a-zA-Z0-9]/', '', $controllerName);
$actionName     = preg_replace('/[^a-zA-Z0-9_]/', '', $actionName);

$controllerClass = ucfirst($controllerName) . 'Controller';
$controllerFile  = BASE_PATH . '/app/controllers/' . $controllerClass . '.php';

// ─── Despachar la solicitud ───────────────────────────────────────────────────
if (file_exists($controllerFile)) {
    require_once $controllerFile;

    if (class_exists($controllerClass)) {
        $controller = new $controllerClass();

        if (method_exists($controller, $actionName)) {
            $controller->$actionName();
        } else {
            http_response_code(404);
            require BASE_PATH . '/app/views/errors/404.php';
        }
    } else {
        http_response_code(404);
        require BASE_PATH . '/app/views/errors/404.php';
    }
} else {
    http_response_code(404);
    require BASE_PATH . '/app/views/errors/404.php';
}
