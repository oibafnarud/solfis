<?php
/**
 * Script para obtener datos de una referencia por AJAX
 */
session_start();

// Verificar que el usuario esté autenticado como candidato
if (!isset($_SESSION['candidato_id'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

// Verificar que se proporcionó un ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'ID de referencia no proporcionado']);
    exit;
}

// Incluir archivos necesarios
require_once '../includes/jobs-system.php';

// Obtener ID de referencia
$referencia_id = (int)$_GET['id'];
$candidato_id = (int)$_SESSION['candidato_id'];

// Conexión a base de datos
$db = Database::getInstance();

// Verificar que la referencia pertenece al candidato
$sql = "SELECT * FROM referencias WHERE id = $referencia_id AND candidato_id = $candidato_id";
$result = $db->query($sql);

if (!$result || $result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Referencia no encontrada o no pertenece a este candidato']);
    exit;
}

$referencia = $result->fetch_assoc();

// Devolver datos en formato JSON
echo json_encode(['success' => true, 'referencia' => $referencia]);