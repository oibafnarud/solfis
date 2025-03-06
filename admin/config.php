<?php
/**
 * Archivo de configuración para el sistema de blog SolFis
 * 
 * Este archivo contiene todas las configuraciones globales del sistema,
 * incluyendo credenciales de base de datos y rutas.
 */

// Configuración de la base de datos
define('DB_HOST', 'localhost');       // Host de la base de datos
define('DB_USER', 'root');     // Usuario de la base de datos
define('DB_PASS', '');   // Contraseña de la base de datos
define('DB_NAME', 'solfis_blog');     // Nombre de la base de datos

// Rutas del sistema
define('SITE_URL', 'https://solfis.com.do');  // URL del sitio (ajustar según corresponda)
define('ADMIN_URL', SITE_URL . '/admin');     // URL del panel de administración
define('UPLOADS_DIR', 'img/blog/uploads/');    // Directorio para subir archivos

// Configuración del sitio
define('SITE_NAME', 'SolFis');                // Nombre del sitio
define('SITE_DESCRIPTION', 'Soluciones fiscales y contables'); // Descripción del sitio
define('POSTS_PER_PAGE', 6);                  // Número de posts por página en el blog
define('COMMENTS_PER_PAGE', 10);              // Número de comentarios por página
define('ENABLE_COMMENTS', true);              // Habilitar/deshabilitar comentarios
define('REQUIRE_COMMENT_APPROVAL', true);     // Requerir aprobación de comentarios

// Zona horaria
date_default_timezone_set('America/Santo_Domingo');

// Manejo de errores (desactivar en producción)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Iniciar sesión si no está iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Funciones de utilidad
function redirect($url) {
    header("Location: $url");
    exit;
}

function sanitize_output($output) {
    return htmlspecialchars($output, ENT_QUOTES, 'UTF-8');
}

// Autoload de clases
function autoloadClasses($className) {
    $classFile = __DIR__ . '/includes/' . $className . '.php';
    if (file_exists($classFile)) {
        require_once $classFile;
    }
}

// Configurar autoload
spl_autoload_register('autoloadClasses');