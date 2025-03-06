<?php
/**
 * Página para procesar suscripciones al newsletter
 */

// Incluir archivos necesarios
require_once 'config.php';
require_once 'includes/blog-system.php';

// Verificar que se hayan enviado datos por POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['email']) || empty($_POST['email'])) {
    header('Location: blog.php');
    exit;
}

// Obtener y validar el email
$email = trim($_POST['email']);
if (!Helpers::validateEmail($email)) {
    // Email no válido, redirigir con error
    header('Location: blog.php?subscription=invalid-email');
    exit;
}

// Instanciar clase Subscriber
$subscriber = new Subscriber();

// Intentar suscribir el email
$result = $subscriber->subscribe($email);

// Redirigir según resultado
if ($result['success']) {
    header('Location: blog.php?subscription=success');
} else {
    header('Location: blog.php?subscription=error&message=' . urlencode($result['message']));
}
exit;