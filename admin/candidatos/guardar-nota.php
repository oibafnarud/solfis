<?php
/**
 * Panel de Administración para SolFis
 * admin/candidatos/guardar-nota.php - Procesar guardado de nota
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

// Verificar método de petición
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

// Verificar parámetros
if (empty($_POST['candidato_id']) || empty($_POST['titulo']) || empty($_POST['contenido'])) {
    header('Location: index.php?error=missing_fields');
    exit;
}

// Obtener datos
$candidato_id = (int)$_POST['candidato_id'];
$titulo = $_POST['titulo'];
$contenido = $_POST['contenido'];

// Instanciar clases necesarias
$candidateManager = new CandidateManager();

// Agregar nota
$result = $candidateManager->addCandidateNote($candidato_id, $titulo, $contenido);

// Redireccionar según resultado
if ($result['success']) {
    header('Location: detalle.php?id=' . $candidato_id . '&tab=notas&message=note-added');
} else {
    header('Location: agregar-nota.php?id=' . $candidato_id . '&error=' . urlencode($result['message']));
}
exit;