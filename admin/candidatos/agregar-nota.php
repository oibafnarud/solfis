<?php
/**
 * Panel de Administración para SolFis
 * admin/candidatos/agregar-nota.php - Agregar nota a un candidato
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
    header('Location: index.php');
    exit;
}

$candidato_id = (int)$_GET['id'];

// Instanciar clases necesarias
$candidateManager = new CandidateManager();

// Obtener candidato
$candidato = $candidateManager->getCandidateById($candidato_id);
if (!$candidato) {
    header('Location: index.php');
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
        // Agregar nota
        $result = $candidateManager->addCandidateNote(
            $candidato_id,
            $_POST['titulo'],
            $_POST['contenido']
        );
        
        if ($result['success']) {
            // Redireccionar a la página de detalle
            header('Location: detalle.php?id=' . $candidato_id . '&tab=notas&message=note-added');
            exit;
        }
    }
}

// Título de la página
$pageTitle = 'Agregar Nota - Panel de Administración';
?>

<?php include '../includes/header.php'; ?>

<div class="admin-main">
    <div class="container-fluid">
        <div class="row">
            <?php include '../includes/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Agregar Nota para <?php echo htmlspecialchars($candidato['nombre'] . ' ' . $candidato['apellido']); ?></h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <a href="detalle.php?id=<?php echo $candidato_id; ?>" class="btn btn-sm btn-outline-secondary">
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
                    <div class="card-body">
						<form action="guardar-nota.php" method="post">
							<input type="hidden" name="candidato_id" value="<?php echo $candidato_id; ?>">
							
							<div class="mb-3">
								<label for="titulo" class="form-label">Título <span class="text-danger">*</span></label>
								<input type="text" class="form-control" id="titulo" name="titulo" required>
							</div>
							
							<div class="mb-3">
								<label for="contenido" class="form-label">Contenido <span class="text-danger">*</span></label>
								<textarea class="form-control" id="contenido" name="contenido" rows="5" required></textarea>
							</div>
							
							<div class="d-grid gap-2 d-md-flex justify-content-md-end">
								<button type="submit" class="btn btn-primary">
									<i class="fas fa-save"></i> Guardar Nota
								</button>
								<a href="detalle.php?id=<?php echo $candidato_id; ?>" class="btn btn-outline-secondary">
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