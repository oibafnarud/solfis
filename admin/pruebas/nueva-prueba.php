<?php
/**
 * Panel de Administración para SolFis
 * admin/pruebas/nueva-prueba.php - Creación de nuevas evaluaciones psicométricas
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

// Verificar si existe el TestManager
$testManager = null;
$hasTestManager = false;

if (file_exists('../../includes/TestManager.php')) {
    require_once '../../includes/TestManager.php';
    if (class_exists('TestManager')) {
        $testManager = new TestManager();
        $hasTestManager = true;
    }
}

// Si no existe TestManager, mostrar un mensaje de error
if (!$hasTestManager) {
    $_SESSION['error'] = "El módulo de evaluaciones psicométricas no está disponible en el sistema.";
    header('Location: ../index.php');
    exit;
}

// Obtener categorías para el selector
$categories = $testManager->getTestCategories();

// Obtener dimensiones para el selector (si existen)
$dimensions = [];
try {
    $db = Database::getInstance();
    $dimensionsQuery = "SELECT * FROM dimensiones ORDER BY nombre ASC";
    $result = $db->query($dimensionsQuery);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $dimensions[] = $row;
        }
    }
} catch (Exception $e) {
    // Si no existe la tabla, creamos algunas dimensiones por defecto
    $dimensions = [
        ['id' => 1, 'nombre' => 'Razonamiento Verbal', 'descripcion' => 'Mide la capacidad de comprensión, análisis y uso del lenguaje'],
        ['id' => 2, 'nombre' => 'Razonamiento Numérico', 'descripcion' => 'Mide la habilidad para trabajar con números y resolver problemas matemáticos'],
        ['id' => 3, 'nombre' => 'Razonamiento Lógico', 'descripcion' => 'Mide la capacidad para razonar de forma lógica y resolver problemas abstractos'],
        ['id' => 4, 'nombre' => 'Inteligencia Emocional', 'descripcion' => 'Mide la capacidad para reconocer y gestionar emociones'],
        ['id' => 5, 'nombre' => 'Habilidades Sociales', 'descripcion' => 'Mide la capacidad para interactuar y comunicarse con otros']
    ];
}

// Procesar el formulario de creación si se envió
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Recoger y validar datos
    $titulo = isset($_POST['titulo']) ? trim($_POST['titulo']) : '';
    $descripcion = isset($_POST['descripcion']) ? trim($_POST['descripcion']) : '';
    $instrucciones = isset($_POST['instrucciones']) ? trim($_POST['instrucciones']) : '';
    $categoria_id = isset($_POST['categoria_id']) ? (int)$_POST['categoria_id'] : 0;
    $tiempo_estimado = isset($_POST['tiempo_estimado']) ? (int)$_POST['tiempo_estimado'] : 30;
    $nivel_dificultad = isset($_POST['nivel_dificultad']) ? trim($_POST['nivel_dificultad']) : 'medio';
    $estado = isset($_POST['estado']) ? trim($_POST['estado']) : 'inactiva';
    $dimensiones_seleccionadas = isset($_POST['dimensiones']) ? $_POST['dimensiones'] : [];
    
    // Validación básica
    $errores = [];
    
    if (empty($titulo)) {
        $errores[] = "El título de la prueba es obligatorio";
    }
    
    if (empty($descripcion)) {
        $errores[] = "La descripción de la prueba es obligatoria";
    }
    
    if ($categoria_id <= 0) {
        $errores[] = "Debe seleccionar una categoría para la prueba";
    }
    
    if ($tiempo_estimado <= 0) {
        $errores[] = "El tiempo estimado debe ser mayor a 0 minutos";
    }
    
    // Si no hay errores, crear la prueba
    if (empty($errores)) {
        $db = Database::getInstance();
        
        // Escapar datos
        $titulo = $db->escape($titulo);
        $descripcion = $db->escape($descripcion);
        $instrucciones = $db->escape($instrucciones);
        $nivel_dificultad = $db->escape($nivel_dificultad);
        $estado = $db->escape($estado);
        
        // Consulta SQL para insertar prueba
        $sql = "INSERT INTO pruebas (
                    titulo, descripcion, instrucciones, categoria_id, tiempo_estimado,
                    nivel_dificultad, estado, created_at, updated_at
                ) VALUES (
                    '$titulo', '$descripcion', '$instrucciones', $categoria_id, $tiempo_estimado,
                    '$nivel_dificultad', '$estado', NOW(), NOW()
                )";
        
        if ($db->query($sql)) {
            $prueba_id = $db->lastInsertId();
            
            // Asociar dimensiones si se seleccionaron
            if (!empty($dimensiones_seleccionadas)) {
                foreach ($dimensiones_seleccionadas as $dimension_id) {
                    $dimension_id = (int)$dimension_id;
                    $sqlDimension = "INSERT INTO pruebas_dimensiones (prueba_id, dimension_id, created_at, updated_at) 
                                    VALUES ($prueba_id, $dimension_id, NOW(), NOW())";
                    $db->query($sqlDimension);
                }
            }
            
            $_SESSION['success'] = "La prueba se ha creado correctamente.";
            header('Location: preguntas.php?test_id=' . $prueba_id);
            exit;
        } else {
            $errores[] = "Error al crear la prueba: " . $db->getConnection()->error;
        }
    }
}

// Título de la página
$pageTitle = 'Nueva Evaluación - Panel de Administración';
?>

<?php include '../includes/header.php'; ?>

<div class="admin-main">
    <div class="container-fluid">
        <div class="row">
            <?php include '../includes/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Nueva Evaluación Psicométrica</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="index.php" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-arrow-left"></i> Volver
                        </a>
                    </div>
                </div>
                
                <?php if (!empty($errores)): ?>
                <div class="alert alert-danger">
                    <strong>Se encontraron los siguientes errores:</strong>
                    <ul class="mb-0 mt-2">
                        <?php foreach ($errores as $error): ?>
                        <li><?php echo $error; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>
                
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="card-title mb-0"><i class="fas fa-plus-circle"></i> Información de la Prueba</h5>
                    </div>
                    <div class="card-body">
                        <form action="" method="post" class="needs-validation" novalidate>
                            <div class="row mb-3">
                                <div class="col-md-8">
                                    <label for="titulo" class="form-label">Título <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="titulo" name="titulo" value="<?php echo isset($titulo) ? htmlspecialchars($titulo) : ''; ?>" required>
                                    <div class="invalid-feedback">
                                        El título es obligatorio.
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <label for="categoria_id" class="form-label">Categoría <span class="text-danger">*</span></label>
                                    <select class="form-select" id="categoria_id" name="categoria_id" required>
                                        <option value="">Selecciona una categoría</option>
                                        <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo $category['id']; ?>" <?php echo isset($categoria_id) && $categoria_id == $category['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($category['nombre']); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="invalid-feedback">
                                        Debes seleccionar una categoría.
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="descripcion" class="form-label">Descripción <span class="text-danger">*</span></label>
                                <textarea class="form-control" id="descripcion" name="descripcion" rows="3" required><?php echo isset($descripcion) ? htmlspecialchars($descripcion) : ''; ?></textarea>
                                <div class="invalid-feedback">
                                    La descripción es obligatoria.
                                </div>
                                <div class="form-text">
                                    Breve descripción de la prueba y qué evalúa. Esta descripción será visible para los candidatos.
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="instrucciones" class="form-label">Instrucciones</label>
                                <textarea class="form-control" id="instrucciones" name="instrucciones" rows="4"><?php echo isset($instrucciones) ? htmlspecialchars($instrucciones) : ''; ?></textarea>
                                <div class="form-text">
                                    Instrucciones detalladas para los candidatos sobre cómo realizar la prueba. Se mostrarán antes de comenzar.
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label for="tiempo_estimado" class="form-label">Tiempo Estimado (min)</label>
                                    <input type="number" class="form-control" id="tiempo_estimado" name="tiempo_estimado" value="<?php echo isset($tiempo_estimado) ? $tiempo_estimado : 30; ?>" min="1" max="180">
                                    <div class="form-text">
                                        Tiempo estimado para completar la prueba en minutos.
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <label for="nivel_dificultad" class="form-label">Nivel de Dificultad</label>
                                    <select class="form-select" id="nivel_dificultad" name="nivel_dificultad">
                                        <option value="bajo" <?php echo isset($nivel_dificultad) && $nivel_dificultad == 'bajo' ? 'selected' : ''; ?>>Bajo</option>
                                        <option value="medio" <?php echo isset($nivel_dificultad) && $nivel_dificultad == 'medio' ? 'selected' : (isset($nivel_dificultad) ? '' : 'selected'); ?>>Medio</option>
                                        <option value="alto" <?php echo isset($nivel_dificultad) && $nivel_dificultad == 'alto' ? 'selected' : ''; ?>>Alto</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="estado" class="form-label">Estado</label>
                                    <select class="form-select" id="estado" name="estado">
                                        <option value="inactiva" <?php echo isset($estado) && $estado == 'inactiva' ? 'selected' : (isset($estado) ? '' : 'selected'); ?>>Inactiva</option>
                                        <option value="activa" <?php echo isset($estado) && $estado == 'activa' ? 'selected' : ''; ?>>Activa</option>
                                    </select>
                                    <div class="form-text">
                                        Las pruebas inactivas no se mostrarán a los candidatos.
                                    </div>
                                </div>
                            </div>
                            
                            <?php if (!empty($dimensions)): ?>
                            <div class="mb-4">
                                <label class="form-label">Dimensiones a evaluar</label>
                                <div class="row">
                                    <?php foreach ($dimensions as $dimension): ?>
                                    <div class="col-md-4">
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" value="<?php echo $dimension['id']; ?>" id="dimension_<?php echo $dimension['id']; ?>" name="dimensiones[]" <?php echo isset($dimensiones_seleccionadas) && in_array($dimension['id'], $dimensiones_seleccionadas) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="dimension_<?php echo $dimension['id']; ?>">
                                                <?php echo htmlspecialchars($dimension['nombre']); ?>
                                            </label>
                                            <div class="form-text small">
                                                <?php echo htmlspecialchars($dimension['descripcion']); ?>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                <div class="form-text mt-2">
                                    Selecciona las dimensiones que evaluará esta prueba. Esto ayudará a categorizar y analizar los resultados.
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <div class="d-flex justify-content-between mt-4">
                                <a href="index.php" class="btn btn-outline-secondary">Cancelar</a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Guardar y Continuar
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <h5 class="card-title mb-0"><i class="fas fa-info-circle"></i> Información sobre el proceso</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6>Creación de una Evaluación</h6>
                                <ol class="small">
                                    <li class="mb-2"><strong>Información básica:</strong> Introduce el título, descripción y parámetros fundamentales.</li>
                                    <li class="mb-2"><strong>Configuración de dimensiones:</strong> Selecciona las dimensiones que evaluará esta prueba.</li>
                                    <li class="mb-2"><strong>Creación de preguntas:</strong> Una vez guardada la prueba, podrás añadir las preguntas.</li>
                                    <li class="mb-2"><strong>Activación:</strong> Cuando la prueba esté lista, actívala para que esté disponible para los candidatos.</li>
                                </ol>
                            </div>
                            <div class="col-md-6">
                                <h6>Tipos de Preguntas Disponibles</h6>
                                <ul class="small">
                                    <li class="mb-2"><strong>Opción múltiple:</strong> Preguntas con varias opciones y una respuesta correcta.</li>
                                    <li class="mb-2"><strong>Verdadero/Falso:</strong> Preguntas con dos opciones: verdadero o falso.</li>
                                    <li class="mb-2"><strong>Escala Likert:</strong> Preguntas que permiten medir el grado de acuerdo o desacuerdo.</li>
                                    <li class="mb-2"><strong>Respuesta abierta:</strong> Preguntas que permiten texto libre como respuesta.</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
</div>

<!-- Agregar el archivo CSS para el admin -->
<link rel="stylesheet" href="../css/admin.css">

<script>
// Script para validación del formulario usando Bootstrap
document.addEventListener('DOMContentLoaded', function() {
    // Fetch all the forms we want to apply custom Bootstrap validation styles to
    var forms = document.querySelectorAll('.needs-validation');
    
    // Loop over them and prevent submission
    Array.from(forms).forEach(function(form) {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            
            form.classList.add('was-validated');
        }, false);
    });
});
</script>

<?php include '../includes/footer.php'; ?>