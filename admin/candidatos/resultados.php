<?php
/**
 * Panel de Administración para SolFis
 * admin/candidatos/resultados.php - Visualización detallada de resultados del candidato
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

// Verificar que se proporciona un ID de candidato
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error'] = "ID de candidato no proporcionado";
    header('Location: index.php');
    exit;
}

$candidato_id = (int)$_GET['id'];

// Instanciar gestores
$candidateManager = new CandidateManager();
$testManager = null;
$hasTestManager = false;
$pruebasCompletadas = [];
$evaluationResults = [];
$dimensiones = [];
$resultados = [];

// Obtener datos del candidato
$candidato = $candidateManager->getCandidateById($candidato_id);

if (!$candidato) {
    $_SESSION['error'] = "Candidato no encontrado";
    header('Location: index.php');
    exit;
}

// Verificar si existe el TestManager para obtener resultados de pruebas
if (file_exists('../../includes/TestManager.php')) {
    require_once '../../includes/TestManager.php';
    if (class_exists('TestManager')) {
        $testManager = new TestManager();
        $hasTestManager = true;
        
        // Obtener pruebas completadas por el candidato
        try {
            $pruebasCompletadas = $testManager->getCompletedTests($candidato_id);
            
            // Obtener resultados por dimensiones
            $db = Database::getInstance();
            
            $dimensionsQuery = "SELECT d.id, d.nombre, AVG(r.valor) as promedio, 
                                CASE 
                                    WHEN AVG(r.valor) >= 90 THEN 'Excepcional' 
                                    WHEN AVG(r.valor) >= 80 THEN 'Sobresaliente'
                                    WHEN AVG(r.valor) >= 70 THEN 'Notable'
                                    WHEN AVG(r.valor) >= 60 THEN 'Adecuado' 
                                    WHEN AVG(r.valor) >= 50 THEN 'Moderado'
                                    WHEN AVG(r.valor) >= 35 THEN 'En desarrollo'
                                    ELSE 'Incipiente' 
                                END as nivel,
                                d.categoria
                                FROM resultados r
                                JOIN dimensiones d ON r.dimension_id = d.id
                                JOIN sesiones_prueba s ON r.sesion_id = s.id
                                WHERE s.candidato_id = $candidato_id AND s.estado = 'completada'
                                GROUP BY d.id
                                ORDER BY promedio DESC";
            
            $result = $db->query($dimensionsQuery);
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $evaluationResults[] = $row;
                    
                    // Clasificar dimensiones por categoría
                    if (!isset($dimensiones[$row['categoria']])) {
                        $dimensiones[$row['categoria']] = [];
                    }
                    $dimensiones[$row['categoria']][] = $row;
                }
            }
            
            // Obtener todos los resultados individuales
            $resultadosQuery = "SELECT r.*, d.nombre as dimension_nombre, d.categoria
                               FROM resultados r
                               JOIN dimensiones d ON r.dimension_id = d.id
                               JOIN sesiones_prueba s ON r.sesion_id = s.id
                               WHERE s.candidato_id = $candidato_id AND s.estado = 'completada'
                               ORDER BY r.fecha_registro DESC";
            
            $result = $db->query($resultadosQuery);
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $resultados[] = $row;
                }
            }
        } catch (Exception $e) {
            // Si hay error, registrarlo pero continuar
            error_log("Error al obtener resultados de pruebas: " . $e->getMessage());
        }
    }
}

// Calcular promedio de resultados
$promedioResultados = 0;
$countResultados = 0;

if (!empty($evaluationResults)) {
    $totalPromedio = 0;
    foreach ($evaluationResults as $result) {
        $totalPromedio += $result['promedio'];
        $countResultados++;
    }
    
    if ($countResultados > 0) {
        $promedioResultados = round($totalPromedio / $countResultados);
    }
}

// Perfiles ideales para comparación
$perfilesIdeales = [];
try {
    $db = Database::getInstance();
    $perfilesQuery = "SELECT id, titulo, descripcion FROM perfiles_ideales WHERE activo = 1 ORDER BY titulo";
    $result = $db->query($perfilesQuery);
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $perfilesIdeales[] = $row;
        }
    }
} catch (Exception $e) {
    error_log("Error al obtener perfiles ideales: " . $e->getMessage());
}

// Determinar nivel de evaluación global
function getNivelEvaluacion($valor) {
    if ($valor >= 90) return ['texto' => 'Excepcional', 'descripcion' => 'Desempeño sobresaliente, muy por encima de la media', 'color' => '#006400', 'class' => 'success'];
    else if ($valor >= 80) return ['texto' => 'Sobresaliente', 'descripcion' => 'Desempeño destacado, por encima de la media', 'color' => '#008000', 'class' => 'success'];
    else if ($valor >= 70) return ['texto' => 'Notable', 'descripcion' => 'Buen desempeño, superior a la media', 'color' => '#90EE90', 'class' => 'info'];
    else if ($valor >= 60) return ['texto' => 'Adecuado', 'descripcion' => 'Desempeño satisfactorio, cumple con lo esperado', 'color' => '#FFFF00', 'class' => 'primary'];
    else if ($valor >= 50) return ['texto' => 'Moderado', 'descripcion' => 'Desempeño aceptable, en el promedio esperado', 'color' => '#FFFFE0', 'class' => 'warning'];
    else if ($valor >= 35) return ['texto' => 'En desarrollo', 'descripcion' => 'Desempeño por debajo del promedio, necesita desarrollo', 'color' => '#FFA500', 'class' => 'warning'];
    else return ['texto' => 'Incipiente', 'descripcion' => 'Desempeño significativamente bajo, requiere atención especial', 'color' => '#FF0000', 'class' => 'danger'];
}

$nivelEvaluacion = getNivelEvaluacion($promedioResultados);

// Obtener fortalezas y debilidades
$fortalezas = [];
$debilidades = [];

foreach ($evaluationResults as $result) {
    if ($result['promedio'] >= 75) {
        $fortalezas[] = $result['nombre'] . ' (' . round($result['promedio']) . '%)';
    } else if ($result['promedio'] < 60) {
        $debilidades[] = $result['nombre'] . ' (' . round($result['promedio']) . '%)';
    }
}

// Limitar a las 5 principales fortalezas y debilidades
$fortalezas = array_slice($fortalezas, 0, 5);
$debilidades = array_slice($debilidades, 0, 5);

// Título de la página
$pageTitle = 'Resultados de Evaluación - ' . $candidato['nombre'] . ' ' . $candidato['apellido'];

// Función para obtener y calcular los índices compuestos
function getIndicesCompuestosDetallados($candidato_id) {
    $db = Database::getInstance();
    $candidato_id = (int)$candidato_id;
    
    // Consulta para obtener los índices compuestos principales
    $sql = "SELECT id, nombre, descripcion FROM indices_compuestos 
            WHERE id IN (SELECT MIN(id) FROM indices_compuestos GROUP BY nombre) 
            ORDER BY id";
    
    $result = $db->query($sql);
    $indices = [];
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            // Por cada índice, calculamos su valor basado en las dimensiones evaluadas
            $indice_id = $row['id'];
            $valor = calcularIndiceCompuesto($indice_id, $candidato_id);
            
            // Determinar nivel según el valor
            $nivel = '';
            $class = '';
            
            if ($valor >= 90) {
                $nivel = 'Excepcional';
                $class = 'success';
            } else if ($valor >= 80) {
                $nivel = 'Sobresaliente';
                $class = 'success';
            } else if ($valor >= 70) {
                $nivel = 'Notable';
                $class = 'info';
            } else if ($valor >= 60) {
                $nivel = 'Adecuado';
                $class = 'primary';
            } else if ($valor >= 50) {
                $nivel = 'Moderado';
                $class = 'warning';
            } else if ($valor >= 35) {
                $nivel = 'En desarrollo';
                $class = 'warning';
            } else {
                $nivel = 'Incipiente';
                $class = 'danger';
            }
            
            $indices[] = [
                'id' => $indice_id,
                'nombre' => $row['nombre'],
                'descripcion' => $row['descripcion'],
                'valor' => $valor,
                'nivel' => $nivel,
                'class' => $class
            ];
        }
    }
    
    return $indices;
}

// Esta función es auxiliar para calcular un índice compuesto específico
function calcularIndiceCompuesto($indice_id, $candidato_id) {
    $db = Database::getInstance();
    $indice_id = (int)$indice_id;
    $candidato_id = (int)$candidato_id;
    
    // Obtener componentes del índice
    $sql = "SELECT ic.origen_tipo, ic.origen_id, ic.ponderacion 
            FROM indices_componentes ic 
            WHERE ic.indice_id = $indice_id 
            ORDER BY ic.id";
    
    $result = $db->query($sql);
    
    if (!$result || $result->num_rows === 0) {
        // Si no hay componentes definidos, retornar valor por defecto
        return 0;
    }
    
    $totalValor = 0;
    $totalPonderacion = 0;
    
    while ($componente = $result->fetch_assoc()) {
        $valor_componente = 0;
        
        if ($componente['origen_tipo'] === 'dimension') {
            // Si es una dimensión, obtener el resultado de evaluación
            $dimension_id = (int)$componente['origen_id'];
            $sql_valor = "SELECT AVG(r.valor) as promedio 
                          FROM resultados r 
                          JOIN sesiones_prueba s ON r.sesion_id = s.id 
                          WHERE r.dimension_id = $dimension_id 
                          AND s.candidato_id = $candidato_id 
                          AND s.estado = 'completada'";
            
            $result_valor = $db->query($sql_valor);
            
            if ($result_valor && $result_valor->num_rows > 0) {
                $row_valor = $result_valor->fetch_assoc();
                $valor_componente = !is_null($row_valor['promedio']) ? floatval($row_valor['promedio']) : 0;
            }
        } else if ($componente['origen_tipo'] === 'indice') {
            // Si es otro índice, llamada recursiva
            $indice_origen_id = (int)$componente['origen_id'];
            $valor_componente = calcularIndiceCompuesto($indice_origen_id, $candidato_id);
        }
        
        $ponderacion = floatval($componente['ponderacion']);
        $totalValor += $valor_componente * $ponderacion;
        $totalPonderacion += $ponderacion;
    }
    
    // Si no hay ponderación total (error en datos), retornar 0
    if ($totalPonderacion == 0) {
        return 0;
    }
    
    // Calcular promedio ponderado y redondear
    return round($totalValor / $totalPonderacion);
}

// Obtener índices compuestos para mostrar en la pestaña de competencias
$indicesCompuestosDetallados = getIndicesCompuestosDetallados($candidato_id);

// Incluir la vista de cabecera
include '../includes/header.php';
?>

<div class="admin-main">
    <div class="container-fluid">
        <div class="row">
            <?php include '../includes/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">
                        Resultados de Evaluación - <?php echo htmlspecialchars($candidato['nombre'] . ' ' . $candidato['apellido']); ?>
                    </h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="detalle.php?id=<?php echo $candidato_id; ?>" class="btn btn-sm btn-outline-secondary me-2">
                            <i class="fas fa-arrow-left"></i> Volver al Perfil
                        </a>
						<a href="exportar-pdf.php?candidato_id=<?php echo $candidato_id; ?>" class="btn btn-sm btn-outline-primary me-2" target="_blank">
							<i class="fas fa-file-pdf"></i> Exportar PDF
						</a>
						<button type="button" class="btn btn-sm btn-outline-success" id="sendEmail">
							<i class="fas fa-envelope"></i> Enviar por Email
						</button>
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
                
                <?php if (!$hasTestManager || empty($pruebasCompletadas)): ?>
                <div class="alert alert-info alert-dismissible fade show" role="alert">
                    <i class="fas fa-info-circle me-2"></i>
                    <?php if (!$hasTestManager): ?>
                    El módulo de pruebas no está disponible o no está configurado correctamente.
                    <?php else: ?>
                    Este candidato no tiene pruebas completadas para mostrar resultados.
                    <?php endif; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                
                <div class="text-center mt-4">
                    <a href="../pruebas/asignar.php?candidato_id=<?php echo $candidato_id; ?>" class="btn btn-primary">
                        <i class="fas fa-plus-circle"></i> Asignar pruebas al candidato
                    </a>
                </div>
                <?php else: ?>
                
                <!-- Menú de navegación -->
                <ul class="nav nav-tabs" id="resultsTabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" id="summary-tab" data-bs-toggle="tab" href="#summary" role="tab">
                            <i class="fas fa-chart-pie me-2"></i> Resumen
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="cognitive-tab" data-bs-toggle="tab" href="#cognitive" role="tab">
                            <i class="fas fa-brain me-2"></i> Aptitudes Cognitivas
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="personality-tab" data-bs-toggle="tab" href="#personality" role="tab">
                            <i class="fas fa-user me-2"></i> Personalidad
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="motivations-tab" data-bs-toggle="tab" href="#motivations" role="tab">
                            <i class="fas fa-star me-2"></i> Motivaciones
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="competencies-tab" data-bs-toggle="tab" href="#competencies" role="tab">
                            <i class="fas fa-check-square me-2"></i> Competencias
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="fit-tab" data-bs-toggle="tab" href="#fit" role="tab">
                            <i class="fas fa-bullseye me-2"></i> Ajuste al Puesto
                        </a>
                    </li>
                </ul>

                <!-- Contenido de las pestañas -->
                <div class="tab-content p-3" id="resultsTabContent">
                    <!-- Pestaña de Resumen -->
                    <div class="tab-pane fade show active" id="summary" role="tabpanel">
                        <div class="row">
                            <!-- Tarjeta de Perfil -->
                            <div class="col-md-4">
                                <div class="card mb-4">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <?php if (!empty($candidato['foto_path'])): ?>
                                                <img src="<?php echo base_url($candidato['foto_path']); ?>" class="rounded-circle img-thumbnail" style="width: 150px; height: 150px; object-fit: cover;">
                                            <?php else: ?>
                                                <div class="rounded-circle bg-light d-flex align-items-center justify-content-center mx-auto" style="width: 150px; height: 150px;">
                                                    <i class="fas fa-user fa-4x text-secondary"></i>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <h4 class="card-title"><?php echo $candidato['nombre'] . ' ' . $candidato['apellido']; ?></h4>
                                        <p class="text-muted">
                                            <?php 
                                            $cargo = '';
                                            $empresa = '';
                                            
                                            // En una implementación real, obtener estos datos de la base de datos
                                            // Aquí usamos placeholder
                                            $experienciaQuery = "SELECT cargo, empresa FROM experiencia_laboral 
                                                                WHERE candidato_id = $candidato_id 
                                                                ORDER BY fecha_fin DESC, fecha_inicio DESC LIMIT 1";
                                            try {
                                                $result = $db->query($experienciaQuery);
                                                if ($result && $result->num_rows > 0) {
                                                    $exp = $result->fetch_assoc();
                                                    $cargo = htmlspecialchars($exp['cargo']);
                                                    $empresa = htmlspecialchars($exp['empresa']);
                                                }
                                            } catch (Exception $e) {
                                                // Ignorar error
                                            }
                                            
                                            echo !empty($cargo) ? $cargo : 'Sin cargo actual';
                                            echo !empty($empresa) ? ' en ' . $empresa : '';
                                            ?>
                                        </p>
                                        <div class="d-flex justify-content-center">
                                            <?php if (!empty($candidato['linkedin'])): ?>
                                                <a href="<?php echo $candidato['linkedin']; ?>" class="btn btn-sm btn-outline-primary mx-1" target="_blank">
                                                    <i class="fab fa-linkedin-in"></i>
                                                </a>
                                            <?php endif; ?>
                                            <?php if (!empty($candidato['cv_path'])): ?>
                                                <a href="<?php echo '../../uploads/resumes/' . $candidato['cv_path']; ?>" class="btn btn-sm btn-outline-info mx-1" target="_blank">
                                                    <i class="fas fa-file-alt"></i> Ver CV
                                                </a>
                                            <?php endif; ?>
                                            <?php if (!empty($candidato['portfolio'])): ?>
                                                <a href="<?php echo $candidato['portfolio']; ?>" class="btn btn-sm btn-outline-secondary mx-1" target="_blank">
                                                    <i class="fas fa-briefcase"></i> Portfolio
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="card-footer bg-transparent">
                                        <div class="d-flex justify-content-between text-center mt-2">
                                            <div>
                                                <p class="mb-0 text-muted">Experiencia</p>
                                                <h6>
                                                    <?php 
                                                    switch ($candidato['experiencia_general']) {
                                                        case 'sin-experiencia': echo 'Sin exp.'; break;
                                                        case 'menos-1': echo '< 1 año'; break;
                                                        case '1-3': echo '1-3 años'; break;
                                                        case '3-5': echo '3-5 años'; break;
                                                        case '5-10': echo '5-10 años'; break;
                                                        case 'mas-10': echo '> 10 años'; break;
                                                        default: echo 'N/A';
                                                    }
                                                    ?>
                                                </h6>
                                            </div>
                                            <div class="border-start border-end px-3">
                                                <p class="mb-0 text-muted">Disponibilidad</p>
                                                <h6>
                                                    <?php 
                                                    switch ($candidato['disponibilidad']) {
                                                        case 'inmediata': echo 'Inmediata'; break;
                                                        case '2-semanas': echo '2 semanas'; break;
                                                        case '1-mes': echo '1 mes'; break;
                                                        case 'mas-1-mes': echo '> 1 mes'; break;
                                                        default: echo 'N/A';
                                                    }
                                                    ?>
                                                </h6>
                                            </div>
                                            <div>
                                                <p class="mb-0 text-muted">Modalidad</p>
                                                <h6>
                                                    <?php 
                                                    switch ($candidato['modalidad_preferida']) {
                                                        case 'presencial': echo 'Presencial'; break;
                                                        case 'remoto': echo 'Remoto'; break;
                                                        case 'hibrido': echo 'Híbrido'; break;
                                                        default: echo 'N/A';
                                                    }
                                                    ?>
                                                </h6>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!-- Evaluación General -->
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">Evaluación General</h5>
                                    </div>
                                    <div class="card-body text-center">
                                        <div class="gauge-container">
                                            <canvas id="generalScoreGauge" width="200" height="200"></canvas>
                                            <div id="gaugeValue" class="gauge-value"><?php echo $promedioResultados; ?>%</div>
                                        </div>
                                        <div class="mt-3">
                                            <h4 id="evaluationLevel" class="mb-1"><?php echo $nivelEvaluacion['texto']; ?></h4>
                                            <p id="evaluationDesc" class="text-muted mb-0"><?php echo $nivelEvaluacion['descripcion']; ?></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Columna Central - Principales resultados -->
                            <div class="col-md-8">
                                <!-- Fortalezas y áreas de mejora -->
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">Principales Hallazgos</h5>
                                    </div>
                                    <div class="card-body">
                                        <div id="strengthsWeaknesses" class="row">
                                            <div class="col-md-6">
                                                <h6 class="text-success"><i class="fas fa-arrow-up me-1"></i> Fortalezas</h6>
                                                <ul class="strength-list" id="strengthsList">
                                                    <?php foreach ($fortalezas as $fortaleza): ?>
                                                    <li><?php echo $fortaleza; ?></li>
                                                    <?php endforeach; ?>
                                                    
                                                    <?php if (empty($fortalezas)): ?>
                                                    <li>No hay suficientes datos para determinar fortalezas</li>
                                                    <?php endif; ?>
                                                </ul>
                                            </div>
                                            <div class="col-md-6">
                                                <h6 class="text-warning"><i class="fas fa-arrow-down me-1"></i> Áreas de Mejora</h6>
                                                <ul class="weakness-list" id="weaknessesList">
                                                    <?php foreach ($debilidades as $debilidad): ?>
                                                    <li><?php echo $debilidad; ?></li>
                                                    <?php endforeach; ?>
                                                    
                                                    <?php if (empty($debilidades)): ?>
                                                    <li>No hay suficientes datos para determinar áreas de mejora</li>
                                                    <?php endif; ?>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Gráfico de Radar - Perfil General -->
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">Perfil Completo</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="radar-chart-container">
                                            <canvas id="profileRadarChart" height="300"></canvas>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Perfil Motivacional -->
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">Perfil Motivacional</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="horizontal-bar-container">
                                            <canvas id="motivationBarChart" height="250"></canvas>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Pestaña de Aptitudes Cognitivas -->
                    <div class="tab-pane fade" id="cognitive" role="tabpanel">
                        <div class="row">
                            <div class="col-md-8">
                                <!-- Gráfica de Aptitudes -->
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">Perfil de Aptitudes Cognitivas</h5>
                                    </div>
                                    <div class="card-body">
                                        <canvas id="cognitiveBarChart" height="300"></canvas>
                                    </div>
                                </div>
                                
                                <!-- Detalles de cada aptitud -->
                                <div id="cognitiveDetailsCards" class="row">
                                    <?php
                                    // Mostrar aptitudes cognitivas si existen
                                    if (isset($dimensiones['cognitiva'])) {
                                        foreach ($dimensiones['cognitiva'] as $aptitud) {
                                            $nivel = getNivelEvaluacion($aptitud['promedio']);
                                    ?>
                                    <div class="col-md-6 mb-4">
                                        <div class="card border-left-<?php echo $nivel['class']; ?> h-100">
                                            <div class="card-body">
                                                <div class="row no-gutters align-items-center">
                                                    <div class="col mr-2">
                                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $aptitud['nombre']; ?></div>
                                                        <div class="row mt-3">
                                                            <div class="col-7">
                                                                <div class="progress mb-1" style="height: 12px;">
                                                                    <div class="progress-bar" role="progressbar" 
                                                                         style="width: <?php echo $aptitud['promedio']; ?>%; background-color: <?php echo $nivel['color']; ?>;" 
                                                                         aria-valuenow="<?php echo $aptitud['promedio']; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                                                </div>
                                                                <div class="text-xs font-weight-bold text-<?php echo $nivel['class']; ?> text-uppercase mt-1">
                                                                    <?php echo $nivel['texto']; ?> (<?php echo round($aptitud['promedio']); ?>%)
                                                                </div>
                                                            </div>
                                                            <div class="col-5 text-right">
                                                                <div class="percentile-display">
                                                                    <span class="small text-gray-600">Percentil</span>
                                                                    <div class="h4 mb-0 font-weight-bold text-gray-800">
                                                                        <?php 
                                                                        // Calcular el percentil basado en el valor promedio
                                                                        $percentil = round($aptitud['promedio'] * 0.95);
                                                                        echo $percentil; 
                                                                        ?>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <?php 
                                        }
                                    } else {
                                    ?>
                                    <div class="col-12">
                                        <div class="alert alert-info">
                                            No hay datos de aptitudes cognitivas disponibles para este candidato.
                                        </div>
                                    </div>
                                    <?php } ?>
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <!-- Indicador de percentil general -->
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">Percentil General</h5>
                                    </div>
                                    <div class="card-body text-center">
                                        <div class="progress-radial-container">
                                            <?php 
                                            // Calcular el percentil cognitivo basado en los resultados reales
                                            $cognitivePercentile = 0;
                                            if (isset($dimensiones['cognitiva']) && !empty($dimensiones['cognitiva'])) {
                                                $totalCognitivo = 0;
                                                foreach ($dimensiones['cognitiva'] as $aptitud) {
                                                    $totalCognitivo += $aptitud['promedio'];
                                                }
                                                $promedioCognitivo = $totalCognitivo / count($dimensiones['cognitiva']);
                                                $cognitivePercentile = round($promedioCognitivo * 0.95); // Ajustar para percentil
                                            }
                                            ?>
                                            <div id="cognitivePercentile" class="progress-radial" style="--progress: <?php echo $cognitivePercentile; ?>">
                                                <div class="percentile-value"><?php echo $cognitivePercentile; ?>%</div>
                                            </div>
                                        </div>
                                        <p class="mt-3">Mejor que el <span id="cognitivePercentileText"><?php echo $cognitivePercentile; ?>%</span> de la población evaluada</p>
                                    </div>
                                </div>
                                
                                <!-- Interpretación cognitiva -->
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">Interpretación</h5>
                                    </div>
                                    <div class="card-body">
                                        <div id="cognitiveInterpretation">
                                            <?php if (isset($dimensiones['cognitiva'])): ?>
                                            <p>El candidato muestra una aptitud cognitiva general <strong><?php echo strtolower($nivelEvaluacion['texto']); ?></strong>, 
                                            <?php
                                            $aptitudesOrdenadas = $dimensiones['cognitiva'];
                                            usort($aptitudesOrdenadas, function($a, $b) {
                                                return $b['promedio'] - $a['promedio'];
                                            });
                                            
                                            if (count($aptitudesOrdenadas) >= 2) {
                                                echo 'destacando especialmente en ' . strtolower($aptitudesOrdenadas[0]['nombre']) . ' y ' . strtolower($aptitudesOrdenadas[1]['nombre']) . '.';
                                            } elseif (count($aptitudesOrdenadas) == 1) {
                                                echo 'destacando especialmente en ' . strtolower($aptitudesOrdenadas[0]['nombre']) . '.';
                                            } else {
                                                echo 'con un desempeño equilibrado en las diferentes áreas evaluadas.';
                                            }
                                            ?>
                                            </p>
                                            
                                            <p>Su capacidad para análisis y resolución de problemas 
                                            <?php
                                            if ($promedioResultados >= 80) echo 'es superior al promedio, lo que le permite abordar situaciones complejas con eficacia.';
                                            elseif ($promedioResultados >= 65) echo 'se encuentra por encima del promedio, permitiéndole manejar adecuadamente problemas estructurados.';
                                            else echo 'se encuentra en un nivel aceptable, pudiendo beneficiarse de apoyo en situaciones más complejas.';
                                            ?>
                                            </p>
                                            <?php else: ?>
                                            <p>No hay suficientes datos de aptitudes cognitivas para realizar una interpretación completa.</p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Recomendaciones -->
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">Recomendaciones</h5>
                                    </div>
                                    <div class="card-body">
                                        <div id="cognitiveRecommendations">
                                            <?php if (isset($dimensiones['cognitiva'])): ?>
                                            <ul class="recommendation-list">
                                                <?php if ($promedioResultados >= 75): ?>
                                                <li>Ideal para roles que requieran análisis de información compleja</li>
                                                <li>Considerar asignar proyectos que aprovechen su capacidad analítica</li>
                                                <li>Ofrecer oportunidades para liderar resolución de problemas estratégicos</li>
                                                <?php elseif ($promedioResultados >= 60): ?>
                                                <li>Adecuado para roles que requieran análisis estructurado</li>
                                                <li>Puede beneficiarse de formación adicional en metodologías analíticas</li>
                                                <li>Asignar tareas de complejidad progresiva para desarrollar sus capacidades</li>
                                                <?php else: ?>
                                                <li>Proporcionar estructura clara para tareas analíticas</li>
                                                <li>Ofrecer formación específica en herramientas de análisis</li>
                                                <li>Asignar tareas de complejidad moderada con supervisión adecuada</li>
                                                <?php endif; ?>
                                            </ul>
                                            <?php else: ?>
                                            <p>Se recomienda realizar pruebas de aptitudes cognitivas para obtener recomendaciones específicas.</p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Pestaña de Personalidad -->
                    <div class="tab-pane fade" id="personality" role="tabpanel">
                        <div class="row">
                            <div class="col-md-7">
                                <!-- Gráfico Polar de Personalidad -->
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">Dimensiones de Personalidad</h5>
                                    </div>
                                    <div class="card-body">
                                        <canvas id="personalityPolarChart" height="350"></canvas>
                                    </div>
                                </div>
                                
                                <!-- Detalles de cada dimensión -->
                                <div id="personalityDetailsAccordion" class="accordion">
                                    <?php
                                    // Mostrar dimensiones de personalidad si existen
                                    if (isset($dimensiones['personalidad'])) {
                                        foreach ($dimensiones['personalidad'] as $index => $dim) {
                                            $nivel = getNivelEvaluacion($dim['promedio']);
                                    ?>
                                    <div class="card mb-2">
                                        <div class="card-header d-flex justify-content-between align-items-center" id="heading<?php echo $index; ?>">
                                            <h6 class="mb-0">
                                                <button class="btn btn-link btn-block text-left collapsed" type="button" data-bs-toggle="collapse" 
                                                        data-bs-target="#collapse<?php echo $index; ?>" aria-expanded="false" aria-controls="collapse<?php echo $index; ?>">
                                                    <?php echo $dim['nombre']; ?>
                                                </button>
                                            </h6>
                                            <span class="badge bg-<?php echo $nivel['class']; ?> text-white">
                                                <?php echo round($dim['promedio']); ?>%
                                            </span>
                                        </div>
                                        <div id="collapse<?php echo $index; ?>" class="collapse" aria-labelledby="heading<?php echo $index; ?>" data-bs-parent="#personalityDetailsAccordion">
                                            <div class="card-body">
                                                <?php
                                                // Obtener descripción e implicaciones de la base de datos
                                                $dimId = $dim['id'];
                                                $dimPromedio = $dim['promedio'];
                                                
                                                // Query para obtener interpretación apropiada para esta dimensión y valor
                                                $interpretacionQuery = "SELECT * FROM interpretaciones 
                                                    WHERE dimension_id = $dimId 
                                                    AND $dimPromedio BETWEEN rango_min AND rango_max
                                                    LIMIT 1";
                                                
                                                try {
                                                    $interpretacion = null;
                                                    $resultInterp = $db->query($interpretacionQuery);
                                                    if ($resultInterp && $resultInterp->num_rows > 0) {
                                                        $interpretacion = $resultInterp->fetch_assoc();
                                                    }
                                                } catch (Exception $e) {
                                                    // Ignorar error
                                                }
                                                ?>
                                                
                                                <p class="personality-description">
                                                    <?php
                                                    if ($interpretacion && !empty($interpretacion['descripcion'])) {
                                                        echo htmlspecialchars($interpretacion['descripcion']);
                                                    } else {
                                                        // Descripción genérica basada en nivel
                                                        if ($dim['promedio'] >= 75) {
                                                            echo 'Muestra un nivel elevado en esta dimensión, lo que indica una presencia significativa de estos rasgos en su perfil de personalidad.';
                                                        } elseif ($dim['promedio'] >= 50) {
                                                            echo 'Presenta un nivel moderado en esta dimensión, indicando un equilibrio en la expresión de estos rasgos.';
                                                        } else {
                                                            echo 'Muestra un nivel bajo en esta dimensión, sugiriendo una menor presencia de estos rasgos en su comportamiento habitual.';
                                                        }
                                                    }
                                                    ?>
                                                </p>
                                                
                                                <div class="mb-2">
                                                    <small class="text-muted">Implicaciones laborales:</small>
                                                    <p class="mb-0 mt-1">
                                                        <?php
                                                        if ($interpretacion && !empty($interpretacion['implicacion_laboral'])) {
                                                            echo htmlspecialchars($interpretacion['implicacion_laboral']);
                                                        } else {
                                                            // Implicación genérica basada en nivel
                                                            if ($dim['promedio'] >= 75) {
                                                                echo 'Se desempeñará mejor en entornos que valoren y aprovechen esta característica. Considerar cómo esta fortaleza puede contribuir positivamente a los equipos y proyectos.';
                                                            } elseif ($dim['promedio'] >= 50) {
                                                                echo 'Muestra versatilidad en esta dimensión, pudiendo adaptarse a diferentes contextos según las necesidades. Ofrece un equilibrio valioso en equipos diversos.';
                                                            } else {
                                                                echo 'Puede complementar efectivamente a perfiles con alta puntuación en esta dimensión. Considerar cómo esta característica afecta sus preferencias laborales y estilo de trabajo.';
                                                            }
                                                        }
                                                        ?>
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <?php 
                                        }
                                    } else {
                                    ?>
                                    <div class="alert alert-info">
                                        No hay datos de personalidad disponibles para este candidato.
                                    </div>
                                    <?php } ?>
                                </div>
                            </div>
                            
                            <div class="col-md-5">
                                <!-- Perfil de Personalidad -->
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">Perfil de Personalidad</h5>
                                    </div>
                                    <div class="card-body">
                                        <div id="personalityProfile">
                                            <?php if (isset($dimensiones['personalidad'])): ?>
                                            <p class="mb-3">
                                                El candidato muestra un perfil de personalidad caracterizado por 
                                                <?php
                                                $personDims = $dimensiones['personalidad'];
                                                usort($personDims, function($a, $b) {
                                                    return $b['promedio'] - $a['promedio'];
                                                });
                                                
                                                if (count($personDims) >= 2) {
                                                    echo 'una alta ' . strtolower($personDims[0]['nombre']) . ', buena ' . strtolower($personDims[1]['nombre']);
                                                    if (count($personDims) >= 3) {
                                                        echo ' y nivel moderado de ' . strtolower($personDims[2]['nombre']) . '.';
                                                    } else {
                                                        echo '.';
                                                    }
                                                } elseif (count($personDims) == 1) {
                                                    echo 'una ' . ($personDims[0]['promedio'] >= 75 ? 'alta ' : ($personDims[0]['promedio'] >= 50 ? 'moderada ' : 'baja '));
                                                    echo strtolower($personDims[0]['nombre']) . '.';
                                                } else {
                                                    echo 'características equilibradas sin rasgos predominantes.';
                                                }
                                                ?>
                                            </p>
                                            <?php endif; ?>
                                            
                                            <!-- Coincidencia con perfil -->
                                            <div class="profile-match mb-3">
                                                <h6>Coincidencia con perfil:</h6>
                                                <div class="d-flex align-items-center">
                                                    <div class="profile-icon me-2 text-primary">
                                                        <i class="fas fa-user-tie fa-2x"></i>
                                                    </div>
                                                    <div class="profile-info flex-grow-1">
                                                        <?php
                                                        // Determinar el perfil coincidente basado en los resultados
                                                        $matchingProfile = "No determinado";
                                                        $matchPercentage = 0;
                                                        
                                                        if (isset($dimensiones['personalidad']) && !empty($dimensiones['personalidad'])) {
                                                            // Aquí podríamos implementar un algoritmo más sofisticado
                                                            // usando los datos reales de las dimensiones de personalidad
                                                            
                                                            // Ejemplo básico:
                                                            // Verificar si tiene alta extroversión y apertura
                                                            $hasHighExtroversion = false;
                                                            $hasHighOpenness = false;
                                                            
                                                            foreach ($dimensiones['personalidad'] as $dim) {
                                                                if (strtolower($dim['nombre']) === 'extroversión' && $dim['promedio'] >= 70) {
                                                                    $hasHighExtroversion = true;
                                                                }
                                                                if (strtolower($dim['nombre']) === 'apertura' && $dim['promedio'] >= 70) {
                                                                    $hasHighOpenness = true;
                                                                }
                                                            }
                                                            
                                                            if ($hasHighExtroversion && $hasHighOpenness) {
                                                                $matchingProfile = "Emprendedor";
                                                                $matchPercentage = 85;
                                                            } elseif ($hasHighExtroversion) {
                                                                $matchingProfile = "Comunicador";
                                                                $matchPercentage = 75;
                                                            } elseif ($hasHighOpenness) {
                                                                $matchingProfile = "Innovador";
                                                                $matchPercentage = 70;
                                                            } else {
                                                                // Basado en el promedio general
                                                                if ($promedioResultados >= 75) {
                                                                    $matchingProfile = "Especialista";
                                                                    $matchPercentage = 80;
                                                                } elseif ($promedioResultados >= 60) {
                                                                    $matchingProfile = "Analista";
                                                                    $matchPercentage = 65;
                                                                } else {
                                                                    $matchingProfile = "Colaborador";
                                                                    $matchPercentage = 60;
                                                                }
                                                            }
                                                        }
                                                        ?>
                                                        <h5 id="matchingProfileName"><?php echo $matchingProfile; ?></h5>
                                                        <div class="progress" style="height: 8px;">
                                                            <div id="matchingProfilePercentage" class="progress-bar bg-primary" role="progressbar" 
                                                                 style="width: <?php echo $matchPercentage; ?>%;" aria-valuenow="<?php echo $matchPercentage; ?>" 
                                                                 aria-valuemin="0" aria-valuemax="100"></div>
                                                        </div>
                                                        <div class="d-flex justify-content-between small mt-1">
                                                            <span>Baja</span>
                                                            <span>Alta</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Dimensiones Bipolares -->
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">Dimensiones Bipolares</h5>
                                    </div>
                                    <div class="card-body">
                                        <div id="bipolarDimensions">
                                            <?php
                                            // Obtener dimensiones bipolares de la base de datos
                                            $dimensionesBipolares = [];
                                            
                                            try {
                                                $bipolarQuery = "SELECT db.id, db.dimension_positiva, db.dimension_negativa, 
                                                                 AVG(r.valor) as promedio
                                                                 FROM dimensiones_bipolares db
                                                                 JOIN resultados r ON r.dimension_id = db.dimension_id
                                                                 JOIN sesiones_prueba s ON r.sesion_id = s.id
                                                                 WHERE s.candidato_id = $candidato_id AND s.estado = 'completada'
                                                                 GROUP BY db.id
                                                                 ORDER BY db.id";
                                                
                                                $resultBip = $db->query($bipolarQuery);
                                                if ($resultBip && $resultBip->num_rows > 0) {
                                                    while ($row = $resultBip->fetch_assoc()) {
                                                        $dimensionesBipolares[] = [
                                                            'negative' => $row['dimension_negativa'],
                                                            'positive' => $row['dimension_positiva'],
                                                            'value' => round($row['promedio'])
                                                        ];
                                                    }
                                                }
                                            } catch (Exception $e) {
                                                // Si hay error o la tabla no existe, usar datos de ejemplo
                                                $dimensionesBipolares = [
                                                    ['negative' => 'Introversión', 'positive' => 'Extroversión', 'value' => 65],
                                                    ['negative' => 'Reactividad Emocional', 'positive' => 'Estabilidad Emocional', 'value' => 75],
                                                    ['negative' => 'Convencionalidad', 'positive' => 'Apertura', 'value' => 80],
                                                    ['negative' => 'Independencia', 'positive' => 'Cooperación', 'value' => 60],
                                                    ['negative' => 'Flexibilidad', 'positive' => 'Meticulosidad', 'value' => 70]
                                                ];
                                            }
                                            
                                            if (empty($dimensionesBipolares) && isset($dimensiones['personalidad'])) {
                                                // Generar dimensiones bipolares basadas en las dimensiones de personalidad disponibles
                                                foreach ($dimensiones['personalidad'] as $dim) {
                                                    $nombre = strtolower($dim['nombre']);
                                                    $valor = $dim['promedio'];
                                                    
                                                    if ($nombre === 'extroversión') {
                                                        $dimensionesBipolares[] = ['negative' => 'Introversión', 'positive' => 'Extroversión', 'value' => $valor];
                                                    } elseif ($nombre === 'estabilidad emocional' || $nombre === 'neuroticismo') {
                                                        $dimensionesBipolares[] = ['negative' => 'Reactividad Emocional', 'positive' => 'Estabilidad Emocional', 'value' => $valor];
                                                    } elseif ($nombre === 'apertura' || $nombre === 'apertura a la experiencia') {
                                                        $dimensionesBipolares[] = ['negative' => 'Convencionalidad', 'positive' => 'Apertura', 'value' => $valor];
                                                    } elseif ($nombre === 'amabilidad' || $nombre === 'cordialidad') {
                                                        $dimensionesBipolares[] = ['negative' => 'Independencia', 'positive' => 'Cooperación', 'value' => $valor];
                                                    } elseif ($nombre === 'responsabilidad' || $nombre === 'meticulosidad') {
                                                        $dimensionesBipolares[] = ['negative' => 'Flexibilidad', 'positive' => 'Meticulosidad', 'value' => $valor];
                                                    }
                                                }
                                            }
                                            
                                            // Si no hay datos, usar valores predeterminados para demostración
                                            if (empty($dimensionesBipolares)) {
                                                $dimensionesBipolares = [
                                                    ['negative' => 'Introversión', 'positive' => 'Extroversión', 'value' => 65],
                                                    ['negative' => 'Reactividad Emocional', 'positive' => 'Estabilidad Emocional', 'value' => 75],
                                                    ['negative' => 'Convencionalidad', 'positive' => 'Apertura', 'value' => 80],
                                                    ['negative' => 'Independencia', 'positive' => 'Cooperación', 'value' => 60],
                                                    ['negative' => 'Flexibilidad', 'positive' => 'Meticulosidad', 'value' => 70]
                                                ];
                                            }
                                            
                                            foreach ($dimensionesBipolares as $dimension) {
                                            ?>
                                            <div class="bipolar-dimension mb-3">
                                                <div class="d-flex justify-content-between mb-1">
                                                    <span class="text-muted"><?php echo $dimension['negative']; ?></span>
                                                    <span class="text-muted"><?php echo $dimension['positive']; ?></span>
                                                </div>
                                                <div class="progress" style="height: 12px;">
                                                    <div class="progress-bar" role="progressbar" style="width: <?php echo $dimension['value']; ?>%;" 
                                                         aria-valuenow="<?php echo $dimension['value']; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                                </div>
                                            </div>
                                            <?php } ?>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Interpretación de Personalidad -->
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">Interpretación</h5>
                                    </div>
                                    <div class="card-body">
                                        <div id="personalityInterpretation">
                                            <?php if (isset($dimensiones['personalidad'])): ?>
                                            <p>El candidato presenta un perfil que combina 
                                            <?php 
                                            $rasgosAltos = array_filter($dimensiones['personalidad'], function($d) {
                                                return $d['promedio'] >= 75;
                                            });
                                            
                                            if (!empty($rasgosAltos)) {
                                                $nombres = array_map(function($d) { return strtolower($d['nombre']); }, $rasgosAltos);
                                                echo implode(' y ', $nombres);
                                            } else {
                                                echo 'rasgos equilibrados';
                                            }
                                            ?>
                                            con 
                                            <?php
                                            $dimensionEstabilidad = array_filter($dimensiones['personalidad'], function($d) {
                                                return strtolower($d['nombre']) == 'estabilidad emocional';
                                            });
                                            
                                            if (!empty($dimensionEstabilidad)) {
                                                $estabilidad = reset($dimensionEstabilidad);
                                                echo $estabilidad['promedio'] >= 75 ? 'buena estabilidad emocional' : ($estabilidad['promedio'] >= 60 ? 'moderada estabilidad emocional' : 'cierta reactividad emocional');
                                            } else {
                                                echo 'un manejo adecuado del estrés';
                                            }
                                            ?>, 
                                            <?php
                                            $dimensionExtroversion = array_filter($dimensiones['personalidad'], function($d) {
                                                return strtolower($d['nombre']) == 'extroversión';
                                            });
                                            
                                            if (!empty($dimensionExtroversion)) {
                                                $extroversion = reset($dimensionExtroversion);
                                                if ($extroversion['promedio'] >= 75) {
                                                    echo 'lo que favorece su adaptación a entornos sociales dinámicos.';
                                                } elseif ($extroversion['promedio'] >= 50) {
                                                    echo 'lo que le permite adaptarse tanto a trabajo individual como en equipo.';
                                                } else {
                                                    echo 'prefiriendo entornos con interacciones más limitadas y estructuradas.';
                                                }
                                            } else {
                                                echo 'mostrando una adaptabilidad adecuada a diferentes contextos.';
                                            }
                                            ?>
                                            </p>
                                            
                                            <p>Su nivel 
                                            <?php
                                            $dimensionMeticulosidad = array_filter($dimensiones['personalidad'], function($d) {
                                                return strtolower($d['nombre']) == 'meticulosidad';
                                            });
                                            
                                            if (!empty($dimensionMeticulosidad)) {
                                                $meticulosidad = reset($dimensionMeticulosidad);
                                                echo $meticulosidad['promedio'] >= 75 ? 'alto' : ($meticulosidad['promedio'] >= 50 ? 'moderado' : 'bajo');
                                            } else {
                                                echo 'moderado';
                                            }
                                            ?>
                                            de organización y cumplimiento de objetivos
                                            <?php
                                            $dimensionApertura = array_filter($dimensiones['personalidad'], function($d) {
                                                return strtolower($d['nombre']) == 'apertura a experiencias' || strtolower($d['nombre']) == 'apertura';
                                            });
                                            
                                            if (!empty($dimensionApertura)) {
                                                $apertura = reset($dimensionApertura);
                                                if ($apertura['promedio'] >= 75) {
                                                    echo ', combinado con su alta apertura a nuevas ideas, indica un perfil equilibrado entre innovación y ejecución.';
                                                } elseif ($apertura['promedio'] >= 50) {
                                                    echo ' se complementa con una moderada apertura a nuevas experiencias, logrando un balance entre estabilidad e innovación.';
                                                } else {
                                                    echo ' sugiere una preferencia por métodos establecidos y enfoques convencionales.';
                                                }
                                            } else {
                                                echo ' representa un aspecto importante de su estilo de trabajo.';
                                            }
                                            ?>
                                            </p>
                                            <?php else: ?>
                                            <p>No hay suficientes datos de personalidad disponibles para realizar una interpretación completa.</p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Pestaña de Motivaciones -->
                    <div class="tab-pane fade" id="motivations" role="tabpanel">
                        <div class="row">
                            <div class="col-md-8">
                                <!-- Gráfico de Motivaciones -->
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">Perfil Motivacional</h5>
                                    </div>
                                    <div class="card-body">
                                        <canvas id="motivationalRadarChart" height="350"></canvas>
                                    </div>
                                </div>
                                
                                <!-- Núcleo Motivacional -->
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">Núcleo Motivacional</h5>
                                    </div>
                                    <div class="card-body">
                                        <?php
                                        // Obtener motivaciones desde la base de datos
                                        $motivaciones = isset($dimensiones['motivacion']) ? $dimensiones['motivacion'] : [];
                                        
                                        // Si no hay datos reales, usar datos de ejemplo para visualización
                                        if (empty($motivaciones)) {
                                            try {
                                                $motivacionesQuery = "SELECT d.id, d.nombre, AVG(r.valor) as promedio
                                                                    FROM resultados r
                                                                    JOIN dimensiones d ON r.dimension_id = d.id
                                                                    JOIN sesiones_prueba s ON r.sesion_id = s.id
                                                                    WHERE s.candidato_id = $candidato_id 
                                                                    AND s.estado = 'completada'
                                                                    AND d.categoria = 'motivacion'
                                                                    GROUP BY d.id
                                                                    ORDER BY promedio DESC";
                                                
                                                $resultMotiv = $db->query($motivacionesQuery);
                                                if ($resultMotiv && $resultMotiv->num_rows > 0) {
                                                    while ($row = $resultMotiv->fetch_assoc()) {
                                                        $motivaciones[] = $row;
                                                    }
                                                }
                                            } catch (Exception $e) {
                                                // Ignorar error
                                            }
                                        }
                                        
                                        // Si todavía no hay datos, usar ejemplos
                                        if (empty($motivaciones)) {
                                            $motivaciones = [
                                                ['nombre' => 'Logro', 'promedio' => 85],
                                                ['nombre' => 'Autonomía', 'promedio' => 78],
                                                ['nombre' => 'Reto', 'promedio' => 72],
                                                ['nombre' => 'Afiliación', 'promedio' => 62],
                                                ['nombre' => 'Poder', 'promedio' => 48],
                                                ['nombre' => 'Seguridad', 'promedio' => 45],
                                            ];
                                        }
                                        
                                        // Ordenar por valor de mayor a menor
                                        usort($motivaciones, function($a, $b) {
                                            return $b['promedio'] - $a['promedio'];
                                        });
                                        
                                        // Obtener las 3 principales
                                        $topMotivaciones = array_slice($motivaciones, 0, 3);
                                        ?>
                                        
                                        <div class="row">
                                            <div class="col-lg-8">
                                                <canvas id="motivationPieChart" height="250"></canvas>
                                            </div>
                                            <div class="col-lg-4">
                                                <div class="core-motivations">
                                                    <?php foreach ($topMotivaciones as $index => $motiv): ?>
                                                    <div class="core-motivation mb-3">
                                                        <h6 class="motivation-title">
                                                            <span class="badge bg-<?php echo $index == 0 ? 'primary' : ($index == 1 ? 'success' : 'info'); ?> me-2"><?php echo $index + 1; ?></span> 
                                                            <?php echo $motiv['nombre']; ?>
                                                        </h6>
                                                        <div class="progress" style="height: 10px;">
                                                            <div class="progress-bar bg-<?php echo $index == 0 ? 'primary' : ($index == 1 ? 'success' : 'info'); ?>" 
                                                                 role="progressbar" style="width: <?php echo $motiv['promedio']; ?>%;" 
                                                                 aria-valuenow="<?php echo $motiv['promedio']; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                                        </div>
                                                    </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <!-- Perfil motivacional coincidente -->
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">Perfil Coincidente</h5>
                                    </div>
                                    <div class="card-body">
                                        <?php
                                        // Determinar perfil coincidente basado en las principales motivaciones
                                        $perfilName = "No determinado";
                                        $matchPercentage = 0;
                                        $perfilDesc = "";
                                        
                                        if (!empty($topMotivaciones)) {
                                            $principalMotivacion = strtolower($topMotivaciones[0]['nombre']);
                                            
                                            if ($principalMotivacion === 'logro') {
                                                $perfilName = "Orientado a Resultados";
                                                $matchPercentage = 85;
                                                $perfilDesc = "Perfil caracterizado por un fuerte impulso hacia la consecución de objetivos y metas. Disfruta superando desafíos y obteniendo resultados tangibles.";
                                            } elseif ($principalMotivacion === 'autonomía') {
                                                $perfilName = "Independiente";
                                                $matchPercentage = 82;
                                                $perfilDesc = "Valora altamente la independencia en su trabajo y la capacidad para tomar decisiones. Prefiere entornos con libertad para decidir cómo realizar sus tareas.";
                                            } elseif ($principalMotivacion === 'reto') {
                                                $perfilName = "Buscador de Desafíos";
                                                $matchPercentage = 80;
                                                $perfilDesc = "Motivado por enfrentar situaciones complejas y desafiantes. Disfruta resolviendo problemas difíciles y aprendiendo constantemente.";
                                            } elseif ($principalMotivacion === 'afiliación') {
                                                $perfilName = "Colaborativo";
                                                $matchPercentage = 78;
                                                $perfilDesc = "Valora las relaciones interpersonales y el trabajo en equipo. Se siente motivado en ambientes con buen clima laboral y cooperación.";
                                            } elseif ($principalMotivacion === 'poder') {
                                                $perfilName = "Líder";
                                                $matchPercentage = 80;
                                                $perfilDesc = "Busca influir en otros y tener impacto. Disfruta dirigiendo equipos y tomando decisiones importantes.";
                                            } elseif ($principalMotivacion === 'seguridad') {
                                                $perfilName = "Estable";
                                                $matchPercentage = 75;
                                                $perfilDesc = "Valora la estabilidad y previsibilidad en su entorno laboral. Prefiere ambientes con clara estructura y bajo riesgo.";
                                            } else {
                                                $perfilName = "Emprendedor";
                                                $matchPercentage = 70;
                                                $perfilDesc = "Perfil equilibrado con capacidad para adaptarse a diferentes contextos. Muestra interés por conseguir resultados mientras desarrolla sus capacidades.";
                                            }
                                        } else {
                                            $perfilDesc = "No hay suficientes datos para determinar un perfil motivacional específico.";
                                        }
                                        ?>
                                        
                                        <div class="text-center mb-3">
                                            <div class="profile-icon bg-light rounded-circle p-3 d-inline-block mb-2">
                                                <i class="fas fa-user-tie fa-3x text-primary"></i>
                                            </div>
                                            <h4 id="motivationalProfileName"><?php echo $perfilName; ?></h4>
                                            <?php if ($matchPercentage > 0): ?>
                                            <div class="match-percentage">
                                                <span class="badge bg-success p-2">Coincidencia <?php echo $matchPercentage; ?>%</span>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                        <p id="motivationalProfileDescription">
                                            <?php echo $perfilDesc; ?>
                                        </p>
                                    </div>
                                </div>
                                
                                <!-- Interpretación Motivacional -->
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">Interpretación</h5>
                                    </div>
                                    <div class="card-body">
                                        <div id="motivationalInterpretation">
                                            <?php if (!empty($motivaciones)): ?>
                                            <p>
                                                El candidato muestra un claro patrón motivacional orientado al <strong><?php echo strtolower($topMotivaciones[0]['nombre']); ?></strong>
                                                <?php if (isset($topMotivaciones[1])): ?>
                                                    , <strong><?php echo strtolower($topMotivaciones[1]['nombre']); ?></strong>
                                                <?php endif; ?>
                                                <?php if (isset($topMotivaciones[2])): ?>
                                                    y <strong><?php echo strtolower($topMotivaciones[2]['nombre']); ?></strong>
                                                <?php endif; ?>
                                                , lo que indica una preferencia por entornos laborales donde pueda 
                                                <?php
                                                $motivacionesTexto = [];
                                                foreach ($topMotivaciones as $motiv) {
                                                    switch (strtolower($motiv['nombre'])) {
                                                        case 'logro':
                                                            $motivacionesTexto[] = 'establecer y alcanzar metas desafiantes';
                                                            break;
                                                        case 'autonomía':
                                                            $motivacionesTexto[] = 'trabajar con cierto grado de independencia';
                                                            break;
                                                        case 'reto':
                                                            $motivacionesTexto[] = 'enfrentar situaciones estimulantes';
                                                            break;
                                                        case 'afiliación':
                                                            $motivacionesTexto[] = 'desarrollar relaciones interpersonales positivas';
                                                            break;
                                                        case 'poder':
                                                            $motivacionesTexto[] = 'ejercer influencia y liderazgo';
                                                            break;
                                                        case 'seguridad':
                                                            $motivacionesTexto[] = 'contar con estabilidad y previsibilidad';
                                                            break;
                                                        default:
                                                            $motivacionesTexto[] = 'desarrollar sus intereses principales';
                                                    }
                                                }
                                                echo implode(' y ', $motivacionesTexto);
                                                ?>.
                                            </p>
                                            
                                            <p>
                                                <?php
                                                // Encontrar las motivaciones más bajas
                                                $bottomMotivaciones = array_slice($motivaciones, -2);
                                                if (!empty($bottomMotivaciones)) {
                                                    echo 'El bajo interés en ';
                                                    $bottomTexto = [];
                                                    foreach ($bottomMotivaciones as $motiv) {
                                                        $bottomTexto[] = strtolower($motiv['nombre']);
                                                    }
                                                    echo implode(' y ', $bottomTexto);
                                                    
                                                    echo ' sugiere que no prioriza ';
                                                    $prioridadesTexto = [];
                                                    foreach ($bottomMotivaciones as $motiv) {
                                                        switch (strtolower($motiv['nombre'])) {
                                                            case 'seguridad':
                                                                $prioridadesTexto[] = 'la estabilidad a largo plazo';
                                                                break;
                                                            case 'poder':
                                                                $prioridadesTexto[] = 'el control sobre otros';
                                                                break;
                                                            case 'afiliación':
                                                                $prioridadesTexto[] = 'la integración social';
                                                                break;
                                                            default:
                                                                $prioridadesTexto[] = 'este aspecto';
                                                        }
                                                    }
                                                    echo implode(' ni ', $prioridadesTexto);
                                                    echo ', sino que se enfoca más en su desarrollo personal y profesional.';
                                                }
                                                ?>
                                            </p>
                                            <?php else: ?>
                                            <p>No hay suficientes datos sobre motivaciones para realizar una interpretación detallada.</p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Entornos Óptimos -->
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">Entornos Óptimos</h5>
                                    </div>
                                    <div class="card-body">
                                        <div id="optimalEnvironments">
                                            <?php if (!empty($topMotivaciones)): ?>
                                            <ul class="environment-list">
                                                <?php
                                                // Generar recomendaciones basadas en las principales motivaciones
                                                $entornos = [];
                                                foreach ($topMotivaciones as $motiv) {
                                                    switch (strtolower($motiv['nombre'])) {
                                                        case 'logro':
                                                            $entornos[] = 'Orientados a resultados';
                                                            $entornos[] = 'Con objetivos claros y desafiantes';
                                                            break;
                                                        case 'autonomía':
                                                            $entornos[] = 'Con alto nivel de autonomía';
                                                            $entornos[] = 'Flexibilidad en métodos de trabajo';
                                                            break;
                                                        case 'reto':
                                                            $entornos[] = 'Proyectos desafiantes e innovadores';
                                                            $entornos[] = 'Oportunidades de aprendizaje continuo';
                                                            break;
                                                        case 'afiliación':
                                                            $entornos[] = 'Cultura colaborativa y de equipo';
                                                            $entornos[] = 'Ambiente social positivo';
                                                            break;
                                                        case 'poder':
                                                            $entornos[] = 'Oportunidades de liderazgo';
                                                            $entornos[] = 'Capacidad de influir en decisiones';
                                                            break;
                                                        case 'seguridad':
                                                            $entornos[] = 'Estructura y estabilidad';
                                                            $entornos[] = 'Políticas claras y consistentes';
                                                            break;
                                                    }
                                                }
                                                
                                                // Eliminar duplicados y limitar a 5
                                                $entornos = array_unique($entornos);
                                                $entornos = array_slice($entornos, 0, 5);
                                                
                                                // Mostrar entornos
                                                foreach ($entornos as $entorno) {
                                                    echo '<li>' . $entorno . '</li>';
                                                }
                                                ?>
                                                <li>Reconocimiento basado en logros</li>
                                            </ul>
                                            <?php else: ?>
                                            <p>No hay suficientes datos para determinar los entornos óptimos.</p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Pestaña de Competencias -->
                    <div class="tab-pane fade" id="competencies" role="tabpanel">
                        <div class="row">
                            <div class="col-md-8">
                                <!-- Gráfico de Competencias -->
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">Competencias Fundamentales</h5>
                                    </div>
                                    <div class="card-body">
                                        <canvas id="competencyRadarChart" height="350"></canvas>
                                    </div>
                                </div>
                                
                                <!-- Detalles de competencias -->
                                <div id="competencyDetailsCards" class="row">
                                    <?php
                                    if (isset($dimensiones['competencia'])) {
                                        foreach ($dimensiones['competencia'] as $comp) {
                                            $nivel = getNivelEvaluacion($comp['promedio']);
                                    ?>
                                    <div class="col-md-6 mb-4">
                                        <div class="card h-100">
                                            <div class="card-header bg-<?php echo $nivel['class']; ?> text-white">
                                                <h6 class="m-0 font-weight-bold"><?php echo $comp['nombre']; ?></h6>
                                            </div>
                                            <div class="card-body">
                                                <div class="text-center mb-3">
                                                    <div class="competency-circle" style="background: conic-gradient(<?php echo $nivel['color']; ?> <?php echo $comp['promedio'] * 3.6; ?>deg, #f1f1f1 0deg);">
                                                        <div class="competency-value"><?php echo round($comp['promedio']); ?>%</div>
                                                    </div>
                                                    <div class="mt-2 font-weight-bold" style="color: <?php echo $nivel['color']; ?>;">
                                                        <?php echo $nivel['texto']; ?>
                                                    </div>
                                                </div>
                                                <?php
                                                // Obtener descripción e interpretación de la base de datos
                                                $compId = $comp['id'];
                                                $compPromedio = $comp['promedio'];
                                                
                                                // Query para obtener interpretación apropiada para esta competencia y valor
                                                $interpretacionQuery = "SELECT * FROM interpretaciones 
                                                    WHERE dimension_id = $compId 
                                                    AND $compPromedio BETWEEN rango_min AND rango_max
                                                    LIMIT 1";
                                                
                                                try {
                                                    $interpretacion = null;
                                                    $resultInterp = $db->query($interpretacionQuery);
                                                    if ($resultInterp && $resultInterp->num_rows > 0) {
                                                        $interpretacion = $resultInterp->fetch_assoc();
                                                    }
                                                } catch (Exception $e) {
                                                    // Ignorar error
                                                }
                                                ?>
                                                
                                                <p class="competency-description mb-0">
                                                    <?php
                                                    if ($interpretacion && !empty($interpretacion['descripcion'])) {
                                                        echo htmlspecialchars($interpretacion['descripcion']);
                                                    } else {
                                                        // Descripción genérica basada en nivel
                                                        if ($comp['promedio'] >= 75) {
                                                            echo 'Demuestra un dominio sobresaliente de esta competencia, aplicándola de manera consistente y efectiva en diversos contextos y situaciones complejas.';
                                                        } elseif ($comp['promedio'] >= 60) {
                                                            echo 'Muestra un nivel adecuado de esta competencia, aplicándola efectivamente en situaciones habituales pero pudiendo beneficiarse de desarrollo para contextos más desafiantes.';
                                                        } else {
                                                            echo 'Presenta un nivel básico de esta competencia, pudiendo aplicarla en situaciones estructuradas pero requiriendo desarrollo significativo para mayor efectividad.';
                                                        }
                                                    }
                                                    ?>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                    <?php 
                                        }
                                    } else {
                                    ?>
                                    <div class="col-12">
                                        <div class="alert alert-info">
                                            No hay datos de competencias disponibles para este candidato.
                                        </div>
                                    </div>
                                    <?php } ?>
                                </div>
                            </div>
                            
                            <div class="col-md-4">
								<!-- Índices Compuestos -->
								<div class="card mb-4">
									<div class="card-header">
										<h5 class="card-title mb-0">Índices Compuestos</h5>
									</div>
									<div class="card-body">
										<?php if (!empty($indicesCompuestosDetallados)): ?>
										<div id="compoundIndices">
											<?php foreach ($indicesCompuestosDetallados as $indice): ?>
											<div class="compound-index mb-3">
												<div class="d-flex justify-content-between align-items-center mb-1">
													<h6 class="mb-0" title="<?php echo htmlspecialchars($indice['descripcion']); ?>"><?php echo htmlspecialchars($indice['nombre']); ?></h6>
													<span class="badge bg-<?php echo $indice['class']; ?>"><?php echo $indice['valor']; ?>%</span>
												</div>
												<div class="progress" style="height: 10px;">
													<div class="progress-bar bg-<?php echo $indice['class']; ?>" role="progressbar" 
														 style="width: <?php echo $indice['valor']; ?>%;" 
														 aria-valuenow="<?php echo $indice['valor']; ?>" aria-valuemin="0" aria-valuemax="100"></div>
												</div>
											</div>
											<?php endforeach; ?>
										</div>
										
										<div class="text-center mt-3">
											<p class="small text-muted">
												Los índices compuestos integran resultados de diferentes pruebas para ofrecer una visión más completa 
												de las capacidades del candidato en áreas clave.
											</p>
										</div>
										<?php else: ?>
										<div class="alert alert-info">
											<i class="fas fa-info-circle me-2"></i>
											No hay suficientes datos para calcular índices compuestos. Asigne más pruebas al candidato para generar un perfil más completo.
										</div>
										<?php endif; ?>
									</div>
								</div>

                                
                                <!-- Interpretación de competencias -->
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">Interpretación</h5>
                                    </div>
                                    <div class="card-body">
                                        <div id="competencyInterpretation">
                                            <?php if (isset($dimensiones['competencia'])): ?>
                                            <p>
                                                <?php$compOrdenadas = $dimensiones['competencia'];
                                                usort($compOrdenadas, function($a, $b) {
                                                    return $b['promedio'] - $a['promedio'];
                                                });
                                                
                                                $compAltas = array_filter($compOrdenadas, function($c) { 
                                                    return $c['promedio'] >= 75; 
                                                });
                                                
                                                if (!empty($compAltas)) {
                                                    $nombres = array_map(function($c) { 
                                                        return strtolower($c['nombre']); 
                                                    }, array_slice($compAltas, 0, 2));
                                                    
                                                    echo 'El candidato muestra un alto nivel de ' . implode(' e ', $nombres) . ', ';
                                                    
                                                    // Interpretación basada en competencias específicas
                                                    if (in_array('responsabilidad', $nombres) || in_array('integridad', $nombres)) {
                                                        echo 'lo que indica un fuerte compromiso con sus obligaciones y valores éticos. Estas características lo hacen muy confiable para posiciones que requieran alto grado de autonomía y manejo de información sensible.';
                                                    } else if (in_array('trabajo en equipo', $nombres) || in_array('comunicación', $nombres)) {
                                                        echo 'lo que sugiere una capacidad sobresaliente para colaborar eficazmente con otros, compartiendo información y contribuyendo positivamente a la dinámica grupal.';
                                                    } else {
                                                        echo 'lo que demuestra una fortaleza significativa en estas áreas clave para su desempeño profesional.';
                                                    }
                                                } else {
                                                    echo 'El candidato muestra un perfil de competencias balanceado sin áreas que destaquen particularmente, sugiriendo un desempeño consistente en diferentes aspectos profesionales.';
                                                }
                                                ?>
                                            </p>
                                            
                                            <p>
                                                <?php
                                                // Identificar áreas de mejora
                                                $compBajas = array_filter($compOrdenadas, function($c) { 
                                                    return $c['promedio'] < 60; 
                                                });
                                                
                                                if (!empty($compBajas)) {
                                                    $nombres = array_map(function($c) { 
                                                        return strtolower($c['nombre']); 
                                                    }, array_slice($compBajas, 0, 2));
                                                    
                                                    echo 'Las áreas con oportunidad de desarrollo incluyen ' . implode(' y ', $nombres) . ', ';
                                                    echo 'donde el fortalecimiento mediante capacitación específica podría mejorar significativamente su desempeño general.';
                                                } else {
                                                    // Si no hay competencias bajas, comentar sobre el equilibrio
                                                    $compMedias = array_filter($compOrdenadas, function($c) { 
                                                        return $c['promedio'] >= 60 && $c['promedio'] < 75; 
                                                    });
                                                    
                                                    if (!empty($compMedias)) {
                                                        $nombres = array_map(function($c) { 
                                                            return strtolower($c['nombre']); 
                                                        }, array_slice($compMedias, 0, 2));
                                                        
                                                        echo 'Muestra un nivel adecuado en ' . implode(' y ', $nombres) . ', ';
                                                        echo 'competencias que pueden fortalecerse para alcanzar un nivel de excelencia.';
                                                    } else {
                                                        echo 'Su perfil muestra un desarrollo equilibrado de competencias, sin áreas de mejora críticas.';
                                                    }
                                                }
                                                ?>
                                            </p>
                                            <?php else: ?>
                                            <p>No hay suficientes datos sobre competencias para realizar una interpretación detallada.</p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Recomendaciones de desarrollo -->
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">Recomendaciones de Desarrollo</h5>
                                    </div>
                                    <div class="card-body">
                                        <div id="developmentRecommendations">
                                            <?php if (isset($dimensiones['competencia'])): ?>
                                            <ul class="development-list">
                                                <?php
                                                // Identificar competencias a desarrollar
                                                $compDesarrollo = array_filter($compOrdenadas, function($c) { 
                                                    return $c['promedio'] < 75; 
                                                });
                                                
                                                if (!empty($compDesarrollo)) {
                                                    // Generar recomendaciones específicas
                                                    $recomendaciones = [];
                                                    foreach (array_slice($compDesarrollo, 0, 3) as $comp) {
                                                        switch (strtolower($comp['nombre'])) {
                                                            case 'comunicación':
                                                                $recomendaciones[] = 'Fortalecer habilidades de comunicación a través de prácticas de presentación y expresión oral';
                                                                break;
                                                            case 'adaptabilidad':
                                                                $recomendaciones[] = 'Exponer a situaciones de cambio progresivamente más desafiantes para desarrollar mayor adaptabilidad';
                                                                break;
                                                            case 'trabajo en equipo':
                                                                $recomendaciones[] = 'Participar en proyectos colaborativos que requieran coordinación estrecha con diversos perfiles';
                                                                break;
                                                            case 'liderazgo':
                                                                $recomendaciones[] = 'Asignar gradualmente roles de coordinación en equipos para desarrollar capacidades de liderazgo';
                                                                break;
                                                            default:
                                                                $recomendaciones[] = 'Desarrollar ' . strtolower($comp['nombre']) . ' mediante formación específica y práctica guiada';
                                                        }
                                                    }
                                                    
                                                    // Mostrar recomendaciones
                                                    foreach ($recomendaciones as $rec) {
                                                        echo '<li>' . $rec . '</li>';
                                                    }
                                                }
                                                
                                                // Agregar recomendaciones para fortalecer áreas fuertes
                                                if (!empty($compAltas)) {
                                                    echo '<li>Ofrecer mentorías para potenciar fortalezas en ' . 
                                                         implode(' y ', array_map(function($c) { return strtolower($c['nombre']); }, array_slice($compAltas, 0, 2))) . 
                                                         '</li>';
                                                }
                                                
                                                // Recomendación general
                                                echo '<li>Establecer un plan de desarrollo con objetivos específicos y medibles para cada competencia clave</li>';
                                                ?>
                                            </ul>
                                            <?php else: ?>
                                            <p>Se recomienda realizar evaluaciones de competencias para generar recomendaciones específicas de desarrollo.</p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Pestaña de Ajuste al Puesto -->
                    <div class="tab-pane fade" id="fit" role="tabpanel">
                        <div class="row">
                            <div class="col-md-7">
                                <!-- Gráfico de ajuste al puesto -->
                                <div class="card mb-4">
                                    <div class="card-header d-flex justify-content-between align-items-center">
                                        <h5 class="card-title mb-0">Ajuste al Perfil Ideal</h5>
                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button" id="perfilDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                                Seleccionar perfil
                                            </button>
                                            <ul class="dropdown-menu" aria-labelledby="perfilDropdown">
                                                <?php foreach ($perfilesIdeales as $perfil): ?>
                                                    <li><a class="dropdown-item profile-select" href="#" data-profile-id="<?php echo $perfil['id']; ?>"><?php echo $perfil['titulo']; ?></a></li>
                                                <?php endforeach; ?>
                                                
                                                <?php if (empty($perfilesIdeales)): ?>
                                                    <li><a class="dropdown-item" href="#">No hay perfiles disponibles</a></li>
                                                <?php endif; ?>
                                            </ul>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <canvas id="profileFitChart" height="350"></canvas>
                                    </div>
                                </div>
                                
                                <!-- Tabla comparativa -->
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">Comparativa Detallada</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-hover">
                                                <thead>
                                                    <tr>
                                                        <th>Dimensión</th>
                                                        <th>Candidato</th>
                                                        <th>Perfil Ideal</th>
                                                        <th>Ajuste</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="fitComparisonTable">
                                                    <!-- Se llenará vía JavaScript -->
                                                    <?php if (empty($evaluationResults)): ?>
                                                    <tr>
                                                        <td colspan="4" class="text-center">
                                                            Seleccione un perfil y complete evaluaciones para ver la comparativa
                                                        </td>
                                                    </tr>
                                                    <?php endif; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-5">
                                <!-- Puntuación general de ajuste -->
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">Puntuación de Ajuste</h5>
                                    </div>
                                    <div class="card-body text-center">
                                        <div class="gauge-container">
                                            <canvas id="fitScoreGauge" width="200" height="200"></canvas>
                                            <div id="fitGaugeValue" class="gauge-value">0%</div>
                                        </div>
                                        <h4 id="fitScoreText" class="mt-3">Seleccione un perfil</h4>
                                    </div>
                                </div>
                                
                                <!-- Fortalezas y gaps -->
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">Fortalezas y Gaps</h5>
                                    </div>
                                    <div class="card-body">
                                        <div id="fitStrengthsGaps">
                                            <div class="mb-3">
                                                <h6 class="text-success"><i class="fas fa-check-circle me-1"></i> Áreas de Ajuste</h6>
                                                <ul id="fitStrengthsList" class="mb-0">
                                                    <li>Seleccione un perfil para ver las áreas de ajuste</li>
                                                </ul>
                                            </div>
                                            <div>
                                                <h6 class="text-warning"><i class="fas fa-exclamation-circle me-1"></i> Áreas de Desarrollo</h6>
                                                <ul id="fitGapsList" class="mb-0">
                                                    <li>Seleccione un perfil para ver las áreas de desarrollo</li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Recomendaciones -->
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">Recomendaciones</h5>
                                    </div>
                                    <div class="card-body">
                                        <div id="fitRecommendations">
                                            <p>Seleccione un perfil para ver recomendaciones específicas de ajuste.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </main>
        </div>
    </div>
</div>

<!-- Modal para enviar informe por email -->
<div class="modal fade" id="emailModal" tabindex="-1" aria-labelledby="emailModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="emailModalLabel">Enviar Informe por Email</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="sendReportForm" action="enviar-informe.php" method="post">
                <div class="modal-body">
                    <input type="hidden" name="candidato_id" value="<?php echo $candidato_id; ?>">
                    <div class="mb-3">
                        <label for="recipientEmail" class="form-label">Email del destinatario</label>
                        <input type="email" class="form-control" id="recipientEmail" name="recipientEmail" required>
                    </div>
                    <div class="mb-3">
                        <label for="emailSubject" class="form-label">Asunto</label>
                        <input type="text" class="form-control" id="emailSubject" name="emailSubject" 
                               value="Informe de Evaluación - <?php echo $candidato['nombre'] . ' ' . $candidato['apellido']; ?>">
                    </div>
                    <div class="mb-3">
                        <label for="emailMessage" class="form-label">Mensaje</label>
                        <textarea class="form-control" id="emailMessage" name="emailMessage" rows="4">Adjunto encontrará el informe de evaluación del candidato <?php echo $candidato['nombre'] . ' ' . $candidato['apellido']; ?>.</textarea>
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="includeCV" name="includeCV" value="1" checked>
                        <label class="form-check-label" for="includeCV">Incluir CV del candidato (si está disponible)</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Enviar Informe</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Script para manejar la selección de perfil ideal y mostrar comparativa
document.addEventListener('DOMContentLoaded', function() {
    // Función para actualizar la comparativa cuando se selecciona un perfil
    function actualizarComparativa(perfilId, perfilNombre) {
        // Actualizar el título del dropdown
        document.getElementById('perfilDropdown').innerHTML = perfilNombre;
        
        // Obtener datos del candidato y perfil ideal
        fetch(`../api/get_profile_comparison.php?candidato_id=<?php echo $candidato_id; ?>&perfil_id=${perfilId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Actualizar gauge de ajuste
                    const fitScore = data.fit_score;
                    document.getElementById('fitGaugeValue').innerText = `${fitScore}%`;
                    
                    // Determinar texto del ajuste
                    let fitText = '';
                    if (fitScore >= 90) fitText = 'Ajuste excepcional';
                    else if (fitScore >= 80) fitText = 'Ajuste sobresaliente';
                    else if (fitScore >= 70) fitText = 'Buen ajuste';
                    else if (fitScore >= 60) fitText = 'Ajuste adecuado';
                    else if (fitScore >= 50) fitText = 'Ajuste moderado';
                    else if (fitScore >= 35) fitText = 'Ajuste bajo';
                    else fitText = 'Ajuste insuficiente';
                    
                    document.getElementById('fitScoreText').innerText = fitText;
                    
                    // Actualizar el chart de comparación
                    updateFitChart(data.dimensions);
                    
                    // Actualizar tabla comparativa
                    updateComparisonTable(data.dimensions);
                    
                    // Actualizar fortalezas y gaps
                    updateStrengthsGaps(data.strengths, data.gaps);
                    
                    // Actualizar recomendaciones
                    updateRecommendations(data.recommendations);
                }
            })
            .catch(error => console.error('Error:', error));
    }
    
    // Manejador para selección de perfil
    document.querySelectorAll('.profile-select').forEach(item => {
        item.addEventListener('click', function(e) {
            e.preventDefault();
            const perfilId = this.getAttribute('data-profile-id');
            const perfilNombre = this.innerText;
            actualizarComparativa(perfilId, perfilNombre);
        });
    });
    
    // Función para actualizar el gráfico de comparación
    function updateFitChart(dimensions) {
        // Implementación de actualización del chart
        // Usar Chart.js para dibujar el gráfico de radar comparativo
        
        // Ejemplo básico:
        const labels = dimensions.map(d => d.nombre);
        const candidateValues = dimensions.map(d => d.candidato_valor);
        const profileValues = dimensions.map(d => d.perfil_valor);
        
        // Obtener el contexto del canvas
        const ctx = document.getElementById('profileFitChart').getContext('2d');
        
        // Destruir chart previo si existe
        if (window.fitChart) {
            window.fitChart.destroy();
        }
        
        // Crear nuevo chart
        window.fitChart = new Chart(ctx, {
            type: 'radar',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Candidato',
                        data: candidateValues,
                        backgroundColor: 'rgba(78, 115, 223, 0.2)',
                        borderColor: '#4e73df',
                        pointBackgroundColor: '#4e73df',
                        pointRadius: 3
                    },
                    {
                        label: 'Perfil Ideal',
                        data: profileValues,
                        backgroundColor: 'rgba(28, 200, 138, 0.2)',
                        borderColor: '#1cc88a',
                        pointBackgroundColor: '#1cc88a',
                        pointRadius: 3
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    r: {
                        angleLines: {
                            display: true
                        },
                        beginAtZero: true,
                        max: 100,
                        ticks: {
                            stepSize: 20
                        }
                    }
                }
            }
        });
    }
    
    // Función para actualizar la tabla comparativa
    function updateComparisonTable(dimensions) {
        let html = '';
        
        dimensions.forEach(dim => {
            const diferencia = dim.candidato_valor - dim.perfil_valor;
            const diferenciaClass = diferencia >= 0 ? 'text-success' : 'text-danger';
            const diferenciaIcon = diferencia >= 0 ? 'fa-arrow-up' : 'fa-arrow-down';
            
            html += `
            <tr>
                <td>${dim.nombre}</td>
                <td><span class="badge bg-primary">${dim.candidato_valor}%</span></td>
                <td><span class="badge bg-success">${dim.perfil_valor}%</span></td>
                <td class="${diferenciaClass}">
                    <i class="fas ${diferenciaIcon} me-1"></i> ${Math.abs(diferencia)}%
                </td>
            </tr>
            `;
        });
        
        document.getElementById('fitComparisonTable').innerHTML = html;
    }
    
    // Actualizar fortalezas y gaps
    function updateStrengthsGaps(strengths, gaps) {
        let strengthsHtml = '';
        if (strengths && strengths.length > 0) {
            strengths.forEach(strength => {
                strengthsHtml += `<li>${strength}</li>`;
            });
        } else {
            strengthsHtml = '<li>No se identificaron áreas de ajuste destacadas</li>';
        }
        document.getElementById('fitStrengthsList').innerHTML = strengthsHtml;
        
        let gapsHtml = '';
        if (gaps && gaps.length > 0) {
            gaps.forEach(gap => {
                gapsHtml += `<li>${gap}</li>`;
            });
        } else {
            gapsHtml = '<li>No se identificaron brechas significativas</li>';
        }
        document.getElementById('fitGapsList').innerHTML = gapsHtml;
    }
    
    // Actualizar recomendaciones
    function updateRecommendations(recommendations) {
        let html = '';
        if (recommendations && recommendations.length > 0) {
            recommendations.forEach(rec => {
                html += `<p>${rec}</p>`;
            });
        } else {
            html = '<p>Seleccione un perfil para recibir recomendaciones específicas.</p>';
        }
        document.getElementById('fitRecommendations').innerHTML = html;
    }
    
    // Inicializar gráficos
    initializeCharts();
    
    // Manejar el modal de email
    document.getElementById('sendEmail').addEventListener('click', function() {
        new bootstrap.Modal(document.getElementById('emailModal')).show();
    });
});

// Función para inicializar todos los gráficos
function initializeCharts() {
    // Gráfico del gauge general
    const gaugeCtx = document.getElementById('generalScoreGauge');
    if (gaugeCtx) {
        new Chart(gaugeCtx, {
            type: 'doughnut',
            data: {
                datasets: [{
                    data: [<?php echo $promedioResultados; ?>, 100 - <?php echo $promedioResultados; ?>],
                    backgroundColor: ['<?php echo $nivelEvaluacion['color']; ?>', '#f1f1f1'],
                    borderWidth: 0,
                    circumference: 180,
                    rotation: 270
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '80%',
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        enabled: false
                    }
                }
            }
        });
    }
    
    // Gráfico de radar para el perfil general
    const radarCtx = document.getElementById('profileRadarChart');
    if (radarCtx && <?php echo !empty($evaluationResults) ? 'true' : 'false'; ?>) {
        const labels = [
            <?php 
            // Limitar a 8 dimensiones para mejor visualización
            $radarDimensions = array_slice($evaluationResults, 0, 8);
            foreach ($radarDimensions as $dim) {
                echo "'" . addslashes($dim['nombre']) . "', ";
            }
            ?>
        ];
        
        const values = [
            <?php 
            foreach ($radarDimensions as $dim) {
                echo round($dim['promedio']) . ", ";
            }
            ?>
        ];
        
        new Chart(radarCtx, {
            type: 'radar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Perfil del Candidato',
                    data: values,
                    backgroundColor: 'rgba(78, 115, 223, 0.2)',
                    borderColor: 'rgba(78, 115, 223, 1)',
                    pointBackgroundColor: 'rgba(78, 115, 223, 1)',
                    pointBorderColor: '#fff',
                    pointHoverBackgroundColor: '#fff',
                    pointHoverBorderColor: 'rgba(78, 115, 223, 1)',
                    pointRadius: 4
                }]
            },
            options: {
                scales: {
                    r: {
                        angleLines: {
                            display: true
                        },
                        beginAtZero: true,
                        max: 100,
                        ticks: {
                            stepSize: 20
                        }
                    }
                }
            }
        });
    }
    
    // Gráfico de barras para motivaciones
    const motivationBarCtx = document.getElementById('motivationBarChart');
    if (motivationBarCtx) {
        // Obtener datos de motivaciones
        <?php
        $motivLabels = [];
        $motivValues = [];
        $motivColors = [];
        
        // Usar datos reales si existen
        if (isset($dimensiones['motivacion']) && !empty($dimensiones['motivacion'])) {
            $motivacionesOrdenadas = $dimensiones['motivacion'];
            usort($motivacionesOrdenadas, function($a, $b) {
                return $b['promedio'] - $a['promedio'];
            });
            
            // Limitar a las 5 principales
            $topMotivaciones = array_slice($motivacionesOrdenadas, 0, 5);
            
            foreach ($topMotivaciones as $index => $motiv) {
                $motivLabels[] = $motiv['nombre'];
                $motivValues[] = round($motiv['promedio']);
                
                // Asignar colores según rango de valores
                if ($motiv['promedio'] >= 75) {
                    $motivColors[] = '#4e73df'; // Azul
                } elseif ($motiv['promedio'] >= 60) {
                    $motivColors[] = '#1cc88a'; // Verde
                } elseif ($motiv['promedio'] >= 50) {
                    $motivColors[] = '#36b9cc'; // Turquesa
                } else {
                    $motivColors[] = '#f6c23e'; // Amarillo
                }
            }
        } else {
            // Datos de ejemplo si no hay datos reales
            $motivLabels = ['Logro', 'Autonomía', 'Reto', 'Reconocimiento', 'Poder'];
            $motivValues = [85, 75, 68, 62, 55];
            $motivColors = ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#fd7e14'];
        }
        ?>
        
        new Chart(motivationBarCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($motivLabels); ?>,
                datasets: [{
                    label: 'Puntuación',
                    data: <?php echo json_encode($motivValues); ?>,
                    backgroundColor: <?php echo json_encode($motivColors); ?>,
                    borderWidth: 0
                }]
            },
            options: {
                indexAxis: 'y',
                scales: {
                    x: {
                        beginAtZero: true,
                        max: 100
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
    }
    
    // Gráfico para aptitudes cognitivas
    const cognitiveBarCtx = document.getElementById('cognitiveBarChart');
    if (cognitiveBarCtx && <?php echo isset($dimensiones['cognitiva']) ? 'true' : 'false'; ?>) {
        <?php if (isset($dimensiones['cognitiva'])): ?>
        const cogLabels = [<?php foreach ($dimensiones['cognitiva'] as $dim) echo "'" . addslashes($dim['nombre']) . "', "; ?>];
        const cogValues = [<?php foreach ($dimensiones['cognitiva'] as $dim) echo round($dim['promedio']) . ", "; ?>];
        const cogColors = [
            <?php 
            foreach ($dimensiones['cognitiva'] as $dim) {
                if ($dim['promedio'] >= 75) echo "'#1cc88a', "; // Verde
                elseif ($dim['promedio'] >= 60) echo "'#4e73df', "; // Azul
                else echo "'#f6c23e', "; // Amarillo
            }
            ?>
        ];
        
        new Chart(cognitiveBarCtx, {
            type: 'bar',
            data: {
                labels: cogLabels,
                datasets: [{
                    label: 'Puntuación',
                    data: cogValues,
                    backgroundColor: cogColors,
                    borderWidth: 0
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
        <?php endif; ?>
    }
    
    // Gráfico polar para personalidad
    const personalityPolarCtx = document.getElementById('personalityPolarChart');
    if (personalityPolarCtx && <?php echo isset($dimensiones['personalidad']) ? 'true' : 'false'; ?>) {
        <?php if (isset($dimensiones['personalidad'])): ?>
        const persLabels = [<?php foreach ($dimensiones['personalidad'] as $dim) echo "'" . addslashes($dim['nombre']) . "', "; ?>];
        const persValues = [<?php foreach ($dimensiones['personalidad'] as $dim) echo round($dim['promedio']) . ", "; ?>];
        const persColors = [
            'rgba(78, 115, 223, 0.7)',
            'rgba(28, 200, 138, 0.7)',
            'rgba(54, 185, 204, 0.7)',
            'rgba(246, 194, 62, 0.7)',
            'rgba(231, 74, 59, 0.7)',
            'rgba(133, 135, 150, 0.7)'
        ];
        
        new Chart(personalityPolarCtx, {
            type: 'polarArea',
            data: {
                labels: persLabels,
                datasets: [{
                    data: persValues,
                    backgroundColor: persColors,
                    borderWidth: 0
                }]
            },
            options: {
                scales: {
                    r: {
                        beginAtZero: true,
                        max: 100
                    }
                }
            }
        });
        <?php endif; ?>
    }
    
    // Gráfico de radar para motivaciones
    const motivationalRadarCtx = document.getElementById('motivationalRadarChart');
    if (motivationalRadarCtx) {
        // Usar los datos de motivaciones ya preparados para el gráfico de barras
        new Chart(motivationalRadarCtx, {
            type: 'radar',
            data: {
                labels: <?php echo json_encode($motivLabels); ?>,
                datasets: [{
                    label: 'Motivaciones',
                    data: <?php echo json_encode($motivValues); ?>,
                    backgroundColor: 'rgba(28, 200, 138, 0.2)',
                    borderColor: 'rgba(28, 200, 138, 1)',
                    pointBackgroundColor: 'rgba(28, 200, 138, 1)',
                    pointBorderColor: '#fff',
                    pointRadius: 4
                }]
            },
            options: {
                scales: {
                    r: {
                        beginAtZero: true,
                        max: 100
                    }
                }
            }
        });
    }
    
    // Gráfico de pie para motivaciones principales
    const motivationPieCtx = document.getElementById('motivationPieChart');
    if (motivationPieCtx) {
        <?php
        // Preparar datos de top motivaciones para pie chart
        $pieLabels = [];
        $pieValues = [];
        $pieColors = ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#fd7e14'];
        
        $topN = min(3, count($motivLabels));
        for ($i = 0; $i < $topN; $i++) {
            $pieLabels[] = $motivLabels[$i];
            $pieValues[] = $motivValues[$i];
        }
        // Agregar "Otros" si hay más de 3
        if (count($motivLabels) > 3) {
            $pieLabels[] = 'Otros';
            $pieValues[] = 100 - array_sum(array_slice($motivValues, 0, 3));
        }
        ?>
        
        new Chart(motivationPieCtx, {
            type: 'pie',
            data: {
                labels: <?php echo json_encode($pieLabels); ?>,
                datasets: [{
                    data: <?php echo json_encode($pieValues); ?>,
                    backgroundColor: <?php echo json_encode(array_slice($pieColors, 0, count($pieLabels))); ?>,
                    borderWidth: 0
                }]
            },
            options: {
                plugins: {
                    legend: {
                        position: 'right'
                    }
                }
            }
        });
    }
    
    // Gráfico de radar para competencias
    const competencyRadarCtx = document.getElementById('competencyRadarChart');
    if (competencyRadarCtx && <?php echo isset($dimensiones['competencia']) ? 'true' : 'false'; ?>) {
        <?php if (isset($dimensiones['competencia'])): ?>
        const compLabels = [<?php foreach ($dimensiones['competencia'] as $dim) echo "'" . addslashes($dim['nombre']) . "', "; ?>];
        const compValues = [<?php foreach ($dimensiones['competencia'] as $dim) echo round($dim['promedio']) . ", "; ?>];
        
        new Chart(competencyRadarCtx, {
            type: 'radar',
            data: {
                labels: compLabels,
                datasets: [{
                    label: 'Competencias',
                    data: compValues,
                    backgroundColor: 'rgba(54, 185, 204, 0.2)',
                    borderColor: 'rgba(54, 185, 204, 1)',
                    pointBackgroundColor: 'rgba(54, 185, 204, 1)',
                    pointBorderColor: '#fff',
                    pointRadius: 4
                }]
            },
            options: {
                scales: {
                    r: {
                        beginAtZero: true,
                        max: 100
                    }
                }
            }
        });
        <?php endif; ?>
    }
    
    // Gauge para puntaje de ajuste
    const fitScoreGaugeCtx = document.getElementById('fitScoreGauge');
    if (fitScoreGaugeCtx) {
        // Inicialmente con valor cero, se actualizará al seleccionar un perfil
        new Chart(fitScoreGaugeCtx, {
            type: 'doughnut',
            data: {
                datasets: [{
                    data: [0, 100],
                    backgroundColor: ['#e9ecef', '#e9ecef'],
                    borderWidth: 0,
                    circumference: 180,
                    rotation: 270
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '80%',
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        enabled: false
                    }
                }
            }
        });
    }
}
</script>

<?php include '../includes/footer.php'; ?>