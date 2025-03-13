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

// Obtener datos actuales del usuario
$user = new User();
$userId = (int)$_POST['id'];
$currentUser = $user->getUserById($userId);

if (!$currentUser) {
    header('Location: users.php?message=user-not-found');
    exit;
}

// Recoger datos del formulario
$name = $_POST['name'] ?? '';
$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';
$role = $_POST['role'] ?? $currentUser['role']; // Usar el rol actual si no se proporciona

if (empty($name) || empty($email)) {
    header('Location: users.php?message=invalid-data');
    exit;
}

if (!Helpers::validateEmail($email)) {
    header('Location: users.php?message=invalid-email');
    exit;
}

// Datos para actualización
$updateData = [
    'name' => $name,
    'email' => $email,
    'role' => $role // Incluir el rol explícitamente
];

// Actualizar usuario (solo info básica)
$result = $user->updateUser($userId, $updateData);

// Actualizar contraseña SOLO si se proporcionó una nueva
if (!empty($password)) {
    if (strlen($password) < 6) {
        header('Location: users.php?message=password-too-short');
        exit;
    }
    
    // Llamar al método específico que solo actualiza la contraseña
    $passwordResult = $user->changePassword($userId, $password);
    
    // El resultado final depende de ambas operaciones
    $result = $result && $passwordResult;
}

// Redireccionar según resultado
if ($result) {
    header('Location: users.php?message=user-updated');
} else {
    header('Location: users.php?message=user-error');
}
exit;