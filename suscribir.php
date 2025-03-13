<?php
/**
 * Procesar suscripción al newsletter
 */

// Incluir archivos necesarios
require_once 'config.php';
require_once 'includes/blog-system.php';

// Verificar que se envió el formulario
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

// Recoger datos del formulario
$email = $_POST['email'] ?? '';
$name = $_POST['name'] ?? '';

// Validar email
if (empty($email) || !Helpers::validateEmail($email)) {
    header('Location: blog.php?subscription=invalid-email');
    exit;
}

// Procesar suscripción
$subscriber = new Subscriber();
$result = $subscriber->subscribe($email, $name);

// Redireccionar según resultado
if ($result['success']) {
    header('Location: blog.php?subscription=success');
} else {
    header('Location: blog.php?subscription=error&message=' . urlencode($result['message']));
}
exit;