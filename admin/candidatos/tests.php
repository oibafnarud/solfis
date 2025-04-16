<?php
/**
 * Panel de Administración para SolFis
 * admin/candidatos/tests.php - Gestionar pruebas de un candidato
 */

// Inicializar sesión
session_start();

// Incluir archivos necesarios
require_once '../config.php';
require_once '../../includes/blog-system.php';
require_once '../../includes/jobs-system.php';
require_once '../../includes/TestManager.php';

// Verificar autenticación
$auth = Auth::getInstance();
if (!$auth->isLoggedIn()) {
    header('Location: ../login.php');
    exit;
}

// Verificar que se proporciona un ID de candidato
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error'] = "ID de candidato no proporcionado";
    header('Location: index.php');
    exit;
}

$candidato_id = (int)$_GET['id'];

// Instanciar gestores
$candidateManager = new CandidateManager();
$testManager = new TestManager();

// Obtener datos del candidato
$candidato = $candidateManager->getCandidateById($candidato_id);

if (!$candidato) {
    $_SESSION['error'] = "Candidato no encontrado";
    header('Location: index.php');
    exit;
}

// Procesar asignación de pruebas
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['pruebas']) && is_array($_POST['pruebas'])) {
        $pruebasAsignadas = array_map('intval', $_POST['pruebas']);
        
        // Asignar cada prueba seleccionada
        foreach ($pruebasAsignadas as $pruebaId) {
            // Verificar si ya hay una sesión para esta prueba
            $sesionExistente = $testManager->checkExistingSession($candidato_id, $pruebaId);
            
            if (!$sesionExistente) {
                // Crear nueva sesión para la prueba
                $testManager->createSession($candidato_id, $pruebaId);
            }
        }
        
        $_SESSION['success'] = "Pruebas asignadas correctamente al candidato";
    } else {
        $_SESSION['error'] = "No se seleccionaron pruebas para asignar";
    }
    
    header("Location: tests.php?id=$candidato_id");
    exit;
}

// Obtener pruebas disponibles
$pruebasDisponibles = $testManager->getAllTests();

// Obtener pruebas asignadas al candidato
$pruebasAsignadas = $testManager->getTestsByCandidate($candidato_id);

// Obtener pruebas completadas por el candidato
$pruebasCompletadas = $testManager->getCompletedTests($candidato_id);

// Obtener pruebas en progreso
$pruebasEnProgreso = $testManager->getInProgressTests($candidato_id);

// Título de la página
$pageTitle = 'Gestión de Pruebas - ' . $candidato['nombre'] . ' ' . $candidato['apellido'];

// Incluir la vista
include '../includes/header.php';
?>

<div class="admin-main">
    <div class="container-fluid">
        <div class="row">
            <?php include '../includes/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Gestión de Pruebas - <?php echo htmlspecialchars($candidato['nombre'] . ' ' . $candidato['apellido']); ?></h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="detalle.php?id=<?php echo $candidato_id; ?>" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-arrow-left"></i> Volver al Perfil
                        </a>
                    </div>
                </div>
                
                <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>
                
                <!-- Pruebas Completadas -->
                <div class="card mb-4">
                    <div class="card-header bg-success text-white">
                        <i class="fas fa-check-circle"></i> Pruebas Completadas
                    </div>
                    <div class="card-body">
                        <?php if (empty($pruebasCompletadas)): ?>
                        <p class="text-muted">El candidato no ha completado ninguna prueba aún.</p>
                        <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Prueba</th>
                                        <th>Categoría</th>
                                        <th>Fecha</th>
                                        <th>Resultado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($pruebasCompletadas as $prueba): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($prueba['prueba_titulo']); ?></td>
                                        <td><?php echo htmlspecialchars($prueba['categoria_nombre'] ?? 'Sin categoría'); ?></td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($prueba['fecha_fin'])); ?></td>
                                        <td>
                                            <?php if (isset($prueba['resultado_global'])): ?>
                                            <div class="progress" style="height: 20px;">
                                                <?php
                                                $resultClass = 'bg-secondary';
                                                if ($prueba['resultado_global'] >= 90) $resultClass = 'bg-success';
                                                elseif ($prueba['resultado_global'] >= 75) $resultClass = 'bg-primary';
                                                elseif ($prueba['resultado_global'] >= 60) $resultClass = 'bg-info';
                                                elseif ($prueba['resultado_global'] >= 40) $resultClass = 'bg-warning';
                                                else $resultClass = 'bg-danger';
                                                ?>
                                                <div class="progress-bar <?php echo $resultClass; ?>" role="progressbar" 
                                                     style="width: <?php echo $prueba['resultado_global']; ?>%;" 
                                                     aria-valuenow="<?php echo $prueba['resultado_global']; ?>" 
                                                     aria-valuemin="0" aria-valuemax="100">
                                                    <?php echo $prueba['resultado_global']; ?>%
                                                </div>
                                            </div>
                                            <?php else: ?>
                                            <span class="text-muted">Sin calificar</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="../pruebas/resultados.php?session_id=<?php echo $prueba['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-eye"></i> Ver Resultados
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Pruebas En Progreso -->
                <div class="card mb-4">
                    <div class="card-header bg-warning text-dark">
                        <i class="fas fa-hourglass-half"></i> Pruebas En Progreso
                    </div>
                    <div class="card-body">
                        <?php if (empty($pruebasEnProgreso)): ?>
                        <p class="text-muted">El candidato no tiene pruebas en progreso.</p>
                        <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Prueba</th>
                                        <th>Categoría</th>
                                        <th>Iniciada</th>
                                        <th>Progreso</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($pruebasEnProgreso as $prueba): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($prueba['prueba_titulo']); ?></td>
                                        <td><?php echo htmlspecialchars($prueba['categoria_nombre'] ?? 'Sin categoría'); ?></td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($prueba['fecha_inicio'])); ?></td>
                                        <td>
                                            <?php if (isset($prueba['respuestas_count']) && isset($prueba['preguntas_count']) && $prueba['preguntas_count'] > 0): ?>
                                            <div class="progress" style="height: 20px;">
                                                <?php $porcentaje = round(($prueba['respuestas_count'] / $prueba['preguntas_count']) * 100); ?>
                                                <div class="progress-bar bg-info" role="progressbar" 
                                                     style="width: <?php echo $porcentaje; ?>%;" 
                                                     aria-valuenow="<?php echo $porcentaje; ?>" 
                                                     aria-valuemin="0" aria-valuemax="100">
                                                    <?php echo $porcentaje; ?>% (<?php echo $prueba['respuestas_count']; ?>/<?php echo $prueba['preguntas_count']; ?>)
                                                </div>
                                            </div>
                                            <?php else: ?>
                                            <span class="text-muted">Sin datos de progreso</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="../pruebas/enviar-recordatorio.php?session_id=<?php echo $prueba['id']; ?>" class="btn btn-sm btn-outline-info">
                                                <i class="fas fa-bell"></i> Enviar Recordatorio
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Asignar Nuevas Pruebas -->
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <i class="fas fa-plus-circle"></i> Asignar Nuevas Pruebas
                    </div>
                    <div class="card-body">
                        <form action="tests.php?id=<?php echo $candidato_id; ?>" method="post">
                            <div class="mb-3">
                                <label for="pruebas" class="form-label">Seleccione las pruebas a asignar:</label>
                                
                                <?php if (empty($pruebasDisponibles)): ?>
                                <div class="alert alert-warning">
                                    No hay pruebas disponibles para asignar.
                                </div>
                                <?php else: ?>
                                
                                <?php
                                // Agrupar pruebas por categoría
                                $pruebasPorCategoria = [];
                                foreach ($pruebasDisponibles as $prueba) {
                                    $categoria = $prueba['categoria_nombre'] ?? 'Sin categoría';
                                    if (!isset($pruebasPorCategoria[$categoria])) {
                                        $pruebasPorCategoria[$categoria] = [];
                                    }
                                    $pruebasPorCategoria[$categoria][] = $prueba;
                                }
                                
                                // Obtener IDs de pruebas ya asignadas/completadas
                                $pruebasYaAsignadas = [];
                                foreach ($pruebasAsignadas as $prueba) {
                                    $pruebasYaAsignadas[] = $prueba['prueba_id'];
                                }
                                foreach ($pruebasCompletadas as $prueba) {
                                    $pruebasYaAsignadas[] = $prueba['prueba_id'];
                                }
                                foreach ($pruebasEnProgreso as $prueba) {
                                    $pruebasYaAsignadas[] = $prueba['prueba_id'];
                                }
                                ?>
                                
                                <div class="row">
                                    <?php foreach ($pruebasPorCategoria as $categoria => $pruebas): ?>
                                    <div class="col-md-6 mb-3">
                                        <div class="card h-100">
                                            <div class="card-header">
                                                <?php echo htmlspecialchars($categoria); ?>
                                            </div>
                                            <div class="card-body">
                                                <?php foreach ($pruebas as $prueba): ?>
                                                <div class="form-check mb-2">
                                                    <input class="form-check-input" type="checkbox" name="pruebas[]" 
                                                           id="prueba-<?php echo $prueba['id']; ?>"
                                                           value="<?php echo $prueba['id']; ?>"
                                                           <?php echo in_array($prueba['id'], $pruebasYaAsignadas) ? 'disabled checked' : ''; ?>>
                                                    <label class="form-check-label <?php echo in_array($prueba['id'], $pruebasYaAsignadas) ? 'text-muted' : ''; ?>" for="prueba-<?php echo $prueba['id']; ?>">
                                                        <?php echo htmlspecialchars($prueba['titulo']); ?>
                                                        <?php if (in_array($prueba['id'], $pruebasYaAsignadas)): ?>
                                                        <small class="text-muted">(Ya asignada)</small>
                                                        <?php endif; ?>
                                                    </label>
                                                    <?php if (!empty($prueba['descripcion'])): ?>
                                                    <small class="form-text text-muted d-block">
                                                        <?php echo htmlspecialchars(substr($prueba['descripcion'], 0, 100) . (strlen($prueba['descripcion']) > 100 ? '...' : '')); ?>
                                                    </small>
                                                    <?php endif; ?>
                                                </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-check"></i> Asignar Pruebas Seleccionadas
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