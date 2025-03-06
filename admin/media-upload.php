<?php
// Inicializar sesión
session_start();

// Incluir archivos necesarios
require_once '../config.php';
require_once '../includes/blog-system.php';

// Verificar autenticación
$auth = Auth::getInstance();
if (!$auth->isLoggedIn()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

// Verificar que se haya subido un archivo
if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'No se ha recibido ningún archivo válido.']);
    exit;
}

// Procesar subida
$media = new Media();
$result = $media->uploadImage($_FILES['image']);

// Devolver resultado en formato JSON
header('Content-Type: application/json');
echo json_encode($result);
exit;


?>