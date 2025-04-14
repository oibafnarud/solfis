<?php
/**
 * Panel de Administración para SolFis
 * admin/aplicaciones/guardar-nota.php - Guardar notas para una aplicación
 */

// Inicializar sesión
session_start();

// Incluir archivos necesarios
require_once '../config.php';
require_once '../../includes/blog-system.php';
require_once '../../includes/jobs-system.php';

// Verificar autenticación
$auth = Auth::getInstance();
if (!$auth->isLoggedIn()) {
    header('Location: ../login.php');
    exit;
}

// Verificar método de solicitud
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

// Verificar parámetros requeridos
if (!isset($_POST['id']) || !isset($_POST['notas_internas'])) {
    header('Location: index.php?message=error');
    exit;
}

// Obtener datos del formulario
$id = (int)$_POST['id'];
$notas_internas = $_POST['notas_internas'];

// Instanciar gestor de aplicaciones
$applicationManager = new ApplicationManager();

// Obtener aplicación actual
$aplicacion = $applicationManager->getApplicationById($id);
if (!$aplicacion) {
    header('Location: index.php?message=error');
    exit;
}

// Preparar datos para actualizar
$data = [
    'notas_internas' => $notas_internas
];

// Actualizar aplicación
$result = $applicationManager->updateApplication($id, $data);

// Registrar en historial si existe el método
if ($result && method_exists($applicationManager, 'addApplicationHistory')) {
    $usuario_id = $auth->getUserId();
    $comentario = "Se han actualizado las notas internas";
    $applicationManager->addApplicationHistory($id, $aplicacion['estado'], $aplicacion['estado'], $comentario, $usuario_id);
}

// Redirigir según resultado
if ($result) {
    header("Location: detalle.php?id={$id}&message=note-added");
} else {
    header("Location: detalle.php?id={$id}&message=error");
}
exit;
?>