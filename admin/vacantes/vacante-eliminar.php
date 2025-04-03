<?php
/**
 * Panel de Administración para SolFis
 * admin/vacantes/vacante-eliminar.php - Eliminar vacante
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

// Verificar que se haya proporcionado un ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: index.php?message=error');
    exit;
}

// Eliminar vacante
$id = (int)$_GET['id'];
$vacancyManager = new VacancyManager();
$result = $vacancyManager->deleteVacancy($id);

// Redireccionar según resultado
if ($result) {
    header('Location: index.php?message=vacante-deleted');
} else {
    header('Location: index.php?message=error');
}
exit;
?>