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

// Verificar que se haya enviado el formulario
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['email']) || empty($_POST['email'])) {
    header('Location: subscribers.php?message=subscriber-error');
    exit;
}

// Validar email
if (!Helpers::validateEmail($_POST['email'])) {
    header('Location: subscribers.php?message=invalid-email');
    exit;
}

// Agregar suscriptor
$subscriber = new Subscriber();
$result = $subscriber->subscribe($_POST['email'], $_POST['name'] ?? null);

// Redireccionar según resultado
if ($result['success']) {
    header('Location: subscribers.php?message=subscriber-added');
} else {
    header('Location: subscribers.php?message=subscriber-error');
}
exit;

?>