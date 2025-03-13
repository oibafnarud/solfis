<?php
// Inicializar sesión
session_start();

// Incluir archivos necesarios
require_once '../config.php';
require_once '../includes/blog-system.php';

// Verificar autenticación
$auth = Auth::getInstance();
if (!$auth->isLoggedIn() || !$auth->isAdmin()) {
    header('Location: login.php');
    exit;
}

// Verificar que se haya proporcionado un ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: users.php?message=user-error');
    exit;
}

$id = (int)$_GET['id'];

// No permitir eliminar el propio usuario
if ($id === $auth->getUserId()) {
    header('Location: users.php?message=cannot-delete-self');
    exit;
}

// Eliminar usuario
$user = new User();
$result = $user->deleteUser($id);

// Redireccionar según resultado
if ($result) {
    header('Location: users.php?message=user-deleted');
} else {
    header('Location: users.php?message=user-error');
}
exit;