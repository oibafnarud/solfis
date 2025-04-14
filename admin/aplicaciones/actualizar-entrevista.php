<?php
/**
 * Panel de Administración para SolFis
 * admin/aplicaciones/actualizar-entrevista.php - Actualizar estado de una entrevista
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

// Verificar parámetros
if (!isset($_GET['id']) || !isset($_GET['estado'])) {
    header('Location: index.php?error=missing_parameters');
    exit;
}

$id = (int)$_GET['id'];
$estado = $_GET['estado'];
$redirect = isset($_GET['redirect']) ? $_GET['redirect'] : 'index.php';

// Validar estado
$estados_validos = ['pendiente', 'completada', 'cancelada'];
if (!in_array($estado, $estados_validos)) {
    header('Location: ' . $redirect . '&error=invalid_status');
    exit;
}

// Instanciar clases necesarias
$applicationManager = new ApplicationManager();

// Actualizar estado de la entrevista
$result = false;
if (method_exists($applicationManager, 'updateInterviewStatus')) {
    $result = $applicationManager->updateInterviewStatus($id, $estado);
} else {
    // Implementación alternativa si el método no existe
    $sql = "UPDATE etapas_proceso SET estado = '" . $applicationManager->db->escape($estado) . "', updated_at = NOW() WHERE id = $id";
    $result = $applicationManager->db->query($sql);
}

// Redireccionar
header('Location: ' . $redirect . ($result ? '&message=interview-updated' : '&error=update-failed'));
exit;