<?php
/**
 * Panel de Administración para SolFis
 * admin/aplicaciones/cambiar-estado.php - Cambiar estado de una aplicación
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

// Verificar método y parámetros
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['id']) || empty($_POST['estado'])) {
    header('Location: index.php?error=invalid_request');
    exit;
}

// Obtener datos
$id = (int)$_POST['id'];
$estado = $_POST['estado'];
$notas = $_POST['notas'] ?? '';
$from = $_POST['from'] ?? '';
$candidato_id = isset($_POST['candidato_id']) ? (int)$_POST['candidato_id'] : 0;

// Instanciar el gestor de aplicaciones
$applicationManager = new ApplicationManager();

// Cambiar estado
$result = $applicationManager->updateApplicationStatus($id, $estado, $notas);

// Determinar la redirección
if ($from === 'candidato' && $candidato_id > 0) {
    // Redirigir a la página del candidato
    header('Location: ../candidatos/detalle.php?id=' . $candidato_id . '&tab=applications&message=status-updated');
} else {
    // Redirigir a la página de detalles de aplicación
    header('Location: detalle.php?id=' . $id . '&message=status-updated');
}
exit;