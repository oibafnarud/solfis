<?php
/**
 * Panel de Administración para SolFis
 * admin/vacantes/vacante-editar.php - Editar vacante existente
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
$vacancyManager = new VacancyManager();
$categoryManager = new CategoryManager();

// Obtener vacante por ID
$vacante = $vacancyManager->getVacancyById($id);

// Si no existe la vacante, redirigir
if (!$vacante) {
    header('Location: index.php?message=error');
    exit;
}

// Obtener categorías
$categorias = $categoryManager->getCategories();

// Procesar formulario
$success = false;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Procesar y actualizar la vacante
    $result = $vacancyManager->updateVacancy($id, $_POST);
    
    if ($result['success']) {
        header('Location: index.php?message=vacante-updated');
        exit;
    } else {
        $error = $result['message'];
    }
}

// Título de la página
$pageTitle = 'Editar Vacante - Panel de Administración';
?>

<?php include '../includes/header.php'; ?>

<div class="admin-main">
    <div class="container-fluid">
        <div class="row">
            <?php include '../includes/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Editar Vacante</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <a href="index.php" class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-arrow-left"></i> Volver a Vacantes
                            </a>
                            <a href="../../vacantes/detalle.php?id=<?php echo $id; ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-eye"></i> Ver Vacante
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
                
                <form action="vacante-editar.php?id=<?php echo $id; ?>" method="post" id="vacancy-form" class="needs-validation" novalidate>
                    <div class="row">
                        <div class="col-md-8">
                            <div class="card mb-4">
                                <div class="card-body">
                                    <h5 class="card-title mb-4">Información Básica</h5>
                                    
                                    <div class="mb-3">
                                        <label for="titulo" class="form-label">Título de la Vacante <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="titulo" name="titulo" value="<?php echo htmlspecialchars($vacante['titulo']); ?>" required>
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
                                        <textarea class="form-control" id="descripcion" name="descripcion" rows="4" required><?php echo htmlspecialchars($vacante['descripcion']); ?></textarea>
                                        <div class="invalid-feedback">
                                            Por favor ingrese una descripción.
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="requisitos" class="form-label">Requisitos <span class="text-danger">*</span></label>
                                        <textarea class="form-control" id="requisitos" name="requisitos" rows="4" required><?php echo htmlspecialchars($vacante['requisitos']); ?></textarea>
                                        <div class="form-text">Ingrese cada requisito en una línea nueva.</div>
                                        <div class="invalid-feedback">
                                            Por favor ingrese los requisitos.
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="responsabilidades" class="form-label">Responsabilidades <span class="text-danger">*</span></label>
                                        <textarea class="form-control" id="responsabilidades" name="responsabilidades" rows="4" required><?php echo htmlspecialchars($vacante['responsabilidades']); ?></textarea>
                                        <div class="form-text">Ingrese cada responsabilidad en una línea nueva.</div>
                                        <div class="invalid-feedback">
                                            Por favor ingrese las responsabilidades.
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="beneficios" class="form-label">Beneficios</label>
                                        <textarea class="form-control" id="beneficios" name="beneficios" rows="4"><?php echo htmlspecialchars($vacante['beneficios']); ?></textarea>
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
                                            <option value="<?php echo $categoria['id']; ?>" <?php echo $vacante['categoria_id'] == $categoria['id'] ? 'selected' : ''; ?>>
                                                <?php echo $categoria['nombre']; ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <div class="invalid-feedback">
                                            Por favor seleccione una categoría.
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="ubicacion" class="form-label">Ubicación <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="ubicacion" name="ubicacion" value="<?php echo htmlspecialchars($vacante['ubicacion']); ?>" required>
                                        <div class="invalid-feedback">
                                            Por favor ingrese la ubicación.
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="modalidad" class="form-label">Modalidad <span class="text-danger">*</span></label>
                                        <select class="form-select" id="modalidad" name="modalidad" required>
                                            <option value="presencial" <?php echo $vacante['modalidad'] === 'presencial' ? 'selected' : ''; ?>>Presencial</option>
                                            <option value="remoto" <?php echo $vacante['modalidad'] === 'remoto' ? 'selected' : ''; ?>>Remoto</option>
                                            <option value="hibrido" <?php echo $vacante['modalidad'] === 'hibrido' ? 'selected' : ''; ?>>Híbrido</option>
                                        </select>
                                        <div class="invalid-feedback">
                                            Por favor seleccione una modalidad.
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="tipo_contrato" class="form-label">Tipo de Contrato <span class="text-danger">*</span></label>
                                        <select class="form-select" id="tipo_contrato" name="tipo_contrato" required>
                                            <option value="tiempo_completo" <?php echo $vacante['tipo_contrato'] === 'tiempo_completo' ? 'selected' : ''; ?>>Tiempo Completo</option>
                                            <option value="tiempo_parcial" <?php echo $vacante['tipo_contrato'] === 'tiempo_parcial' ? 'selected' : ''; ?>>Tiempo Parcial</option>
                                            <option value="proyecto" <?php echo $vacante['tipo_contrato'] === 'proyecto' ? 'selected' : ''; ?>>Por Proyecto</option>
                                            <option value="temporal" <?php echo $vacante['tipo_contrato'] === 'temporal' ? 'selected' : ''; ?>>Temporal</option>
                                        </select>
                                        <div class="invalid-feedback">
                                            Por favor seleccione un tipo de contrato.
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="experiencia" class="form-label">Experiencia Requerida</label>
                                        <input type="text" class="form-control" id="experiencia" name="experiencia" placeholder="Ej: 3-5 años" value="<?php echo htmlspecialchars($vacante['experiencia']); ?>">
                                    </div>
                                    
                                    <div class="row mb-3">
                                        <div class="col-6">
                                            <label for="salario_min" class="form-label">Salario Mínimo</label>
                                            <input type="number" class="form-control" id="salario_min" name="salario_min" min="0" step="1000" value="<?php echo $vacante['salario_min']; ?>">
                                        </div>
                                        <div class="col-6">
                                            <label for="salario_max" class="form-label">Salario Máximo</label>
                                            <input type="number" class="form-control" id="salario_max" name="salario_max" min="0" step="1000" value="<?php echo $vacante['salario_max']; ?>">
                                        </div>
                                    </div>
                                    
                                    <div class="form-check mb-3">
                                        <input class="form-check-input" type="checkbox" id="mostrar_salario" name="mostrar_salario" value="1" <?php echo $vacante['mostrar_salario'] ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="mostrar_salario">
                                            Mostrar salario en publicación
                                        </label>
                                    </div>
                                    
                                    <div class="form-check mb-3">
                                        <input class="form-check-input" type="checkbox" id="destacada" name="destacada" value="1" <?php echo $vacante['destacada'] ? 'checked' : ''; ?>>
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
                                        <input type="date" class="form-control" id="fecha_publicacion" name="fecha_publicacion" value="<?php echo $vacante['fecha_publicacion'] ? date('Y-m-d', strtotime($vacante['fecha_publicacion'])) : ''; ?>">
                                        <div class="form-text">Dejar en blanco para usar la fecha actual cuando se publique.</div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="fecha_cierre" class="form-label">Fecha de Cierre</label>
                                        <input type="date" class="form-control" id="fecha_cierre" name="fecha_cierre" value="<?php echo $vacante['fecha_cierre'] ? date('Y-m-d', strtotime($vacante['fecha_cierre'])) : ''; ?>">
                                        <div class="form-text">Dejar en blanco si no hay fecha límite.</div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label d-block">Estado de Publicación</label>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="estado" id="estado_borrador" value="borrador" <?php echo $vacante['estado'] === 'borrador' ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="estado_borrador">Borrador</label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="estado" id="estado_publicada" value="publicada" <?php echo $vacante['estado'] === 'publicada' ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="estado_publicada">Publicada</label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="estado" id="estado_cerrada" value="cerrada" <?php echo $vacante['estado'] === 'cerrada' ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="estado_cerrada">Cerrada</label>
                                        </div>
                                    </div>
                                    
                                    <div class="d-grid gap-2">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save"></i> Actualizar Vacante
                                        </button>
                                        <a href="index.php" class="btn btn-outline-secondary">
                                            <i class="fas fa-times"></i> Cancelar
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <div class="card mb-4">
                                <div class="card-body">
                                    <h5 class="card-title mb-4">Información de la Vacante</h5>
                                    
                                    <div class="mb-2">
                                        <strong>Fecha de Creación:</strong><br>
                                        <?php echo date('d/m/Y H:i', strtotime($vacante['created_at'])); ?>
                                    </div>
                                    
                                    <div class="mb-2">
                                        <strong>Última Actualización:</strong><br>
                                        <?php echo date('d/m/Y H:i', strtotime($vacante['updated_at'])); ?>
                                    </div>

                                    <div class="mt-4">
                                        <a href="vacante-eliminar.php?id=<?php echo $id; ?>" class="btn btn-outline-danger w-100" onclick="return confirm('¿Está seguro de eliminar esta vacante? Esta acción no se puede deshacer.');">
                                            <i class="fas fa-trash"></i> Eliminar Vacante
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