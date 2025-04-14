<?php
/**
 * Panel de Administración para SolFis
 * admin/candidatos/actualizar-estado.php - Actualizar estado de aplicación directamente
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
if (!isset($_GET['id']) || empty($_GET['id']) || !isset($_GET['estado'])) {
    header('Location: index.php');
    exit;
}

$aplicacion_id = (int)$_GET['id'];
$nuevo_estado = $_GET['estado'];
$candidato_id = isset($_GET['candidato_id']) ? (int)$_GET['candidato_id'] : 0;

// Validar estado
$estados_validos = ['recibida', 'revision', 'entrevista', 'prueba', 'oferta', 'contratado', 'rechazado'];
if (!in_array($nuevo_estado, $estados_validos)) {
    header('Location: ' . (isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'index.php'));
    exit;
}

// Instanciar clases necesarias
$applicationManager = new ApplicationManager();

// Actualizar estado
$result = $applicationManager->updateApplicationStatus($aplicacion_id, $nuevo_estado);

// Determinar redirección
$redirect = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'index.php';

// Si venimos de la página de detalle de candidato, asegurarse de volver allí
if ($candidato_id > 0) {
    $redirect = '../candidatos/detalle.php?id=' . $candidato_id . '&tab=aplicaciones&message=status-updated';
}

// Redireccionar
header('Location: ' . $redirect);
exit;