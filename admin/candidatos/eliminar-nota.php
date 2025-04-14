<?php
/**
 * Panel de Administración para SolFis
 * admin/candidatos/eliminar-nota.php - Eliminar nota de candidato
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
if (!isset($_GET['id']) || empty($_GET['id']) || !isset($_GET['candidato_id']) || empty($_GET['candidato_id'])) {
    header('Location: index.php?error=missing_params');
    exit;
}

// Obtener IDs
$id = (int)$_GET['id'];
$candidato_id = (int)$_GET['candidato_id'];

// Instanciar clases necesarias
$candidateManager = new CandidateManager();

// Verificar que el candidato existe
$candidato = $candidateManager->getCandidateById($candidato_id);
if (!$candidato) {
    header('Location: index.php?error=candidate_not_found');
    exit;
}

// Verificar que la nota existe y pertenece al candidato
$nota = $candidateManager->getNoteById($id);
if (!$nota || $nota['candidato_id'] != $candidato_id) {
    header('Location: detalle.php?id=' . $candidato_id . '&tab=notes&error=note_not_found');
    exit;
}

// Eliminar la nota
$result = $candidateManager->deleteCandidateNote($id);

// Redireccionar según resultado
if ($result['success']) {
    header('Location: detalle.php?id=' . $candidato_id . '&tab=notes&message=note-deleted');
} else {
    header('Location: detalle.php?id=' . $candidato_id . '&tab=notes&error=' . urlencode($result['message']));
}
exit;