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

// Verificar que se haya enviado el formulario
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: users.php');
    exit;
}

// Validar datos
$name = $_POST['name'] ?? '';
$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';
$role = $_POST['role'] ?? 'author';

if (empty($name) || empty($email) || empty($password)) {
    header('Location: users.php?message=invalid-data');
    exit;
}

if (!Helpers::validateEmail($email)) {
    header('Location: users.php?message=invalid-email');
    exit;
}

if (strlen($password) < 6) {
    header('Location: users.php?message=password-too-short');
    exit;
}

// Crear usuario
$user = new User();
$data = [
    'name' => $name,
    'email' => $email,
    'password' => $password,
    'role' => $role
];

$result = $user->createUser($data);

// Redireccionar según resultado
if ($result) {
    header('Location: users.php?message=user-created');
} else {
    header('Location: users.php?message=user-error');
}
exit;