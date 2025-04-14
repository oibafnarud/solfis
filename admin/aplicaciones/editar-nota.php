<?php
/**
 * Panel de Administración para SolFis
 * admin/aplicaciones/editar-nota.php - Editar nota de aplicación
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
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: index.php?error=missing_id');
    exit;
}

// Obtener ID de la nota
$id = (int)$_GET['id'];

// Instanciar clases necesarias
$applicationManager = new ApplicationManager();

// Obtener nota
$nota = $applicationManager->getNoteById($id);
if (!$nota) {
    header('Location: index.php?error=note_not_found');
    exit;
}

// Obtener aplicación relacionada para poder redirigir después
$aplicacion = $applicationManager->getApplicationById($nota['aplicacion_id']);
if (!$aplicacion) {
    header('Location: index.php?error=application_not_found');
    exit;
}

// Procesar formulario
$result = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validar datos
    if (empty($_POST['etapa']) || empty($_POST['notas'])) {
        $result = [
            'success' => false,
            'message' => 'Los campos Etapa y Contenido son obligatorios.'
        ];
    } else {
        // Preparar datos
        $data = [
            'etapa' => $_POST['etapa'],
            'notas' => $_POST['notas']
        ];
        
        if (!empty($_POST['fecha'])) {
            $data['fecha'] = $_POST['fecha'];
        }
        
        // Actualizar nota
        $result = $applicationManager->editApplicationNote($id, $data);
        
        if ($result['success']) {
            // Determinar redirección
            $redirect = 'detalle.php?id=' . $nota['aplicacion_id'] . '&tab=notes&message=note-updated';
            
            // Si venimos de la página de detalle de candidato, regresar allí
            if (isset($_GET['from']) && $_GET['from'] == 'candidato' && isset($_GET['candidato_id'])) {
                $redirect = '../candidatos/detalle.php?id=' . (int)$_GET['candidato_id'] . '&tab=notes&message=note-updated';
            }
            
            header('Location: ' . $redirect);
            exit;
        }
    }
}

// Título de la página
$pageTitle = 'Editar Nota - Panel de Administración';
?>

<?php include '../includes/header.php'; ?>

<div class="admin-main">
    <div class="container-fluid">
        <div class="row">
            <?php include '../includes/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Editar Nota</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <a href="detalle.php?id=<?php echo $nota['aplicacion_id']; ?>" class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-arrow-left"></i> Volver a la Aplicación
                            </a>
                        </div>
                    </div>
                </div>
                
                <?php if (!$result['success'] && !empty($result['message'])): ?>
                <div class="alert alert-danger" role="alert">
                    <?php echo $result['message']; ?>
                </div>
                <?php endif; ?>
                
                <div class="card">
                    <div class="card-body">
                        <form action="editar-nota.php?id=<?php echo $id; ?><?php echo isset($_GET['from']) && $_GET['from'] == 'candidato' && isset($_GET['candidato_id']) ? '&from=candidato&candidato_id=' . (int)$_GET['candidato_id'] : ''; ?>" method="post">
                            <div class="mb-3">
                                <label for="etapa" class="form-label">Etapa <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="etapa" name="etapa" value="<?php echo htmlspecialchars($nota['etapa']); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="notas" class="form-label">Contenido <span class="text-danger">*</span></label>
                                <textarea class="form-control" id="notas" name="notas" rows="5" required><?php echo htmlspecialchars($nota['notas']); ?></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label for="fecha" class="form-label">Fecha</label>
                                <input type="datetime-local" class="form-control" id="fecha" name="fecha" value="<?php echo date('Y-m-d\TH:i', strtotime($nota['fecha'])); ?>">
                            </div>
                            
                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Guardar Cambios
                                </button>
                                <a href="<?php echo isset($_GET['from']) && $_GET['from'] == 'candidato' && isset($_GET['candidato_id']) ? '../candidatos/detalle.php?id=' . (int)$_GET['candidato_id'] . '&tab=notes' : 'detalle.php?id=' . $nota['aplicacion_id'] . '&tab=notes'; ?>" class="btn btn-outline-secondary">
                                    <i class="fas fa-times"></i> Cancelar
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </main>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>