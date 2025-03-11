<?php
// Inicializar sesión
session_start();

// Incluir archivos necesarios
require_once '../config.php';
require_once '../includes/blog-system.php';

// Verificar autenticación
$auth = Auth::getInstance();
if (!$auth->isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// Conectar a la base de datos
$db = Database::getInstance();

// Corregir rutas en la tabla media
$sql = "UPDATE media SET path = REPLACE(path, '../img/', 'img/') WHERE path LIKE '../img/%'";
$result1 = $db->query($sql);

// Corregir rutas en la tabla posts
$sql = "UPDATE posts SET image = REPLACE(image, '../img/', 'img/') WHERE image LIKE '../img/%'";
$result2 = $db->query($sql);

echo "Corrección de rutas completada.<br>";
echo "Registros actualizados en media: " . $db->getConnection()->affected_rows . "<br>";
echo "Registros actualizados en posts: " . $db->getConnection()->affected_rows . "<br>";
?>