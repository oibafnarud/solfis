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

// Obtener datos del formulario
$email = $_POST['email'];
$name = $_POST['name'] ?? '';
$status = $_POST['status'] ?? 'active';

// Agregar suscriptor
$subscriber = new Subscriber();

// Si se proporcionó un ID, es una edición
if (isset($_POST['id']) && !empty($_POST['id'])) {
    $id = (int)$_POST['id'];
    $result = $subscriber->updateSubscriber($id, $email, $name, $status);
    
    if ($result) {
        header('Location: subscribers.php?message=subscriber-updated');
    } else {
        header('Location: subscribers.php?message=subscriber-error');
    }
} else {
    // Es una nueva suscripción
    $result = $subscriber->subscribe($email, $name);
    
    if ($result['success']) {
        header('Location: subscribers.php?message=subscriber-added');
    } else {
        header('Location: subscribers.php?message=subscriber-error');
    }
}
exit;