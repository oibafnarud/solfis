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
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['id'])) {
    header('Location: users.php');
    exit;
}

// Validar datos
$id = (int)$_POST['id'];
$name = $_POST['name'] ?? '';
$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';
$role = $_POST['role'] ?? 'author';

if (empty($name) || empty($email)) {
    header('Location: users.php?message=invalid-data');
    exit;
}

if (!Helpers::validateEmail($email)) {
    header('Location: users.php?message=invalid-email');
    exit;
}

// Actualizar usuario
$user = new User();
$data = [
    'name' => $name,
    'email' => $email,
    'role' => $role
];

$result = $user->updateUser($id, $data);

// Actualizar contraseña si se proporcionó una nueva
if (!empty($password)) {
    if (strlen($password) < 6) {
        header('Location: users.php?message=password-too-short');
        exit;
    }
    
    $user->changePassword($id, $password);
}

// Redireccionar según resultado
if ($result) {
    header('Location: users.php?message=user-updated');
} else {
    header('Location: users.php?message=user-error');
}
exit;