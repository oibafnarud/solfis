<?php
// Inicializar sesión
session_start();

// Incluir archivos necesarios
require_once '../config.php';
require_once '../includes/blog-system.php';

// Cerrar sesión
$auth = Auth::getInstance();
$auth->logout();

// Redireccionar a login
header('Location: login.php');
exit;

?>