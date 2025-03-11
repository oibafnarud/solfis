<?php
// Inicializar sesión
session_start();

// Incluir archivos necesarios
require_once '../config.php';
require_once '../includes/blog-system.php';

// Verificar autenticación
$auth = Auth::getInstance();
if (!$auth->isLoggedIn()) {
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'No autorizado']);
    } else {
        header('Location: login.php');
    }
    exit;
}

// Verificar que se haya subido un archivo
if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'No se ha recibido ningún archivo válido.']);
    } else {
        $_SESSION['error_message'] = 'No se ha recibido ningún archivo válido.';
        header('Location: media.php');
    }
    exit;
}

// Procesar subida
$media = new Media();
$result = $media->uploadImage($_FILES['image']);

// Devolver resultado según el tipo de solicitud
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
    header('Content-Type: application/json');
    echo json_encode($result);
} else {
    if ($result['success']) {
        $_SESSION['success_message'] = 'Imagen subida correctamente.';
        header('Location: media.php?message=media-uploaded');
    } else {
        $_SESSION['error_message'] = $result['message'];
        header('Location: media.php?message=media-error');
    }
}
exit;