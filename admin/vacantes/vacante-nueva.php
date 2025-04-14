<?php
/**
 * Panel de Administración para SolFis
 * admin/vacantes/vacante-nueva.php - Crear nueva vacante
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

// Instanciar clases necesarias
$vacancyManager = new VacancyManager();
$categoryManager = new CategoryManager();

// Obtener categorías
$categorias = $categoryManager->getCategories();

// Procesar formulario
$success = false;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Procesar y guardar la vacante
    $result = $vacancyManager->createVacancy($_POST);
    
    if ($result['success']) {
        header('Location: index.php?message=vacante-created');
        exit;
    } else {
        $error = $result['message'];
    }
}

// Título de la página
$pageTitle = 'Nueva Vacante - Panel de Administración';
?>

<?php include '../includes/header.php'; ?>

<div class="admin-main">
    <div class="container-fluid">
        <div class="row">
            <?php include '../includes/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Nueva Vacante</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <a href="index.php" class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-arrow-left"></i> Volver a Vacantes
                            </a>
                        </div>
                    </div>
                </div>
                
                <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo $error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>
                
                <form action="vacante-nueva.php" method="post" id="vacancy-form" class="needs-validation" novalidate>
                    <div class="row">
                        <div class="col-md-8">
                            <div class="card mb-4">
                                <div class="card-body">
                                    <h5 class="card-title mb-4">Información Básica</h5>
                                    
                                    <div class="mb-3">
                                        <label for="titulo" class="form-label">Título de la Vacante <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="titulo" name="titulo" required>
                                        <div class="invalid-feedback">
                                            Por favor ingrese un título para la vacante.
                                        </div>
                                    </div>
<<<<<<< HEAD
									
									<!-- Agregar después del checkbox "destacada" -->
									<div class="mb-3">
										<label for="empresa_contratante" class="form-label">Empresa Contratante</label>
										<input type="text" class="form-control" id="empresa_contratante" name="empresa_contratante" value="<?php echo htmlspecialchars($vacante['empresa_contratante'] ?? ''); ?>">
										<div class="form-text">Nombre de la empresa para la cual se contrata (si aplica).</div>
									</div>

									<div class="form-check mb-3">
										<input class="form-check-input" type="checkbox" id="mostrar_empresa" name="mostrar_empresa" value="1" <?php echo isset($vacante['mostrar_empresa']) && $vacante['mostrar_empresa'] ? 'checked' : ''; ?>>
										<label class="form-check-label" for="mostrar_empresa">
											Mostrar empresa contratante en publicación
										</label>
									</div>
=======
>>>>>>> bfdd4b60a420df76ff03f2ca490715c5b78545c5
                                    
                                    <div class="mb-3">
                                        <label for="descripcion" class="form-label">Descripción <span class="text-danger">*</span></label>
                                        <textarea class="form-control" id="descripcion" name="descripcion" rows="4" required></textarea>
                                        <div class="invalid-feedback">
                                            Por favor ingrese una descripción.
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="requisitos" class="form-label">Requisitos <span class="text-danger">*</span></label>
                                        <textarea class="form-control" id="requisitos" name="requisitos" rows="4" required></textarea>
                                        <div class="form-text">Ingrese cada requisito en una línea nueva.</div>
                                        <div class="invalid-feedback">
                                            Por favor ingrese los requisitos.
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="responsabilidades" class="form-label">Responsabilidades <span class="text-danger">*</span></label>
                                        <textarea class="form-control" id="responsabilidades" name="responsabilidades" rows="4" required></textarea>
                                        <div class="form-text">Ingrese cada responsabilidad en una línea nueva.</div>
                                        <div class="invalid-feedback">
                                            Por favor ingrese las responsabilidades.
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="beneficios" class="form-label">Beneficios</label>
                                        <textarea class="form-control" id="beneficios" name="beneficios" rows="4"></textarea>
                                        <div class="form-text">Ingrese cada beneficio en una línea nueva.</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="card mb-4">
                                <div class="card-body">
                                    <h5 class="card-title mb-4">Detalles y Publicación</h5>
                                    
                                    <div class="mb-3">
                                        <label for="categoria" class="form-label">Categoría <span class="text-danger">*</span></label>
                                        <select class="form-select" id="categoria" name="categoria" required>
                                            <option value="">Seleccionar categoría</option>
                                            <?php foreach ($categorias as $categoria): ?>
                                            <option value="<?php echo $categoria['id']; ?>"><?php echo $categoria['nombre']; ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <div class="invalid-feedback">
                                            Por favor seleccione una categoría.
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="ubicacion" class="form-label">Ubicación <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="ubicacion" name="ubicacion" required>
                                        <div class="invalid-feedback">
                                            Por favor ingrese la ubicación.
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="modalidad" class="form-label">Modalidad <span class="text-danger">*</span></label>
                                        <select class="form-select" id="modalidad" name="modalidad" required>
                                            <option value="presencial">Presencial</option>
                                            <option value="remoto">Remoto</option>
                                            <option value="hibrido">Híbrido</option>
                                        </select>
                                        <div class="invalid-feedback">
                                            Por favor seleccione una modalidad.
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="tipo_contrato" class="form-label">Tipo de Contrato <span class="text-danger">*</span></label>
                                        <select class="form-select" id="tipo_contrato" name="tipo_contrato" required>
                                            <option value="tiempo_completo">Tiempo Completo</option>
                                            <option value="tiempo_parcial">Tiempo Parcial</option>
                                            <option value="proyecto">Por Proyecto</option>
                                            <option value="temporal">Temporal</option>
                                        </select>
                                        <div class="invalid-feedback">
                                            Por favor seleccione un tipo de contrato.
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="experiencia" class="form-label">Experiencia Requerida</label>
                                        <input type="text" class="form-control" id="experiencia" name="experiencia" placeholder="Ej: 3-5 años">
                                    </div>
                                    
                                    <div class="row mb-3">
                                        <div class="col-6">
                                            <label for="salario_min" class="form-label">Salario Mínimo</label>
                                            <input type="number" class="form-control" id="salario_min" name="salario_min" min="0" step="1000">
                                        </div>
                                        <div class="col-6">
                                            <label for="salario_max" class="form-label">Salario Máximo</label>
                                            <input type="number" class="form-control" id="salario_max" name="salario_max" min="0" step="1000">
                                        </div>
                                    </div>
                                    
                                    <div class="form-check mb-3">
                                        <input class="form-check-input" type="checkbox" id="mostrar_salario" name="mostrar_salario" value="1">
                                        <label class="form-check-label" for="mostrar_salario">
                                            Mostrar salario en publicación
                                        </label>
                                    </div>
                                    
                                    <div class="form-check mb-3">
                                        <input class="form-check-input" type="checkbox" id="destacada" name="destacada" value="1">
                                        <label class="form-check-label" for="destacada">
                                            Destacar esta vacante
                                        </label>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="card mb-4">
                                <div class="card-body">
                                    <h5 class="card-title mb-4">Fechas y Estado</h5>
                                    
                                    <div class="mb-3">
                                        <label for="fecha_publicacion" class="form-label">Fecha de Publicación</label>
                                        <input type="date" class="form-control" id="fecha_publicacion" name="fecha_publicacion">
                                        <div class="form-text">Dejar en blanco para usar la fecha actual cuando se publique.</div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="fecha_cierre" class="form-label">Fecha de Cierre</label>
                                        <input type="date" class="form-control" id="fecha_cierre" name="fecha_cierre">
                                        <div class="form-text">Dejar en blanco si no hay fecha límite.</div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label d-block">Estado de Publicación</label>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="estado" id="estado_borrador" value="borrador" checked>
                                            <label class="form-check-label" for="estado_borrador">Borrador</label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="estado" id="estado_publicada" value="publicada">
                                            <label class="form-check-label" for="estado_publicada">Publicada</label>
                                        </div>
                                    </div>
                                    
                                    <div class="d-grid gap-2">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save"></i> Guardar Vacante
                                        </button>
                                        <a href="index.php" class="btn btn-outline-secondary">
                                            <i class="fas fa-times"></i> Cancelar
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
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
    
    var form = document.getElementById('vacancy-form');
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