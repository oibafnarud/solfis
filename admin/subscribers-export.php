<?php
// Inicializar sesión
session_start();

// Incluir archivos necesarios
require_once '../config.php';
require_once '../includes/blog-system.php';

// Verificar autenticación
$auth = Auth::getInstance();
if (!$auth->isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// Verificar que se haya enviado el formulario
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: subscribers.php');
    exit;
}

// Obtener parámetros
$status = $_POST['export_status'] ?? 'active';
$format = $_POST['export_format'] ?? 'csv';

// Obtener todos los suscriptores (sin paginación)
$db = Database::getInstance();
$sql = "SELECT email, name, status, created_at FROM subscribers";

if ($status !== 'all') {
    $status = $db->escape($status);
    $sql .= " WHERE status = '$status'";
}

$sql .= " ORDER BY created_at DESC";

$result = $db->query($sql);
$subscribers = [];

while ($row = $result->fetch_assoc()) {
    $subscribers[] = $row;
}

// Exportar según formato
if ($format === 'csv') {
    // Generar CSV
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="suscriptores_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    // Encabezados
    fputcsv($output, ['Email', 'Nombre', 'Estado', 'Fecha de Suscripción']);
    
    // Datos
    foreach ($subscribers as $subscriber) {
        fputcsv($output, [
            $subscriber['email'],
            $subscriber['name'],
            $subscriber['status'],
            $subscriber['created_at']
        ]);
    }
    
    fclose($output);
} else {
    // No implementado: exportación a Excel
    header('Location: subscribers.php?message=format-not-supported');
}

exit;

?>