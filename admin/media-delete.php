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

// Verificar que se haya proporcionado un ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: media.php?message=media-error');
    exit;
}

// Eliminar imagen
$media = new Media();
$result = $media->deleteImage($_GET['id']);

// Redireccionar según resultado
if ($result) {
    header('Location: media.php?message=media-deleted');
} else {
    header('Location: media.php?message=media-error');
}
exit;

?>