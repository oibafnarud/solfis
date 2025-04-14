<?php
/**
 * Panel de Administración para SolFis
 * admin/candidatos/editar.php - Editar información de candidato
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

// Obtener ID
$id = (int)$_GET['id'];

// Instanciar clases necesarias
$candidateManager = new CandidateManager();

// Obtener candidato por ID
$candidato = $candidateManager->getCandidateById($id);

// Si no existe el candidato, redirigir
if (!$candidato) {
    header('Location: index.php?message=error');
    exit;
}

// Procesar formulario
$success = false;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Procesar archivo de CV si se proporcionó
    $cv_path = $candidato['cv_path'];
    
    if (isset($_FILES['cv']) && $_FILES['cv']['error'] === UPLOAD_ERR_OK) {
        $tmp_name = $_FILES['cv']['tmp_name'];
        $name = basename($_FILES['cv']['name']);
        $extension = pathinfo($name, PATHINFO_EXTENSION);
        
        // Validar tipo de archivo
        $allowed_types = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
        if (!in_array($_FILES['cv']['type'], $allowed_types)) {
            $error = 'Solo se permiten archivos PDF, DOC o DOCX para el CV.';
        } else {
            // Generar nombre único
            $new_cv_filename = uniqid() . '_' . time() . '.' . $extension;
            
            // Directorio de destino
            $upload_dir = '../../uploads/resumes/';
            
            // Mover archivo
            if (move_uploaded_file($tmp_name, $upload_dir . $new_cv_filename)) {
                // Eliminar archivo anterior si existe
                if (!empty($cv_path) && file_exists($upload_dir . $cv_path)) {
                    unlink($upload_dir . $cv_path);
                }
                
                $cv_path = $new_cv_filename;
            } else {
                $error = 'Error al subir el archivo CV.';
            }
        }
    }
    
    if (!$error) {
        // Preparar datos
        $data = [
            'nombre' => $_POST['nombre'],
            'apellido' => $_POST['apellido'],
            'email' => $_POST['email'],
            'telefono' => $_POST['telefono'] ?? '',
            'ubicacion' => $_POST['ubicacion'] ?? '',
            'resumen' => $_POST['resumen'] ?? '',
            'linkedin' => $_POST['linkedin'] ?? '',
            'portfolio' => $_POST['portfolio'] ?? ''
        ];
        
        if ($cv_path !== $candidato['cv_path']) {
            $data['cv_path'] = $cv_path;
        }
        
        // Actualizar candidato
        $result = $candidateManager->updateCandidate($id, $data);
        
        if ($result['success']) {
            $success = true;
            $candidato = $candidateManager->getCandidateById($id); // Recargar datos
        } else {
            $error = $result['message'];
        }
    }
}

// Título de la página
$pageTitle = 'Editar Candidato - Panel de Administración';
?>

<?php include '../includes/header.php'; ?>

<div class="admin-main">
    <div class="container-fluid">
        <div class="row">
            <?php include '../includes/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Editar Candidato</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <a href="detalle.php?id=<?php echo $id; ?>" class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-arrow-left"></i> Volver al Detalle
                            </a>
                        </div>
                    </div>
                </div>
                
                <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    Candidato actualizado con éxito.
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo $error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>
                
                <form action="editar.php?id=<?php echo $id; ?>" method="post" enctype="multipart/form-data" id="candidate-form" class="needs-validation" novalidate>
                    <div class="row">
                        <!-- Información Personal -->
                        <div class="col-md-6">
                            <div class="card mb-4">
                                <div class="card-body">
                                    <h5 class="card-title mb-4">Información Personal</h5>
                                    
                                    <div class="mb-3">
                                        <label for="nombre" class="form-label">Nombre <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="nombre" name="nombre" value="<?php echo htmlspecialchars($candidato['nombre']); ?>" required>
                                        <div class="invalid-feedback">
                                            Por favor ingrese el nombre del candidato.
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="apellido" class="form-label">Apellido <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="apellido" name="apellido" value="<?php echo htmlspecialchars($candidato['apellido']); ?>" required>
                                        <div class="invalid-feedback">
                                            Por favor ingrese el apellido del candidato.
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                                        <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($candidato['email']); ?>" required>
                                        <div class="invalid-feedback">
                                            Por favor ingrese un email válido.
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="telefono" class="form-label">Teléfono</label>
                                        <input type="tel" class="form-control" id="telefono" name="telefono" value="<?php echo htmlspecialchars($candidato['telefono']); ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="ubicacion" class="form-label">Ubicación</label>
                                        <input type="text" class="form-control" id="ubicacion" name="ubicacion" value="<?php echo htmlspecialchars($candidato['ubicacion']); ?>">
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Perfil Profesional -->
                        <div class="col-md-6">
                            <div class="card mb-4">
                                <div class="card-body">
                                    <h5 class="card-title mb-4">Perfil Profesional</h5>
                                    
                                    <div class="mb-3">
                                        <label for="resumen" class="form-label">Resumen Profesional</label>
                                        <textarea class="form-control" id="resumen" name="resumen" rows="4"><?php echo htmlspecialchars($candidato['resumen'] ?? ''); ?></textarea>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="linkedin" class="form-label">LinkedIn</label>
                                        <input type="url" class="form-control" id="linkedin" name="linkedin" value="<?php echo htmlspecialchars($candidato['linkedin'] ?? ''); ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="portfolio" class="form-label">Sitio Web / Portfolio</label>
                                        <input type="url" class="form-control" id="portfolio" name="portfolio" value="<?php echo htmlspecialchars($candidato['portfolio'] ?? ''); ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="cv" class="form-label">CV (Curriculum Vitae)</label>
                                        <input type="file" class="form-control" id="cv" name="cv" accept=".pdf,.doc,.docx">
                                        <div class="form-text">
                                            <?php if (!empty($candidato['cv_path'])): ?>
                                                CV actual: <a href="../../uploads/resumes/<?php echo $candidato['cv_path']; ?>" target="_blank"><?php echo $candidato['cv_path']; ?></a>
                                            <?php else: ?>
                                                No hay CV cargado. Sube uno nuevo.
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end mb-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Guardar Cambios
                        </button>
                        <a href="detalle.php?id=<?php echo $id; ?>" class="btn btn-outline-secondary">
                            <i class="fas fa-times"></i> Cancelar
                        </a>
                    </div>
                </form>
            </main>
        </div>
    </div>
</div>

<script>
// Código para validación de formulario
(function() {
    'use strict';
    
    var form = document.getElementById('candidate-form');
    if (form) {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            
            form.classList.add('was-validated');
        }, false);
    }
})();
</script>

<?php include '../includes/footer.php'; ?>