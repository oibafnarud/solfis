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
    header('Location: categories.php?message=category-error');
    exit;
}

// Eliminar categoría
$category = new Category();
$result = $category->deleteCategory($_GET['id']);

// Redireccionar según resultado
if ($result) {
    header('Location: categories.php?message=category-deleted');
} else {
    header('Location: categories.php?message=category-error');
}
exit;