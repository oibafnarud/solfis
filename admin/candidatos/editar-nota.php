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

// Verificar parámetros
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: index.php?error=missing_id');
    exit;
}

// Obtener ID de la nota
$id = (int)$_GET['id'];

// Instanciar clases necesarias
$candidateManager = new CandidateManager();

// Obtener nota
$nota = $candidateManager->getNoteById($id);
if (!$nota) {
    header('Location: index.php?error=note_not_found');
    exit;
}

// Verificar que la nota pertenece a un candidato
if (empty($nota['candidato_id'])) {
    header('Location: index.php?error=invalid_note');
    exit;
}

// Obtener candidato
$candidato = $candidateManager->getCandidateById($nota['candidato_id']);
if (!$candidato) {
    header('Location: index.php?error=candidate_not_found');
    exit;
}

// Procesar formulario
$result = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validar datos
    if (empty($_POST['titulo']) || empty($_POST['contenido'])) {
        $result = [
            'success' => false,
            'message' => 'Los campos Título y Contenido son obligatorios.'
        ];
    } else {
        // Preparar datos
        $data = [
            'titulo' => $_POST['titulo'],
            'contenido' => $_POST['contenido']
        ];
        
        // Actualizar nota
        $result = $candidateManager->editCandidateNote($id, $data);
        
        if ($result['success']) {
            // Redireccionar a la página de detalle
            header('Location: detalle.php?id=' . $candidato['id'] . '&tab=notes&message=note-updated');
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
                            <a href="detalle.php?id=<?php echo $candidato['id']; ?>&tab=notes" class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-arrow-left"></i> Volver al Candidato
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
                    <div class="card-header">
                        <h5 class="mb-0">Editar nota para <?php echo htmlspecialchars($candidato['nombre'] . ' ' . $candidato['apellido']); ?></h5>
                    </div>
                    <div class="card-body">
                        <form action="editar-nota.php?id=<?php echo $id; ?>" method="post">
                            <div class="mb-3">
                                <label for="titulo" class="form-label">Título <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="titulo" name="titulo" value="<?php echo htmlspecialchars($nota['titulo']); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="contenido" class="form-label">Contenido <span class="text-danger">*</span></label>
                                <textarea class="form-control" id="contenido" name="contenido" rows="5" required><?php echo htmlspecialchars($nota['contenido']); ?></textarea>
                            </div>
                            
                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Guardar Cambios
                                </button>
                                <a href="detalle.php?id=<?php echo $candidato['id']; ?>&tab=notes" class="btn btn-outline-secondary">
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