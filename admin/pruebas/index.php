<?php
/**
 * Panel de Administración para SolFis
 * admin/pruebas/index.php - Gestión de evaluaciones psicométricas
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

// Parámetros de paginación y filtrado
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$categoria = isset($_GET['categoria']) ? (int)$_GET['categoria'] : 0;
$q = isset($_GET['q']) ? $_GET['q'] : '';
$estado = isset($_GET['estado']) ? $_GET['estado'] : '';
$per_page = 10;

// Obtener todas las pruebas
$tests = $testManager->getAllTests();

// Filtrar pruebas según parámetros (implementación básica)
$filteredTests = [];
foreach ($tests as $test) {
    $includeTest = true;
    
    // Filtrar por búsqueda
    if (!empty($q)) {
        if (stripos($test['titulo'], $q) === false && stripos($test['descripcion'], $q) === false) {
            $includeTest = false;
        }
    }
    
    // Filtrar por categoría
    if (!empty($categoria) && $test['categoria_id'] != $categoria) {
        $includeTest = false;
    }
    
    // Filtrar por estado
    if (!empty($estado) && $test['estado'] != $estado) {
        $includeTest = false;
    }
    
    if ($includeTest) {
        $filteredTests[] = $test;
    }
}

// Paginación manual de las pruebas filtradas
$totalTests = count($filteredTests);
$totalPages = ceil($totalTests / $per_page);
$page = max(1, min($page, $totalPages > 0 ? $totalPages : 1));
$startIndex = ($page - 1) * $per_page;
$pagedTests = array_slice($filteredTests, $startIndex, $per_page);

// Obtener categorías para el filtro
$categories = $testManager->getTestCategories();

// Obtener estadísticas
$db = Database::getInstance();

// Total de sesiones completadas
$completedSessionsQuery = "SELECT COUNT(*) as total FROM sesiones_prueba WHERE estado = 'completada'";
$result = $db->query($completedSessionsQuery);
$completedSessions = $result && $result->num_rows > 0 ? $result->fetch_assoc()['total'] : 0;

// Total de sesiones en progreso
$inProgressSessionsQuery = "SELECT COUNT(*) as total FROM sesiones_prueba WHERE estado = 'en_progreso'";
$result = $db->query($inProgressSessionsQuery);
$inProgressSessions = $result && $result->num_rows > 0 ? $result->fetch_assoc()['total'] : 0;

// Candidatos evaluados
$evaluatedCandidatesQuery = "SELECT COUNT(DISTINCT candidato_id) as total FROM sesiones_prueba";
$result = $db->query($evaluatedCandidatesQuery);
$evaluatedCandidates = $result && $result->num_rows > 0 ? $result->fetch_assoc()['total'] : 0;

// Puntuación promedio
$avgScoreQuery = "SELECT AVG(resultado_global) as promedio FROM sesiones_prueba WHERE estado = 'completada' AND resultado_global IS NOT NULL";
$result = $db->query($avgScoreQuery);
$avgScore = $result && $result->num_rows > 0 ? round($result->fetch_assoc()['promedio']) : 0;

// Top 5 pruebas más realizadas
$topTestsQuery = "SELECT p.id, p.titulo, COUNT(s.id) as total_completadas 
                 FROM pruebas p 
                 JOIN sesiones_prueba s ON p.id = s.prueba_id 
                 WHERE s.estado = 'completada' 
                 GROUP BY p.id 
                 ORDER BY total_completadas DESC 
                 LIMIT 5";
$result = $db->query($topTestsQuery);
$topTests = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $topTests[] = $row;
    }
}

// Título de la página
$pageTitle = 'Gestión de Evaluaciones - Panel de Administración';

// Procesar acciones (activar/desactivar pruebas)
if (isset($_POST['action']) && isset($_POST['test_id'])) {
    $testId = (int)$_POST['test_id'];
    $action = $_POST['action'];
    
    switch ($action) {
        case 'activate':
            // Activar prueba
            $db->query("UPDATE pruebas SET estado = 'activa', updated_at = NOW() WHERE id = $testId");
            $_SESSION['success'] = "La prueba ha sido activada correctamente.";
            break;
        case 'deactivate':
            // Desactivar prueba
            $db->query("UPDATE pruebas SET estado = 'inactiva', updated_at = NOW() WHERE id = $testId");
            $_SESSION['success'] = "La prueba ha sido desactivada correctamente.";
            break;
        case 'delete':
            // Verificar si tiene sesiones asociadas
            $checkQuery = "SELECT COUNT(*) as total FROM sesiones_prueba WHERE prueba_id = $testId";
            $checkResult = $db->query($checkQuery);
            $hasSessions = $checkResult && $checkResult->fetch_assoc()['total'] > 0;
            
            if ($hasSessions) {
                $_SESSION['error'] = "No se puede eliminar la prueba porque tiene sesiones asociadas.";
            } else {
                // Eliminar preguntas asociadas
                $db->query("DELETE FROM preguntas WHERE prueba_id = $testId");
                
                // Eliminar prueba
                $db->query("DELETE FROM pruebas WHERE id = $testId");
                $_SESSION['success'] = "La prueba ha sido eliminada correctamente.";
            }
            break;
    }
    
    // Redireccionar para evitar reenvío del formulario
    header('Location: index.php');
    exit;
}
?>

<?php include '../includes/header.php'; ?>

<div class="admin-main">
    <div class="container-fluid">
        <div class="row">
            <?php include '../includes/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Gestión de Evaluaciones Psicométricas</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="nueva-prueba.php" class="btn btn-sm btn-primary">
                            <i class="fas fa-plus"></i> Nueva Evaluación
                        </a>
                        <a href="categorias.php" class="btn btn-sm btn-outline-secondary ms-2">
                            <i class="fas fa-folder"></i> Categorías
                        </a>
                        <a href="resultados.php" class="btn btn-sm btn-outline-info ms-2">
                            <i class="fas fa-chart-bar"></i> Resultados
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
                
                <!-- Tarjetas de estadísticas -->
                <div class="row mb-4">
                    <div class="col-md-3 mb-3">
                        <div class="card h-100 border-0 shadow-sm">
                            <div class="card-body d-flex align-items-center">
                                <div class="icon-box bg-primary text-white">
                                    <i class="fas fa-clipboard-check"></i>
                                </div>
                                <div class="ms-3">
                                    <h6 class="mb-0">Pruebas</h6>
                                    <h3 class="mb-0"><?php echo count($tests); ?></h3>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card h-100 border-0 shadow-sm">
                            <div class="card-body d-flex align-items-center">
                                <div class="icon-box bg-success text-white">
                                    <i class="fas fa-tasks"></i>
                                </div>
                                <div class="ms-3">
                                    <h6 class="mb-0">Sesiones Completadas</h6>
                                    <h3 class="mb-0"><?php echo $completedSessions; ?></h3>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card h-100 border-0 shadow-sm">
                            <div class="card-body d-flex align-items-center">
                                <div class="icon-box bg-info text-white">
                                    <i class="fas fa-user-graduate"></i>
                                </div>
                                <div class="ms-3">
                                    <h6 class="mb-0">Candidatos Evaluados</h6>
                                    <h3 class="mb-0"><?php echo $evaluatedCandidates; ?></h3>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card h-100 border-0 shadow-sm">
                            <div class="card-body d-flex align-items-center">
                                <div class="icon-box bg-warning text-white">
                                    <i class="fas fa-star"></i>
                                </div>
                                <div class="ms-3">
                                    <h6 class="mb-0">Puntuación Promedio</h6>
                                    <h3 class="mb-0"><?php echo $avgScore; ?>%</h3>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Filtros -->
                <div class="card mb-4">
                    <div class="card-header bg-light d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-filter"></i> Filtros
                        </h5>
                        <button class="btn btn-sm btn-link" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFilters" aria-expanded="true" aria-controls="collapseFilters">
                            <i class="fas fa-chevron-down"></i>
                        </button>
                    </div>
                    <div class="collapse show" id="collapseFilters">
                        <div class="card-body">
                            <form action="index.php" method="get" class="row g-3">
                                <div class="col-md-4">
                                    <label for="q" class="form-label">Buscar</label>
                                    <div class="search-box">
                                        <i class="fas fa-search"></i>
                                        <input type="text" class="form-control" id="q" name="q" value="<?php echo htmlspecialchars($q); ?>" placeholder="Buscar por título o descripción...">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <label for="categoria" class="form-label">Categoría</label>
                                    <select class="form-select" id="categoria" name="categoria">
                                        <option value="">Todas las categorías</option>
                                        <?php foreach ($categories as $cat): ?>
                                        <option value="<?php echo $cat['id']; ?>" <?php echo $categoria == $cat['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($cat['nombre']); ?> (<?php echo $cat['pruebas_count']; ?>)
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label for="estado" class="form-label">Estado</label>
                                    <select class="form-select" id="estado" name="estado">
                                        <option value="">Todos los estados</option>
                                        <option value="activa" <?php echo $estado === 'activa' ? 'selected' : ''; ?>>Activa</option>
                                        <option value="inactiva" <?php echo $estado === 'inactiva' ? 'selected' : ''; ?>>Inactiva</option>
                                    </select>
                                </div>
                                <div class="col-md-2 d-flex align-items-end">
                                    <div>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-search"></i> Filtrar
                                        </button>
                                        <?php if ($q || $categoria || $estado): ?>
                                        <a href="index.php" class="btn btn-outline-secondary ms-2">
                                            <i class="fas fa-times"></i> Limpiar
                                        </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Lista de Pruebas -->
                <div class="row mb-4">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header bg-light">
                                <h5 class="card-title mb-0">Pruebas Disponibles</h5>
                            </div>
                            <div class="card-body">
                                <?php if (empty($pagedTests)): ?>
                                <div class="alert alert-info">
                                    No se encontraron pruebas que coincidan con los criterios de búsqueda.
                                </div>
                                <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Título</th>
                                                <th>Categoría</th>
                                                <th>Preguntas</th>
                                                <th>Tiempo Estimado</th>
                                                <th>Estado</th>
                                                <th>Completadas</th>
                                                <th>Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($pagedTests as $test): 
                                                // Contar preguntas en la prueba
                                                $questionCountQuery = "SELECT COUNT(*) as total FROM preguntas WHERE prueba_id = {$test['id']} AND activa = 1";
                                                $questionResult = $db->query($questionCountQuery);
                                                $questionCount = 0;
                                                if ($questionResult && $questionResult->num_rows > 0) {
                                                    $questionCount = $questionResult->fetch_assoc()['total'];
                                                }
                                                
                                                // Contar sesiones completadas para esta prueba
                                                $completedForTestQuery = "SELECT COUNT(*) as total FROM sesiones_prueba WHERE prueba_id = {$test['id']} AND estado = 'completada'";
                                                $completedForTestResult = $db->query($completedForTestQuery);
                                                $completedForTest = 0;
                                                if ($completedForTestResult && $completedForTestResult->num_rows > 0) {
                                                    $completedForTest = $completedForTestResult->fetch_assoc()['total'];
                                                }
                                            ?>
                                            <tr>
                                                <td>
                                                    <a href="editar-prueba.php?id=<?php echo $test['id']; ?>" class="fw-bold text-decoration-none">
                                                        <?php echo htmlspecialchars($test['titulo']); ?>
                                                    </a>
                                                </td>
                                                <td>
                                                    <span class="badge bg-light text-dark">
                                                        <i class="<?php echo $test['categoria_icono']; ?>"></i>
                                                        <?php echo htmlspecialchars($test['categoria_nombre']); ?>
                                                    </span>
                                                </td>
                                                <td class="text-center"><?php echo $questionCount; ?></td>
                                                <td><?php echo $test['tiempo_estimado']; ?> min</td>
                                                <td>
                                                    <?php if ($test['estado'] == 'activa'): ?>
                                                        <span class="badge bg-success">Activa</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary">Inactiva</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge bg-primary"><?php echo $completedForTest; ?></span>
                                                </td>
                                                <td>
                                                    <div class="btn-group">
                                                        <a href="editar-prueba.php?id=<?php echo $test['id']; ?>" class="btn btn-sm btn-outline-primary" title="Editar">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <a href="preguntas.php?test_id=<?php echo $test['id']; ?>" class="btn btn-sm btn-outline-info" title="Gestionar preguntas">
                                                            <i class="fas fa-question-circle"></i>
                                                        </a>
                                                        <a href="resultados.php?test_id=<?php echo $test['id']; ?>" class="btn btn-sm btn-outline-success" title="Ver resultados">
                                                            <i class="fas fa-chart-bar"></i>
                                                        </a>
                                                        
                                                        <?php if ($test['estado'] == 'activa'): ?>
                                                        <form action="index.php" method="post" class="d-inline" onsubmit="return confirm('¿Estás seguro de desactivar esta prueba?');">
                                                            <input type="hidden" name="test_id" value="<?php echo $test['id']; ?>">
                                                            <input type="hidden" name="action" value="deactivate">
                                                            <button type="submit" class="btn btn-sm btn-outline-warning" title="Desactivar">
                                                                <i class="fas fa-toggle-off"></i>
                                                            </button>
                                                        </form>
                                                        <?php else: ?>
                                                        <form action="index.php" method="post" class="d-inline">
                                                            <input type="hidden" name="test_id" value="<?php echo $test['id']; ?>">
                                                            <input type="hidden" name="action" value="activate">
                                                            <button type="submit" class="btn btn-sm btn-outline-primary" title="Activar">
                                                                <i class="fas fa-toggle-on"></i>
                                                            </button>
                                                        </form>
                                                        <?php endif; ?>
                                                        
                                                        <?php if ($completedForTest == 0): ?>
                                                        <form action="index.php" method="post" class="d-inline" onsubmit="return confirm('¿Estás seguro de eliminar esta prueba? Esta acción no se puede deshacer.');">
                                                            <input type="hidden" name="test_id" value="<?php echo $test['id']; ?>">
                                                            <input type="hidden" name="action" value="delete">
                                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Eliminar">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </form>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                
                                <!-- Paginación -->
                                <?php if ($totalPages > 1): ?>
                                <nav aria-label="Paginación de pruebas">
                                    <ul class="pagination justify-content-center mt-4">
                                        <?php if ($page > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo $q ? '&q=' . urlencode($q) : ''; ?><?php echo $categoria ? '&categoria=' . urlencode($categoria) : ''; ?><?php echo $estado ? '&estado=' . urlencode($estado) : ''; ?>">
                                                <i class="fas fa-chevron-left"></i>
                                            </a>
                                        </li>
                                        <?php else: ?>
                                        <li class="page-item disabled">
                                            <span class="page-link"><i class="fas fa-chevron-left"></i></span>
                                        </li>
                                        <?php endif; ?>
                                        
                                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                        <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                            <a class="page-link" href="?page=<?php echo $i; ?><?php echo $q ? '&q=' . urlencode($q) : ''; ?><?php echo $categoria ? '&categoria=' . urlencode($categoria) : ''; ?><?php echo $estado ? '&estado=' . urlencode($estado) : ''; ?>">
                                                <?php echo $i; ?>
                                            </a>
                                        </li>
                                        <?php endfor; ?>
                                        
                                        <?php if ($page < $totalPages): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo $q ? '&q=' . urlencode($q) : ''; ?><?php echo $categoria ? '&categoria=' . urlencode($categoria) : ''; ?><?php echo $estado ? '&estado=' . urlencode($estado) : ''; ?>">
                                                <i class="fas fa-chevron-right"></i>
                                            </a>
                                        </li>
                                        <?php else: ?>
                                        <li class="page-item disabled">
                                            <span class="page-link"><i class="fas fa-chevron-right"></i></span>
                                        </li>
                                        <?php endif; ?>
                                    </ul>
                                </nav>
                                <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Análisis de Evaluaciones -->
                <div class="row mb-4">
                    <!-- Top 5 pruebas más realizadas -->
                    <div class="col-md-6 mb-4">
                        <div class="card h-100">
                            <div class="card-header bg-light">
                                <h5 class="card-title mb-0">Pruebas Más Realizadas</h5>
                            </div>
                            <div class="card-body">
                                <?php if (empty($topTests)): ?>
                                <div class="alert alert-info">No hay datos suficientes para mostrar estadísticas.</div>
                                <?php else: ?>
                                <div class="chart-container" style="height: 300px;">
                                    <canvas id="topTestsChart"></canvas>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Estado de las sesiones -->
                    <div class="col-md-6 mb-4">
                        <div class="card h-100">
                            <div class="card-header bg-light">
                                <h5 class="card-title mb-0">Estado de Sesiones</h5>
                            </div>
                            <div class="card-body">
                                <?php if ($completedSessions == 0 && $inProgressSessions == 0): ?>
                                <div class="alert alert-info">No hay datos suficientes para mostrar estadísticas.</div>
                                <?php else: ?>
                                <div class="chart-container" style="height: 300px;">
                                    <canvas id="sessionsStatusChart"></canvas>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
</div>

<!-- Agregar scripts necesarios para los gráficos -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Gráfico de Top 5 pruebas más realizadas
    <?php if (!empty($topTests)): ?>
    const topTestsCtx = document.getElementById('topTestsChart').getContext('2d');
    const topTestsChart = new Chart(topTestsCtx, {
        type: 'bar',
        data: {
            labels: [
                <?php foreach ($topTests as $test): ?>
                "<?php echo addslashes($test['titulo']); ?>",
                <?php endforeach; ?>
            ],
            datasets: [{
                label: 'Veces completada',
                data: [
                    <?php foreach ($topTests as $test): ?>
                    <?php echo $test['total_completadas']; ?>,
                    <?php endforeach; ?>
                ],
                backgroundColor: 'rgba(54, 162, 235, 0.7)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Número de veces completada'
                    }
                },
                x: {
                    title: {
                        display: true,
                        text: 'Prueba'
                    }
                }
            }
        }
    });
    <?php endif; ?>
    
    // Gráfico de estado de sesiones
    <?php if ($completedSessions > 0 || $inProgressSessions > 0): ?>
    const sessionsStatusCtx = document.getElementById('sessionsStatusChart').getContext('2d');
    const sessionsStatusChart = new Chart(sessionsStatusCtx, {
        type: 'doughnut',
        data: {
            labels: ['Completadas', 'En Progreso'],
            datasets: [{
                data: [<?php echo $completedSessions; ?>, <?php echo $inProgressSessions; ?>],
                backgroundColor: [
                    'rgba(40, 167, 69, 0.7)',  // Verde para completadas
                    'rgba(23, 162, 184, 0.7)'  // Azul para en progreso
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
    <?php endif; ?>
});
</script>

<!-- Agregar el archivo CSS para el admin -->
<link rel="stylesheet" href="../css/admin.css">

<style>
/* Estilos específicos para la gestión de pruebas */
.icon-box {
    width: 60px;
    height: 60px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
}

.search-box {
    position: relative;
}

.search-box i {
    position: absolute;
    left: 10px;
    top: 50%;
    transform: translateY(-50%);
    color: #6c757d;
}

.search-box .form-control {
    padding-left: 30px;
}
</style>

<?php include '../includes/footer.php'; ?>