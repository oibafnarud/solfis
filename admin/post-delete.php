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
    header('Location: posts.php?message=post-error');
    exit;
}

// Eliminar post
$blogPost = new BlogPost();
$result = $blogPost->deletePost($_GET['id']);

// Redireccionar según resultado
if ($result) {
    header('Location: posts.php?message=post-deleted');
} else {
    header('Location: posts.php?message=post-error');
}
exit;

?>