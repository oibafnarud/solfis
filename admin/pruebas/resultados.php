<?php
/**
 * Panel de Administración para SolFis
 * admin/pruebas/resultados.php - Visualización de resultados de evaluaciones
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

// Parámetros de filtrado
$test_id = isset($_GET['test_id']) ? (int)$_GET['test_id'] : 0;
$candidato_id = isset($_GET['candidato_id']) ? (int)$_GET['candidato_id'] : 0;
$fecha_inicio = isset($_GET['fecha_inicio']) ? $_GET['fecha_inicio'] : '';
$fecha_fin = isset($_GET['fecha_fin']) ? $_GET['fecha_fin'] : '';
$min_score = isset($_GET['min_score']) ? (int)$_GET['min_score'] : 0;
$max_score = isset($_GET['max_score']) ? (int)$_GET['max_score'] : 100;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;

// Conexión a la base de datos
$db = Database::getInstance();

// Obtener información de la prueba específica si se seleccionó una
$test = null;
if ($test_id > 0) {
    $test = $testManager->getTestById($test_id);
}

// Obtener todas las pruebas para el filtro
$allTests = $testManager->getAllTests();

// Obtener lista de candidatos para el filtro
$candidatesQuery = "SELECT id, CONCAT(nombre, ' ', apellido) as nombre_completo, email 
                   FROM candidatos 
                   ORDER BY nombre_completo ASC";
$candidatesResult = $db->query($candidatesQuery);
$candidates = [];
if ($candidatesResult) {
    while ($row = $candidatesResult->fetch_assoc()) {
        $candidates[$row['id']] = $row;
    }
}

// Construir consulta para resultados
$sql = "SELECT s.id, s.candidato_id, s.prueba_id, s.fecha_inicio, s.fecha_fin, s.resultado_global,
               c.nombre as nombre_candidato, c.apellido as apellido_candidato, c.email as email_candidato,
               p.titulo as titulo_prueba
        FROM sesiones_prueba s
        JOIN candidatos c ON s.candidato_id = c.id
        JOIN pruebas p ON s.prueba_id = p.id
        WHERE s.estado = 'completada'";

// Aplicar filtros
$params = [];
if ($test_id > 0) {
    $sql .= " AND s.prueba_id = $test_id";
}

if ($candidato_id > 0) {
    $sql .= " AND s.candidato_id = $candidato_id";
}

if (!empty($fecha_inicio)) {
    $fecha_inicio_sql = $db->escape($fecha_inicio);
    $sql .= " AND DATE(s.fecha_fin) >= '$fecha_inicio_sql'";
}

if (!empty($fecha_fin)) {
    $fecha_fin_sql = $db->escape($fecha_fin);
    $sql .= " AND DATE(s.fecha_fin) <= '$fecha_fin_sql'";
}

if ($min_score > 0) {
    $sql .= " AND s.resultado_global >= $min_score";
}

if ($max_score < 100) {
    $sql .= " AND s.resultado_global <= $max_score";
}

// Consulta para contar total de resultados
$countSql = str_replace("SELECT s.id, s.candidato_id, s.prueba_id, s.fecha_inicio, s.fecha_fin, s.resultado_global,
               c.nombre as nombre_candidato, c.apellido as apellido_candidato, c.email as email_candidato,
               p.titulo as titulo_prueba, p.nivel_dificultad", "SELECT COUNT(*) as total", $sql);
$countResult = $db->query($countSql);
$totalResults = ($countResult && $countResult->num_rows > 0) ? $countResult->fetch_assoc()['total'] : 0;
$totalPages = ceil($totalResults / $per_page);

// Ordenar y paginar
$sql .= " ORDER BY s.fecha_fin DESC LIMIT " . (($page - 1) * $per_page) . ", $per_page";

// Ejecutar consulta
$result = $db->query($sql);
$results = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $results[] = $row;
    }
}

// Datos para gráficos
$distributionQuery = "SELECT 
                      CASE 
                      WHEN resultado_global >= 90 THEN 'Sobresaliente (90-100%)' 
                      WHEN resultado_global >= 75 THEN 'Avanzado (75-89%)' 
                      WHEN resultado_global >= 60 THEN 'Competente (60-74%)' 
                      WHEN resultado_global >= 40 THEN 'En desarrollo (40-59%)' 
                      ELSE 'Inicial (0-39%)' 
                      END as grupo, 
                      COUNT(*) as total 
                      FROM sesiones_prueba 
                      WHERE estado = 'completada' AND resultado_global IS NOT NULL";

if ($test_id > 0) {
    $distributionQuery .= " AND prueba_id = $test_id";
}

$distributionQuery .= " GROUP BY grupo ORDER BY MIN(resultado_global)";

$distributionResult = $db->query($distributionQuery);
$distribution = [];
if ($distributionResult) {
    while ($row = $distributionResult->fetch_assoc()) {
        $distribution[] = $row;
    }
}

// Título de la página
$pageTitle = 'Resultados de Evaluaciones - Panel de Administración';

// Procesamiento para exportación de datos
if (isset($_POST['export']) && $_POST['export'] === 'csv') {
    // Construir consulta sin límites para exportar todo
	$exportSql = "SELECT s.id, s.candidato_id, s.prueba_id, s.fecha_inicio, s.fecha_fin, s.resultado_global,
						 c.nombre as nombre_candidato, c.apellido as apellido_candidato, c.email as email_candidato,
						 p.titulo as titulo_prueba, p.categoria_id,
						 cat.nombre as categoria_nombre
				  FROM sesiones_prueba s
				  JOIN candidatos c ON s.candidato_id = c.id
				  JOIN pruebas p ON s.prueba_id = p.id
				  LEFT JOIN pruebas_categorias cat ON p.categoria_id = cat.id
				  WHERE s.estado = 'completada'";
    
    // Aplicar filtros
    if ($test_id > 0) {
        $exportSql .= " AND s.prueba_id = $test_id";
    }
    
    if ($candidato_id > 0) {
        $exportSql .= " AND s.candidato_id = $candidato_id";
    }
    
    if (!empty($fecha_inicio)) {
        $fecha_inicio_sql = $db->escape($fecha_inicio);
        $exportSql .= " AND DATE(s.fecha_fin) >= '$fecha_inicio_sql'";
    }
    
    if (!empty($fecha_fin)) {
        $fecha_fin_sql = $db->escape($fecha_fin);
        $exportSql .= " AND DATE(s.fecha_fin) <= '$fecha_fin_sql'";
    }
    
    if ($min_score > 0) {
        $exportSql .= " AND s.resultado_global >= $min_score";
    }
    
    if ($max_score < 100) {
        $exportSql .= " AND s.resultado_global <= $max_score";
    }
    
    $exportSql .= " ORDER BY s.fecha_fin DESC";
    
    $exportResult = $db->query($exportSql);
    
    if ($exportResult) {
        // Encabezados para el CSV
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=resultados_evaluaciones_'.date('Y-m-d').'.csv');
        
        // Abrir output para escritura
        $output = fopen('php://output', 'w');
        
        // Encabezados de columnas
        fputcsv($output, [
            'ID',
            'Nombre Candidato',
            'Email Candidato',
            'Prueba',
            'Categoría',
            'Dificultad',
            'Fecha Inicio',
            'Fecha Fin',
            'Duración (min)',
            'Resultado',
            'Perfil'
        ]);
        
        // Datos
        while ($row = $exportResult->fetch_assoc()) {
            // Calcular duración
            $inicio = new DateTime($row['fecha_inicio']);
            $fin = new DateTime($row['fecha_fin']);
            $duracion = $inicio->diff($fin);
            $minutos = $duracion->days * 24 * 60 + $duracion->h * 60 + $duracion->i;
            
            // Determinar perfil
            $perfil = 'Inicial';
            if ($row['resultado_global'] >= 90) {
                $perfil = 'Sobresaliente';
            } elseif ($row['resultado_global'] >= 75) {
                $perfil = 'Avanzado';
            } elseif ($row['resultado_global'] >= 60) {
                $perfil = 'Competente';
            } elseif ($row['resultado_global'] >= 40) {
                $perfil = 'En desarrollo';
            }
            
            fputcsv($output, [
                $row['id'],
                $row['nombre_candidato'] . ' ' . $row['apellido_candidato'],
                $row['email_candidato'],
                $row['titulo_prueba'],
                $row['categoria_nombre'],
                ucfirst($row['nivel_dificultad']),
                $row['fecha_inicio'],
                $row['fecha_fin'],
                $minutos,
                $row['resultado_global'],
                $perfil
            ]);
        }
        
        fclose($output);
        exit;
    }
}

// Si se solicita ver detalles de un resultado específico
$session_id = isset($_GET['session_id']) ? (int)$_GET['session_id'] : 0;
$sessionDetails = null;

if ($session_id > 0) {
    // Consulta para obtener detalles de la sesión
	$sessionQuery = "SELECT s.*, 
						  c.nombre as nombre_candidato, c.apellido as apellido_candidato, c.email as email_candidato,
						  p.titulo as titulo_prueba, p.categoria_id
					 FROM sesiones_prueba s
					 JOIN candidatos c ON s.candidato_id = c.id
					 JOIN pruebas p ON s.prueba_id = p.id
					 WHERE s.id = $session_id AND s.estado = 'completada'";
    
    $sessionResult = $db->query($sessionQuery);
    
    if ($sessionResult && $sessionResult->num_rows > 0) {
        $sessionDetails = $sessionResult->fetch_assoc();
        
        // Obtener resultados por dimensión si hay datos
        $dimensionQuery = "SELECT rd.*, d.nombre as dimension_nombre, d.descripcion as dimension_descripcion
                          FROM resultados_dimensiones rd
                          JOIN dimensiones d ON rd.dimension_id = d.id
                          WHERE rd.sesion_id = $session_id
                          ORDER BY rd.valor DESC";
        
        $dimensionResult = $db->query($dimensionQuery);
        $dimensionResults = [];
        
        if ($dimensionResult && $dimensionResult->num_rows > 0) {
            while ($row = $dimensionResult->fetch_assoc()) {
                $dimensionResults[] = $row;
            }
        }
        
        // Asignar resultados por dimensión a los detalles de la sesión
        $sessionDetails['dimensiones'] = $dimensionResults;
    }
}
?>

<?php include '../includes/header.php'; ?>

<div class="admin-main">
    <div class="container-fluid">
        <div class="row">
            <?php include '../includes/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <?php if ($sessionDetails): ?>
                <!-- Mostrar detalles de un resultado específico -->
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Detalles del Resultado</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="resultados.php<?php echo isset($_GET['return']) ? '?' . $_GET['return'] : ''; ?>" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-arrow-left"></i> Volver a Resultados
                        </a>
                    </div>
                </div>
                
                <div class="row mb-4">
                    <div class="col-md-8">
                        <!-- Tarjeta con información del candidato y la prueba -->
                        <div class="card mb-4">
                            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                                <h5 class="card-title mb-0"><i class="fas fa-info-circle"></i> Información General</h5>
                                <a href="../candidatos/detalle.php?id=<?php echo $sessionDetails['candidato_id']; ?>" class="btn btn-sm btn-outline-light">
                                    <i class="fas fa-user"></i> Ver Perfil
                                </a>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <h6>Información del Candidato</h6>
                                        <ul class="list-unstyled">
                                            <li><strong>Nombre:</strong> <?php echo htmlspecialchars($sessionDetails['nombre_candidato'] . ' ' . $sessionDetails['apellido_candidato']); ?></li>
                                            <li><strong>Email:</strong> <?php echo htmlspecialchars($sessionDetails['email_candidato']); ?></li>
                                        </ul>
                                    </div>
                                    <div class="col-md-6">
                                        <h6>Información de la Prueba</h6>
                                        <ul class="list-unstyled">
                                            <li><strong>Título:</strong> <?php echo htmlspecialchars($sessionDetails['titulo_prueba']); ?></li>
                                            <li><strong>Dificultad:</strong> <?php echo ucfirst($sessionDetails['nivel_dificultad']); ?></li>
                                        </ul>
                                    </div>
                                </div>
                                <hr>
                                <div class="row">
                                    <div class="col-md-6">
                                        <h6>Detalles de la Sesión</h6>
                                        <ul class="list-unstyled">
                                            <li><strong>Fecha de inicio:</strong> <?php echo date('d/m/Y H:i', strtotime($sessionDetails['fecha_inicio'])); ?></li>
                                            <li><strong>Fecha de finalización:</strong> <?php echo date('d/m/Y H:i', strtotime($sessionDetails['fecha_fin'])); ?></li>
                                            <li><strong>Duración:</strong> 
                                                <?php 
                                                $inicio = new DateTime($sessionDetails['fecha_inicio']);
                                                $fin = new DateTime($sessionDetails['fecha_fin']);
                                                $duracion = $inicio->diff($fin);
                                                echo $duracion->format('%H:%I:%S'); 
                                                ?>
                                            </li>
                                        </ul>
                                    </div>
                                    <div class="col-md-6">
                                        <?php
                                        // Determinar perfil según el resultado global
                                        $resultado = $sessionDetails['resultado_global'];
                                        $perfil = 'Inicial';
                                        $perfilClass = 'bg-danger';
                                        
                                        if ($resultado >= 90) {
                                            $perfil = 'Sobresaliente';
                                            $perfilClass = 'bg-success';
                                        } elseif ($resultado >= 75) {
                                            $perfil = 'Avanzado';
                                            $perfilClass = 'bg-primary';
                                        } elseif ($resultado >= 60) {
                                            $perfil = 'Competente';
                                            $perfilClass = 'bg-info';
                                        } elseif ($resultado >= 40) {
                                            $perfil = 'En desarrollo';
                                            $perfilClass = 'bg-warning';
                                        }
                                        ?>
                                        <h6>Resultado Global</h6>
                                        <div class="d-flex align-items-center mb-3">
                                            <div class="result-score-circle">
                                                <span class="result-score-value"><?php echo $resultado; ?>%</span>
                                            </div>
                                            <div class="ms-3">
                                                <div class="profile-type-badge <?php echo $perfilClass; ?> mb-2"><?php echo $perfil; ?></div>
                                                <div class="small text-muted"><?php echo date('d/m/Y', strtotime($sessionDetails['fecha_fin'])); ?></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Resultados por dimensión si existen -->
                        <?php if (!empty($sessionDetails['dimensiones'])): ?>
                        <div class="card">
                            <div class="card-header bg-light">
                                <h5 class="card-title mb-0"><i class="fas fa-chart-pie"></i> Resultados por Dimensión</h5>
                            </div>
                            <div class="card-body">
                                <div class="chart-container mb-4" style="height: 250px;">
                                    <canvas id="dimensionsChart"></canvas>
                                </div>
                                
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Dimensión</th>
                                                <th>Descripción</th>
                                                <th class="text-center">Puntuación</th>
                                                <th>Nivel</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($sessionDetails['dimensiones'] as $dimension): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($dimension['dimension_nombre']); ?></td>
                                                <td><small><?php echo htmlspecialchars($dimension['dimension_descripcion']); ?></small></td>
                                                <td class="text-center">
                                                    <?php
                                                    $dimensionScore = $dimension['valor'];
                                                    $dimensionClass = 'bg-danger';
                                                    
                                                    if ($dimensionScore >= 90) {
                                                        $dimensionClass = 'bg-success';
                                                    } elseif ($dimensionScore >= 75) {
                                                        $dimensionClass = 'bg-primary';
                                                    } elseif ($dimensionScore >= 60) {
                                                        $dimensionClass = 'bg-info';
                                                    } elseif ($dimensionScore >= 40) {
                                                        $dimensionClass = 'bg-warning';
                                                    }
                                                    ?>
                                                    <span class="badge <?php echo $dimensionClass; ?>"><?php echo $dimensionScore; ?>%</span>
                                                </td>
                                                <td><?php echo htmlspecialchars($dimension['nivel']); ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="col-md-4">
                        <!-- Recomendaciones -->
                        <div class="card mb-4">
                            <div class="card-header bg-light">
                                <h5 class="card-title mb-0"><i class="fas fa-lightbulb"></i> Recomendaciones</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <h6><?php echo $perfil; ?></h6>
                                    
                                    <?php if ($perfil == 'Sobresaliente'): ?>
                                    <p>El candidato demuestra un dominio excepcional de las habilidades evaluadas. Posee un alto potencial para roles de liderazgo y responsabilidades avanzadas.</p>
                                    <?php elseif ($perfil == 'Avanzado'): ?>
                                    <p>El candidato muestra un nivel alto de competencia. Es adecuado para posiciones con responsabilidades significativas y puede desarrollarse hacia roles de liderazgo con la capacitación adecuada.</p>
                                    <?php elseif ($perfil == 'Competente'): ?>
                                    <p>El candidato posee un buen nivel de competencia en las habilidades evaluadas. Es adecuado para posiciones de nivel medio y tiene potencial para crecer con entrenamiento adicional.</p>
                                    <?php elseif ($perfil == 'En desarrollo'): ?>
                                    <p>El candidato muestra conocimientos básicos pero necesita desarrollo adicional. Se recomienda entrenamiento específico en las áreas donde puntuó más bajo.</p>
                                    <?php else: ?>
                                    <p>El candidato se encuentra en una etapa inicial de desarrollo. Necesita entrenamiento extensivo y supervisión cercana.</p>
                                    <?php endif; ?>
                                </div>
                                
                                <hr>
                                
                                <div class="mb-3">
                                    <h6>Próximos Pasos</h6>
                                    <ul class="small">
                                        <?php if ($resultado >= 75): ?>
                                        <li>Considerar para posiciones de mayor responsabilidad</li>
                                        <li>Avanzar en el proceso de selección a la etapa de entrevistas</li>
                                        <li>Asignar pruebas adicionales para confirmar aptitudes específicas</li>
                                        <?php elseif ($resultado >= 60): ?>
                                        <li>Programar entrevista para evaluar otras competencias</li>
                                        <li>Identificar áreas específicas para desarrollo</li>
                                        <li>Considerar para posiciones acordes a su perfil actual</li>
                                        <?php else: ?>
                                        <li>Evaluar si el perfil se ajusta a las necesidades actuales</li>
                                        <li>Sugerir entrenamiento específico en áreas de menor puntuación</li>
                                        <li>Considerar para programas de desarrollo si muestra potencial</li>
                                        <?php endif; ?>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Acciones -->
                        <div class="card">
                            <div class="card-header bg-light">
                                <h5 class="card-title mb-0"><i class="fas fa-cog"></i> Acciones</h5>
                            </div>
                            <div class="card-body">
                                <div class="d-grid gap-2">
                                    <a href="reporte.php?session_id=<?php echo $session_id; ?>" class="btn btn-outline-primary">
                                        <i class="fas fa-file-pdf"></i> Generar Reporte Detallado
                                    </a>
                                    <a href="../candidatos/detalle.php?id=<?php echo $sessionDetails['candidato_id']; ?>" class="btn btn-outline-info">
                                        <i class="fas fa-user"></i> Ver Perfil Completo
                                    </a>
                                    <a href="asignar.php?candidato_id=<?php echo $sessionDetails['candidato_id']; ?>" class="btn btn-outline-success">
                                        <i class="fas fa-clipboard-check"></i> Asignar Nueva Prueba
                                    </a>
                                    <a href="mailto:<?php echo $sessionDetails['email_candidato']; ?>" class="btn btn-outline-secondary">
                                        <i class="fas fa-envelope"></i> Contactar Candidato
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php else: ?>
                <!-- Listado de resultados -->
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Resultados de Evaluaciones</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <form action="" method="post" class="me-2">
                            <input type="hidden" name="export" value="csv">
                            <button type="submit" class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-download"></i> Exportar CSV
                            </button>
                        </form>
                        <a href="index.php" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-list"></i> Todas las Pruebas
                        </a>
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
                            <form action="" method="get" class="row g-3">
                                <div class="col-md-3">
                                    <label for="test_id" class="form-label">Prueba</label>
                                    <select class="form-select" id="test_id" name="test_id">
                                        <option value="">Todas las pruebas</option>
                                        <?php foreach ($allTests as $t): ?>
                                        <option value="<?php echo $t['id']; ?>" <?php echo $test_id == $t['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($t['titulo']); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label for="candidato_id" class="form-label">Candidato</label>
                                    <select class="form-select" id="candidato_id" name="candidato_id">
                                        <option value="">Todos los candidatos</option>
                                        <?php foreach ($candidates as $c): ?>
                                        <option value="<?php echo $c['id']; ?>" <?php echo $candidato_id == $c['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($c['nombre_completo']); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label for="fecha_inicio" class="form-label">Fecha Inicio</label>
                                    <input type="date" class="form-control" id="fecha_inicio" name="fecha_inicio" value="<?php echo $fecha_inicio; ?>">
                                </div>
                                <div class="col-md-3">
                                    <label for="fecha_fin" class="form-label">Fecha Fin</label>
                                    <input type="date" class="form-control" id="fecha_fin" name="fecha_fin" value="<?php echo $fecha_fin; ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Rango de Puntuación</label>
                                    <div class="row">
                                        <div class="col-6">
                                            <div class="input-group">
                                                <span class="input-group-text">Min</span>
                                                <input type="number" class="form-control" name="min_score" min="0" max="100" value="<?php echo $min_score; ?>">
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="input-group">
                                                <span class="input-group-text">Max</span>
                                                <input type="number" class="form-control" name="max_score" min="0" max="100" value="<?php echo $max_score; ?>">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-8 d-flex align-items-end">
                                    <div>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-search"></i> Filtrar
                                        </button>
                                        <?php if ($test_id || $candidato_id || $fecha_inicio || $fecha_fin || $min_score > 0 || $max_score < 100): ?>
                                        <a href="resultados.php" class="btn btn-outline-secondary ms-2">
                                            <i class="fas fa-times"></i> Limpiar
                                        </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                
                <div class="row mb-4">
                    <!-- Resultados -->
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header bg-light">
                                <h5 class="card-title mb-0">Resultados <?php echo $test ? 'de ' . htmlspecialchars($test['titulo']) : ''; ?></h5>
                            </div>
                            <div class="card-body">
                                <?php if (empty($results)): ?>
                                <div class="alert alert-info">
                                    No se encontraron resultados que coincidan con los criterios de búsqueda.
                                </div>
                                <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Candidato</th>
                                                <th>Prueba</th>
                                                <th>Fecha</th>
                                                <th class="text-center">Resultado</th>
                                                <th>Perfil</th>
                                                <th>Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($results as $result): 
                                                // Determinar perfil
                                                $resultScore = $result['resultado_global'];
                                                $resultPerfil = 'Inicial';
                                                $resultClass = 'bg-danger';
                                                
                                                if ($resultScore >= 90) {
                                                    $resultPerfil = 'Sobresaliente';
                                                    $resultClass = 'bg-success';
                                                } elseif ($resultScore >= 75) {
                                                    $resultPerfil = 'Avanzado';
                                                    $resultClass = 'bg-primary';
                                                } elseif ($resultScore >= 60) {
                                                    $resultPerfil = 'Competente';
                                                    $resultClass = 'bg-info';
                                                } elseif ($resultScore >= 40) {
                                                    $resultPerfil = 'En desarrollo';
                                                    $resultClass = 'bg-warning';
                                                }
                                            ?>
                                            <tr>
                                                <td>
                                                    <a href="../candidatos/detalle.php?id=<?php echo $result['candidato_id']; ?>" class="fw-bold text-decoration-none">
                                                        <?php echo htmlspecialchars($result['nombre_candidato'] . ' ' . $result['apellido_candidato']); ?>
                                                    </a>
                                                </td>
                                                <td><?php echo htmlspecialchars($result['titulo_prueba']); ?></td>
                                                <td><?php echo date('d/m/Y', strtotime($result['fecha_fin'])); ?></td>
                                                <td class="text-center">
                                                    <span class="badge <?php echo $resultClass; ?>"><?php echo $resultScore; ?>%</span>
                                                </td>
                                                <td>
                                                    <span class="profile-type-badge <?php echo $resultClass; ?>"><?php echo $resultPerfil; ?></span>
                                                </td>
                                                <td>
                                                    <div class="btn-group">
                                                        <a href="resultados.php?session_id=<?php echo $result['id']; ?>&return=<?php echo urlencode(http_build_query($_GET)); ?>" class="btn btn-sm btn-outline-primary" title="Ver detalles">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                        <a href="reporte.php?session_id=<?php echo $result['id']; ?>" class="btn btn-sm btn-outline-info" title="Generar reporte">
                                                            <i class="fas fa-file-pdf"></i>
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                
                                <!-- Paginación -->
                                <?php if ($totalPages > 1): ?>
                                <nav aria-label="Paginación de resultados">
                                    <ul class="pagination justify-content-center mt-4">
                                        <?php 
                                        // Construir parámetros de URL para paginación
                                        $params = [];
                                        if ($test_id) $params['test_id'] = $test_id;
                                        if ($candidato_id) $params['candidato_id'] = $candidato_id;
                                        if ($fecha_inicio) $params['fecha_inicio'] = $fecha_inicio;
                                        if ($fecha_fin) $params['fecha_fin'] = $fecha_fin;
                                        if ($min_score) $params['min_score'] = $min_score;
                                        if ($max_score < 100) $params['max_score'] = $max_score;
                                        
                                        $queryString = http_build_query($params);
                                        ?>
                                        
                                        <?php if ($page > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo !empty($queryString) ? '&' . $queryString : ''; ?>">
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
                                            <a class="page-link" href="?page=<?php echo $i; ?><?php echo !empty($queryString) ? '&' . $queryString : ''; ?>">
                                                <?php echo $i; ?>
                                            </a>
                                        </li>
                                        <?php endfor; ?>
                                        
                                        <?php if ($page < $totalPages): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo !empty($queryString) ? '&' . $queryString : ''; ?>">
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
                    
                    <!-- Estadísticas -->
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header bg-light">
                                <h5 class="card-title mb-0"><i class="fas fa-chart-pie"></i> Distribución de Resultados</h5>
                            </div>
                            <div class="card-body">
                                <?php if (empty($distribution)): ?>
                                <div class="alert alert-info">
                                    No hay datos suficientes para mostrar estadísticas.
                                </div>
                                <?php else: ?>
                                <div class="chart-container" style="height: 300px;">
                                    <canvas id="resultsDistributionChart"></canvas>
                                </div>
                                
                                <div class="table-responsive mt-4">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Perfil</th>
                                                <th class="text-end">Cantidad</th>
                                                <th class="text-end">Porcentaje</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $totalEvaluados = 0;
                                            foreach ($distribution as $group) {
                                                $totalEvaluados += $group['total'];
                                            }
                                            ?>
                                            <?php foreach ($distribution as $group): ?>
                                            <tr>
                                                <td><?php echo $group['grupo']; ?></td>
                                                <td class="text-end"><?php echo $group['total']; ?></td>
                                                <td class="text-end"><?php echo $totalEvaluados > 0 ? round(($group['total'] / $totalEvaluados) * 100) : 0; ?>%</td>
                                            </tr>
                                            <?php endforeach; ?>
                                            <tr class="table-light">
                                                <td><strong>Total</strong></td>
                                                <td class="text-end"><strong><?php echo $totalEvaluados; ?></strong></td>
                                                <td class="text-end">100%</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </main>
        </div>
    </div>
</div>

<!-- Agregar el archivo CSS para el admin -->
<link rel="stylesheet" href="../css/admin.css">

<style>
/* Estilos específicos para resultados */
.result-score-circle {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background-color: #f8f9fa;
    border: 4px solid;
    border-color: #0088cc;
    display: flex;
    align-items: center;
    justify-content: center;
}

.result-score-value {
    font-size: 1.8rem;
    font-weight: 700;
    color: #0088cc;
}

.profile-type-badge {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 600;
}
</style>

<!-- Scripts para gráficos -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    <?php if (!empty($distribution)): ?>
    // Gráfico de distribución de resultados
    const distributionCtx = document.getElementById('resultsDistributionChart').getContext('2d');
    const distributionChart = new Chart(distributionCtx, {
        type: 'doughnut',
        data: {
            labels: [
                <?php foreach ($distribution as $group): ?>
                "<?php echo $group['grupo']; ?>",
                <?php endforeach; ?>
            ],
            datasets: [{
                data: [
                    <?php foreach ($distribution as $group): ?>
                    <?php echo $group['total']; ?>,
                    <?php endforeach; ?>
                ],
                backgroundColor: [
                    'rgba(40, 167, 69, 0.7)',  // Verde
                    'rgba(0, 123, 255, 0.7)',  // Azul
                    'rgba(23, 162, 184, 0.7)', // Cyan
                    'rgba(255, 193, 7, 0.7)',  // Amarillo
                    'rgba(220, 53, 69, 0.7)'   // Rojo
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'right',
                    labels: {
                        boxWidth: 12,
                        padding: 10
                    }
                }
            }
        }
    });
    <?php endif; ?>
    
    <?php if (isset($sessionDetails) && !empty($sessionDetails['dimensiones'])): ?>
    // Gráfico de dimensiones de un resultado específico
    const dimensionsCtx = document.getElementById('dimensionsChart').getContext('2d');
    const dimensionsChart = new Chart(dimensionsCtx, {
        type: 'radar',
        data: {
            labels: [
                <?php foreach ($sessionDetails['dimensiones'] as $dimension): ?>
                "<?php echo addslashes($dimension['dimension_nombre']); ?>",
                <?php endforeach; ?>
            ],
            datasets: [{
                label: 'Puntuación',
                data: [
                    <?php foreach ($sessionDetails['dimensiones'] as $dimension): ?>
                    <?php echo $dimension['valor']; ?>,
                    <?php endforeach; ?>
                ],
                backgroundColor: 'rgba(54, 162, 235, 0.2)',
                borderColor: 'rgb(54, 162, 235)',
                pointBackgroundColor: 'rgb(54, 162, 235)',
                pointBorderColor: '#fff',
                pointHoverBackgroundColor: '#fff',
                pointHoverBorderColor: 'rgb(54, 162, 235)',
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                r: {
                    angleLines: {
                        display: true
                    },
                    suggestedMin: 0,
                    suggestedMax: 100,
                    ticks: {
                        stepSize: 20
                    }
                }
            }
        }
    });
    <?php endif; ?>
});
</script>

<?php include '../includes/footer.php'; ?>