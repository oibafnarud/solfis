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
    header('Location: subscribers.php?message=subscriber-error');
    exit;
}

// Activar suscriptor
$subscriber = new Subscriber();
$result = $subscriber->changeStatus($_GET['id'], 'active');

// Redireccionar según resultado
if ($result) {
    header('Location: subscribers.php?message=subscriber-activated');
} else {
    header('Location: subscribers.php?message=subscriber-error');
}
exit;

?>