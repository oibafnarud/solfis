<?php
/**
 * Archivo de configuración para el sistema de blog SolFis
 * 
 * Este archivo contiene todas las configuraciones globales del sistema,
 * incluyendo credenciales de base de datos y rutas.
 */

// Configuración de la base de datos
define('DB_HOST', 'localhost');        // Host de la base de datos
define('DB_USER', 'solfisco_onarud');             // Usuario de la base de datos
define('DB_PASS', 'jr010101@');                 // Contraseña de la base de datos
define('DB_NAME', 'solfisco_website');      // Nombre de la base de datos

// Rutas del sistema
$base_url = 'http://' . $_SERVER['HTTP_HOST'] . '/solfis';
define('SITE_URL', $base_url);         // URL del sitio
define('ADMIN_URL', SITE_URL . '/admin'); // URL del panel de administración
define('UPLOADS_DIR', 'img/blog/uploads/'); // Directorio para subir archivos

// Configuración del sitio
define('SITE_NAME', 'SolFis');         // Nombre del sitio
define('SITE_DESCRIPTION', 'Soluciones fiscales y contables'); // Descripción del sitio
define('POSTS_PER_PAGE', 6);           // Número de posts por página en el blog
define('COMMENTS_PER_PAGE', 10);       // Número de comentarios por página
define('ENABLE_COMMENTS', true);       // Habilitar/deshabilitar comentarios
define('REQUIRE_COMMENT_APPROVAL', true); // Requerir aprobación de comentarios

// Zona horaria
date_default_timezone_set('America/Santo_Domingo');

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

// Crear directorio de uploads si no existe
if (!file_exists(UPLOADS_DIR)) {
    mkdir(UPLOADS_DIR, 0755, true);
}
?>