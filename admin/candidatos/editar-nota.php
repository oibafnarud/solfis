<?php
/**
 * Panel de Administración para SolFis
 * admin/candidatos/editar-nota.php - Editar nota de candidato
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

// Verificar que se proporciona un ID de nota
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error'] = "ID de nota no proporcionado";
    header('Location: index.php');
    exit;
}

$nota_id = (int)$_GET['id'];

// Obtener datos de la nota
$db = Database::getInstance();
$nota_id = $db->real_escape_string($nota_id);
$sql = "SELECT n.*, c.id as candidato_id, c.nombre as candidato_nombre, c.apellido as candidato_apellido, 
               u.nombre as usuario_nombre 
        FROM notas_candidatos n 
        LEFT JOIN candidatos c ON n.candidato_id = c.id 
        LEFT JOIN usuarios u ON n.usuario_id = u.id 
        WHERE n.id = '$nota_id'";

$result = $db->query($sql);

if (!$result || $result->num_rows === 0) {
    $_SESSION['error'] = "Nota no encontrada";
    header('Location: index.php');
    exit;
}

// Obtener los datos de la nota
$nota = $result->fetch_assoc();
$candidato_id = $nota['candidato_id'];

// Procesar formulario de actualización
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validar datos
    $contenido = trim($_POST['contenido']);
    $tipo = isset($_POST['tipo']) ? trim($_POST['tipo']) : '';
    
    if (empty($contenido)) {
        $_SESSION['error'] = "El contenido de la nota no puede estar vacío";
    } else {
        // Actualizar nota en la base de datos
        $contenido = $db->real_escape_string($contenido);
        $tipo = $db->real_escape_string($tipo);
        $usuario_id = $auth->getUserId();
        
        $sql = "UPDATE notas_candidatos 
                SET contenido = '$contenido', 
                    tipo = '$tipo', 
                    usuario_id = '$usuario_id', 
                    updated_at = NOW() 
                WHERE id = '$nota_id'";
        
        if ($db->query($sql)) {
            $_SESSION['success'] = "Nota actualizada correctamente";
            header("Location: detalle.php?id=$candidato_id&tab=notas");
            exit;
        } else {
            $_SESSION['error'] = "Error al actualizar la nota: " . $db->error;
        }
    }
}

// Título de la página
$pageTitle = 'Editar Nota - Panel de Administración';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    
    <!-- CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="../css/admin.css">
</head>
<body>
    <!-- Header -->
    <?php include '../includes/header.php'; ?>
    
    <div class="admin-main">
        <div class="container-fluid">
            <div class="row">
                <!-- Sidebar -->
                <?php include '../includes/sidebar.php'; ?>
                
                <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                        <h1 class="h2">Editar Nota</h1>
                        <div class="btn-toolbar mb-2 mb-md-0">
                            <a href="detalle.php?id=<?php echo $candidato_id; ?>&tab=notas" class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-arrow-left"></i> Volver
                            </a>
                        </div>
                    </div>
                    
                    <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php endif; ?>
                    
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Editando nota para <?php echo htmlspecialchars($nota['candidato_nombre'] . ' ' . $nota['candidato_apellido']); ?></h5>
                        </div>
                        <div class="card-body">
                            <form action="editar-nota.php?id=<?php echo $nota_id; ?>" method="post">
                                <div class="mb-3">
                                    <label for="contenido" class="form-label">Contenido de la nota</label>
                                    <textarea class="form-control" id="contenido" name="contenido" rows="5" required><?php echo htmlspecialchars($nota['contenido']); ?></textarea>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="tipo" class="form-label">Tipo de nota</label>
                                    <select class="form-select" id="tipo" name="tipo">
                                        <option value="">Sin categoría</option>
                                        <option value="entrevista" <?php echo $nota['tipo'] === 'entrevista' ? 'selected' : ''; ?>>Entrevista</option>
                                        <option value="evaluacion" <?php echo $nota['tipo'] === 'evaluacion' ? 'selected' : ''; ?>>Evaluación</option>
                                        <option value="seguimiento" <?php echo $nota['tipo'] === 'seguimiento' ? 'selected' : ''; ?>>Seguimiento</option>
                                        <option value="importante" <?php echo $nota['tipo'] === 'importante' ? 'selected' : ''; ?>>Importante</option>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <small class="text-muted">
                                        Creada por: <?php echo htmlspecialchars($nota['usuario_nombre'] ?? 'Usuario desconocido'); ?> - 
                                        Fecha: <?php echo date('d/m/Y H:i', strtotime($nota['created_at'])); ?>
                                    </small>
                                </div>
                                
                                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                    <a href="detalle.php?id=<?php echo $candidato_id; ?>&tab=notas" class="btn btn-outline-secondary me-md-2">Cancelar</a>
                                    <button type="submit" class="btn btn-primary">Guardar cambios</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </main>
            </div>
        </div>
    </div>
    
    <!-- Footer -->
    <?php include '../includes/footer.php'; ?>
    
    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>