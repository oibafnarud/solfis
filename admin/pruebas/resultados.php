<?php
/**
 * Panel de Administración para SolFis
 * admin/pruebas/resultados.php - Ver resultados detallados de una prueba
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

// Verificar que se proporciona un ID de sesión de prueba
if (!isset($_GET['session_id']) || empty($_GET['session_id'])) {
    $_SESSION['error'] = "ID de sesión de prueba no proporcionado";
    header('Location: index.php');
    exit;
}

$sesion_id = (int)$_GET['session_id'];

// Obtener datos de la prueba y del candidato
$db = Database::getInstance();
$sesion_id = $db->real_escape_string($sesion_id);

$sql = "SELECT sp.*, p.titulo as prueba_titulo, p.descripcion as prueba_descripcion, 
               c.id as candidato_id, c.nombre as candidato_nombre, c.apellido as candidato_apellido, 
               c.email as candidato_email, c.foto_path as candidato_foto
        FROM sesiones_prueba sp
        JOIN pruebas p ON sp.prueba_id = p.id
        JOIN candidatos c ON sp.candidato_id = c.id
        WHERE sp.id = '$sesion_id'";

$result = $db->query($sql);

if (!$result || $result->num_rows === 0) {
    $_SESSION['error'] = "Sesión de prueba no encontrada";
    header('Location: index.php');
    exit;
}

$prueba = $result->fetch_assoc();
$candidato_id = $prueba['candidato_id'];

// Verificar que la prueba está completada
if ($prueba['estado'] !== 'completada') {
    $_SESSION['error'] = "La prueba aún no ha sido completada por el candidato";
    header("Location: ../candidatos/detalle.php?id=$candidato_id");
    exit;
}

// Obtener resultados por dimensiones
$sqlDimensiones = "SELECT d.id, d.nombre, d.descripcion, d.categoria, 
                        AVG(r.valor) as promedio,
                        MIN(r.valor) as valor_min,
                        MAX(r.valor) as valor_max,
                        COUNT(r.id) as num_respuestas,
                        CASE 
                            WHEN AVG(r.valor) >= 90 THEN 'Excepcional' 
                            WHEN AVG(r.valor) >= 80 THEN 'Sobresaliente'
                            WHEN AVG(r.valor) >= 70 THEN 'Notable'
                            WHEN AVG(r.valor) >= 60 THEN 'Adecuado' 
                            WHEN AVG(r.valor) >= 50 THEN 'Moderado'
                            WHEN AVG(r.valor) >= 35 THEN 'En desarrollo'
                            ELSE 'Incipiente' 
                        END as nivel,
                        CASE 
                            WHEN AVG(r.valor) >= 90 THEN 'success' 
                            WHEN AVG(r.valor) >= 80 THEN 'success'
                            WHEN AVG(r.valor) >= 70 THEN 'info'
                            WHEN AVG(r.valor) >= 60 THEN 'primary' 
                            WHEN AVG(r.valor) >= 50 THEN 'warning'
                            WHEN AVG(r.valor) >= 35 THEN 'warning'
                            ELSE 'danger' 
                        END as clase_nivel
                 FROM resultados r
                 JOIN dimensiones d ON r.dimension_id = d.id
                 WHERE r.sesion_id = '$sesion_id'
                 GROUP BY d.id
                 ORDER BY promedio DESC";

$resultDimensiones = $db->query($sqlDimensiones);
$dimensiones = [];

if ($resultDimensiones && $resultDimensiones->num_rows > 0) {
    while ($row = $resultDimensiones->fetch_assoc()) {
        $dimensiones[] = $row;
    }
}

// Calcular promedio global
$promedioGlobal = 0;
$totalDimensiones = count($dimensiones);

if ($totalDimensiones > 0) {
    $sumaPromedios = 0;
    foreach ($dimensiones as $dimension) {
        $sumaPromedios += $dimension['promedio'];
    }
    $promedioGlobal = round($sumaPromedios / $totalDimensiones);
}

// Determinar nivel global
function getNivelEvaluacion($valor) {
    if ($valor >= 90) return ['texto' => 'Excepcional', 'descripcion' => 'Desempeño sobresaliente, muy por encima de la media', 'color' => '#006400', 'class' => 'success'];
    else if ($valor >= 80) return ['texto' => 'Sobresaliente', 'descripcion' => 'Desempeño destacado, por encima de la media', 'color' => '#008000', 'class' => 'success'];
    else if ($valor >= 70) return ['texto' => 'Notable', 'descripcion' => 'Buen desempeño, superior a la media', 'color' => '#90EE90', 'class' => 'info'];
    else if ($valor >= 60) return ['texto' => 'Adecuado', 'descripcion' => 'Desempeño satisfactorio, cumple con lo esperado', 'color' => '#FFFF00', 'class' => 'primary'];
    else if ($valor >= 50) return ['texto' => 'Moderado', 'descripcion' => 'Desempeño aceptable, en el promedio esperado', 'color' => '#FFFFE0', 'class' => 'warning'];
    else if ($valor >= 35) return ['texto' => 'En desarrollo', 'descripcion' => 'Desempeño por debajo del promedio, necesita desarrollo', 'color' => '#FFA500', 'class' => 'warning'];
    else return ['texto' => 'Incipiente', 'descripcion' => 'Desempeño significativamente bajo, requiere atención especial', 'color' => '#FF0000', 'class' => 'danger'];
}

$nivelGlobal = getNivelEvaluacion($promedioGlobal);

// Obtener interpretaciones para cada dimensión
function getInterpretacionesDimension($dimension_id, $valor) {
    $db = Database::getInstance();
    $dimension_id = (int)$dimension_id;
    $valor = (float)$valor;
    
    $sql = "SELECT * FROM interpretaciones 
            WHERE dimension_id = $dimension_id 
            AND $valor >= rango_min AND $valor <= rango_max 
            LIMIT 1";
    
    $result = $db->query($sql);
    
    if ($result && $result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    
    return [
        'descripcion' => 'No hay interpretación disponible para este nivel.',
        'implicacion_laboral' => null,
        'recomendacion' => null
    ];
}

// Obtener respuestas individuales
$sqlRespuestas = "SELECT r.*, i.texto as item_texto, d.nombre as dimension_nombre 
                  FROM resultados r
                  LEFT JOIN items i ON r.item_id = i.id
                  LEFT JOIN dimensiones d ON r.dimension_id = d.id
                  WHERE r.sesion_id = '$sesion_id'
                  ORDER BY r.id";

$resultRespuestas = $db->query($sqlRespuestas);
$respuestas = [];

if ($resultRespuestas && $resultRespuestas->num_rows > 0) {
    while ($row = $resultRespuestas->fetch_assoc()) {
        $respuestas[] = $row;
    }
}

// Agrupar dimensiones por categoría
$dimensionesCategoria = [];
foreach ($dimensiones as $dimension) {
    $categoria = !empty($dimension['categoria']) ? $dimension['categoria'] : 'sin_categoria';
    
    if (!isset($dimensionesCategoria[$categoria])) {
        $dimensionesCategoria[$categoria] = [];
    }
    
    $dimensionesCategoria[$categoria][] = $dimension;
}

// Identificar fortalezas y áreas de mejora
$fortalezas = [];
$areasDesarrollo = [];

foreach ($dimensiones as $dimension) {
    if ($dimension['promedio'] >= 75) {
        $fortalezas[] = $dimension;
    } else if ($dimension['promedio'] < 60) {
        $areasDesarrollo[] = $dimension;
    }
}

// Limitar a las principales
$fortalezasPrincipales = array_slice($fortalezas, 0, 3);
$areasPrincipales = array_slice($areasDesarrollo, 0, 3);

// Título de la página
$pageTitle = 'Resultados de Prueba - ' . $prueba['prueba_titulo'];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    
    <!-- CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="../css/admin.css">
    <link rel="stylesheet" href="../candidatos/css/candidato-resultados.css">
    
    <!-- Chart.js para gráficos -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <!-- Header -->
    <?php include '../includes/header.php'; ?>
    
    <div class="admin-main">
        <div class="container-fluid">
            <div class="row">
                <!-- Sidebar -->
                <?php include '../includes/sidebar.php'; ?>
                
                <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                        <h1 class="h2">Resultados: <?php echo htmlspecialchars($prueba['prueba_titulo']); ?></h1>
                        <div class="btn-toolbar mb-2 mb-md-0">
                            <a href="../candidatos/detalle.php?id=<?php echo $candidato_id; ?>&tab=evaluaciones" class="btn btn-sm btn-outline-secondary me-2">
                                <i class="fas fa-arrow-left"></i> Volver al Perfil
                            </a>
                            <a href="../candidatos/resultados.php?id=<?php echo $candidato_id; ?>" class="btn btn-sm btn-outline-primary me-2">
                                <i class="fas fa-chart-bar"></i> Dashboard Completo
                            </a>
                            <button type="button" class="btn btn-sm btn-outline-success" onclick="window.print()">
                                <i class="fas fa-print"></i> Imprimir Resultados
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
                    
                    <!-- Información General -->
                    <div class="card mb-4">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><i class="fas fa-user me-2"></i> Información del Candidato y Prueba</h5>
                            <span class="badge bg-<?php echo $nivelGlobal['class']; ?> p-2">
                                Resultado Global: <?php echo $promedioGlobal; ?>% - <?php echo $nivelGlobal['texto']; ?>
                            </span>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <h6>Datos del Candidato</h6>
                                    <div class="d-flex align-items-center mb-3">
                                        <?php if (!empty($prueba['candidato_foto'])): ?>
                                            <img src="<?php echo '../../uploads/profile_photos/' . $prueba['candidato_foto']; ?>" class="rounded-circle me-3" width="50" height="50" alt="Foto de perfil">
                                        <?php else: ?>
                                            <div class="rounded-circle bg-secondary d-flex align-items-center justify-content-center me-3" style="width: 50px; height: 50px;">
                                                <i class="fas fa-user text-white"></i>
                                            </div>
                                        <?php endif; ?>
                                        <div>
                                            <h5 class="mb-0"><?php echo htmlspecialchars($prueba['candidato_nombre'] . ' ' . $prueba['candidato_apellido']); ?></h5>
                                            <p class="text-muted mb-0"><?php echo htmlspecialchars($prueba['candidato_email']); ?></p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <h6>Datos de la Evaluación</h6>
                                    <ul class="list-unstyled">
                                        <li><strong>Prueba:</strong> <?php echo htmlspecialchars($prueba['prueba_titulo']); ?></li>
                                        <li><strong>Fecha Inicio:</strong> <?php echo date('d/m/Y H:i', strtotime($prueba['fecha_inicio'])); ?></li>
                                        <li><strong>Fecha Fin:</strong> <?php echo date('d/m/Y H:i', strtotime($prueba['fecha_fin'])); ?></li>
                                        <li><strong>Duración:</strong> 
                                            <?php 
                                            $inicio = new DateTime($prueba['fecha_inicio']);
                                            $fin = new DateTime($prueba['fecha_fin']);
                                            $duracion = $inicio->diff($fin);
                                            echo $duracion->format('%H:%I:%S'); 
                                            ?>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Resumen de Resultados -->
                    <div class="row">
                        <div class="col-lg-4">
                            <!-- Puntaje Global -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Puntaje Global</h5>
                                </div>
                                <div class="card-body text-center">
                                    <div class="gauge-container position-relative">
                                        <canvas id="generalScoreGauge" width="200" height="100"></canvas>
                                        <div class="position-absolute" style="top: 50%; left: 50%; transform: translate(-50%, -50%);">
                                            <h2 class="mb-0"><?php echo $promedioGlobal; ?>%</h2>
                                        </div>
                                    </div>
                                    <h5 class="mt-3 text-<?php echo $nivelGlobal['class']; ?>"><?php echo $nivelGlobal['texto']; ?></h5>
                                    <p class="text-muted"><?php echo $nivelGlobal['descripcion']; ?></p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-lg-8">
                            <!-- Fortalezas y Áreas de Desarrollo -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Principales Hallazgos</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <h6 class="text-success"><i class="fas fa-arrow-up me-1"></i> Fortalezas</h6>
                                            <?php if (!empty($fortalezasPrincipales)): ?>
                                            <ul class="mb-0">
                                                <?php foreach ($fortalezasPrincipales as $fortaleza): ?>
                                                <li>
                                                    <strong><?php echo htmlspecialchars($fortaleza['nombre']); ?></strong> 
                                                    (<?php echo round($fortaleza['promedio']); ?>%) - 
                                                    <?php echo $fortaleza['nivel']; ?>
                                                </li>
                                                <?php endforeach; ?>
                                            </ul>
                                            <?php else: ?>
                                            <p class="text-muted">No se identificaron fortalezas destacadas en esta evaluación.</p>
                                            <?php endif; ?>
                                        </div>
                                        <div class="col-md-6">
                                            <h6 class="text-warning"><i class="fas fa-arrow-down me-1"></i> Áreas de Desarrollo</h6>
                                            <?php if (!empty($areasPrincipales)): ?>
                                            <ul class="mb-0">
                                                <?php foreach ($areasPrincipales as $area): ?>
                                                <li>
                                                    <strong><?php echo htmlspecialchars($area['nombre']); ?></strong> 
                                                    (<?php echo round($area['promedio']); ?>%) - 
                                                    <?php echo $area['nivel']; ?>
                                                </li>
                                                <?php endforeach; ?>
                                            </ul>
                                            <?php else: ?>
                                            <p class="text-muted">No se identificaron áreas que requieran desarrollo prioritario.</p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Gráfico de Radar -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Perfil de Dimensiones</h5>
                                </div>
                                <div class="card-body">
                                    <div style="height: 250px;">
                                        <canvas id="dimensionsRadarChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Resultados por Dimensiones -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Resultados por Dimensiones</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>Dimensión</th>
                                            <th>Categoría</th>
                                            <th class="text-center">Puntaje</th>
                                            <th>Nivel</th>
                                            <th>Interpretación</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($dimensiones as $dimension): 
                                            $interpretacion = getInterpretacionesDimension($dimension['id'], $dimension['promedio']);
                                        ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($dimension['nombre']); ?></strong></td>
                                            <td><?php echo ucfirst(htmlspecialchars($dimension['categoria'] ?? 'General')); ?></td>
                                            <td class="text-center">
                                                <div class="d-flex align-items-center justify-content-center">
                                                    <span class="me-2"><?php echo round($dimension['promedio']); ?>%</span>
                                                    <div class="progress flex-grow-1" style="height: 8px; max-width: 100px;">
                                                        <div class="progress-bar bg-<?php echo $dimension['clase_nivel']; ?>" 
                                                             role="progressbar" 
                                                             style="width: <?php echo $dimension['promedio']; ?>%;" 
                                                             aria-valuenow="<?php echo $dimension['promedio']; ?>" 
                                                             aria-valuemin="0" 
                                                             aria-valuemax="100"></div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><span class="badge bg-<?php echo $dimension['clase_nivel']; ?>"><?php echo $dimension['nivel']; ?></span></td>
                                            <td><?php echo htmlspecialchars($interpretacion['descripcion']); ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Dimensiones por Categoría -->
                    <?php foreach ($dimensionesCategoria as $categoria => $categoriaDimensiones): ?>
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0"><?php echo ucfirst(htmlspecialchars($categoria)); ?></h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <!-- Gráfico de Barras -->
                                <div class="col-lg-6">
                                    <canvas id="chart<?php echo str_replace(' ', '', ucwords($categoria)); ?>" height="250"></canvas>
                                </div>
                                
                                <!-- Detalles e Interpretación -->
                                <div class="col-lg-6">
                                    <div class="accordion" id="accordion<?php echo str_replace(' ', '', ucwords($categoria)); ?>">
                                        <?php foreach ($categoriaDimensiones as $index => $dim): 
                                            $interpretacion = getInterpretacionesDimension($dim['id'], $dim['promedio']);
                                            $accordionId = str_replace(' ', '', ucwords($categoria)) . $index;
                                        ?>
                                        <div class="accordion-item mb-2">
                                            <h2 class="accordion-header" id="heading<?php echo $accordionId; ?>">
                                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" 
                                                        data-bs-target="#collapse<?php echo $accordionId; ?>" aria-expanded="false" 
                                                        aria-controls="collapse<?php echo $accordionId; ?>">
                                                    <div class="d-flex justify-content-between w-100 me-3">
                                                        <span><?php echo htmlspecialchars($dim['nombre']); ?></span>
                                                        <span class="badge bg-<?php echo $dim['clase_nivel']; ?>"><?php echo round($dim['promedio']); ?>%</span>
                                                    </div>
                                                </button>
                                            </h2>
                                            <div id="collapse<?php echo $accordionId; ?>" class="accordion-collapse collapse" 
                                                 aria-labelledby="heading<?php echo $accordionId; ?>" 
                                                 data-bs-parent="#accordion<?php echo str_replace(' ', '', ucwords($categoria)); ?>">
                                                <div class="accordion-body">
                                                    <p><strong>Descripción:</strong> <?php echo htmlspecialchars($dim['descripcion'] ?? 'No disponible'); ?></p>
                                                    <p><strong>Interpretación:</strong> <?php echo htmlspecialchars($interpretacion['descripcion']); ?></p>
                                                    
                                                    <?php if (!empty($interpretacion['implicacion_laboral'])): ?>
                                                    <p><strong>Implicación laboral:</strong> <?php echo htmlspecialchars($interpretacion['implicacion_laboral']); ?></p>
                                                    <?php endif; ?>
                                                    
                                                    <?php if (!empty($interpretacion['recomendacion'])): ?>
                                                    <p><strong>Recomendación:</strong> <?php echo htmlspecialchars($interpretacion['recomendacion']); ?></p>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    
                    <!-- Conclusiones y Recomendaciones -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Conclusiones y Recomendaciones</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <h6>Resumen de Perfil</h6>
                                    <p>
                                        <?php 
                                        // Generar texto de resumen basado en resultados
                                        echo "El candidato muestra un nivel " . strtolower($nivelGlobal['texto']) . " en esta evaluación. ";
                                        
                                        if (!empty($fortalezasPrincipales)) {
                                            echo "Sus principales fortalezas se encuentran en ";
                                            $nombres = array_map(function($item) { 
                                                return strtolower($item['nombre']); 
                                            }, $fortalezasPrincipales);
                                            echo implode(', ', array_slice($nombres, 0, -1));
                                            if (count($nombres) > 1) {
                                                echo " y " . end($nombres);
                                            } else if (count($nombres) == 1) {
                                                echo $nombres[0];
                                            }
                                            echo ". ";
                                        }
                                        
                                        if (!empty($areasPrincipales)) {
                                            echo "Las áreas que presentan oportunidad de desarrollo son ";
                                            $nombres = array_map(function($item) { 
                                                return strtolower($item['nombre']); 
                                            }, $areasPrincipales);
                                            echo implode(', ', array_slice($nombres, 0, -1));
                                            if (count($nombres) > 1) {
                                                echo " y " . end($nombres);
                                            } else if (count($nombres) == 1) {
                                                echo $nombres[0];
                                            }
                                            echo ".";
                                        }
                                        ?>
                                    </p>
                                </div>
                                <div class="col-md-6">
                                    <h6>Recomendaciones</h6>
                                    <ul>
                                        <?php if ($promedioGlobal >= 75): ?>
                                        <li>Considerar al candidato para posiciones que requieran alto nivel de desempeño en <?php echo !empty($fortalezasPrincipales) ? strtolower($fortalezasPrincipales[0]['nombre']) : 'su área de especialidad'; ?>.</li>
                                        <li>Aprovechar sus fortalezas asignándole proyectos donde pueda aplicar sus capacidades destacadas.</li>
                                        <li>Ofrecer oportunidades de desarrollo en roles de liderazgo o mentoring en sus áreas de expertise.</li>
                                        <?php elseif ($promedioGlobal >= 60): ?>
                                        <li>El candidato muestra un perfil adecuado para posiciones que requieran las competencias evaluadas.</li>
                                        <li>Complementar con entrevistas enfocadas en las áreas de mejora identificadas.</li>
                                        <li>Considerar un plan de desarrollo específico para potenciar áreas con oportunidad de mejora.</li>
                                        <?php else: ?>
                                        <li>Realizar evaluaciones adicionales para complementar estos resultados.</li>
                                        <li>Considerar programas de formación específicos antes de asignar responsabilidades en las áreas con menor puntuación.</li>
                                        <li>Evaluar la adecuación del candidato para posiciones que requieran menor énfasis en las áreas con puntuación baja.</li>
                                        <?php endif; ?>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Botones de Acción -->
                    <div class="d-flex justify-content-between mb-5">
                        <a href="../candidatos/detalle.php?id=<?php echo $candidato_id; ?>&tab=evaluaciones" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left"></i> Volver al Perfil
                        </a>
                        <div>
                            <button type="button" class="btn btn-outline-primary me-2" id="exportPDF">
                                <i class="fas fa-file-pdf"></i> Exportar PDF
                            </button>
                            <button type="button" class="btn btn-outline-success" id="sendByEmail">
                                <i class="fas fa-envelope"></i> Enviar por Email
                            </button>
                        </div>
                    </div>
                </main>
            </div>
        </div>
    </div>
    
    <!-- Modal para enviar por email -->
    <div class="modal fade" id="emailModal" tabindex="-1" aria-labelledby="emailModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="emailModalLabel">Enviar Resultados por Email</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="sendEmailForm" action="enviar-resultados.php" method="post">
                    <div class="modal-body">
                        <input type="hidden" name="session_id" value="<?php echo $sesion_id; ?>">
                        <div class="mb-3">
                            <label for="emailTo" class="form-label">Destinatario</label>
                            <input type="email" class="form-control" id="emailTo" name="email_to" required>
                        </div>
                        <div class="mb-3">
                            <label for="emailSubject" class="form-label">Asunto</label>
                            <input type="text" class="form-control" id="emailSubject" name="email_subject" 
                                   value="Resultados de <?php echo htmlspecialchars($prueba['prueba_titulo']); ?> - <?php echo htmlspecialchars($prueba['candidato_nombre'] . ' ' . $prueba['candidato_apellido']); ?>">
                        </div>
                        <div class="mb-3">
                            <label for="emailMessage" class="form-label">Mensaje</label>
                            <textarea class="form-control" id="emailMessage" name="email_message" rows="4">Adjunto encontrará los resultados de la evaluación realizada al candidato.</textarea>
                        </div>
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="includeCandidateEmail" name="include_candidate" value="1">
                            <label class="form-check-label" for="includeCandidateEmail">
                                Enviar copia al candidato
                            </label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Enviar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Footer -->
    <?php include '../includes/footer.php'; ?>
    
    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Gráfico de Gauge para puntaje global
        const gaugeCtx = document.getElementById('generalScoreGauge').getContext('2d');
        const score = <?php echo $promedioGlobal; ?>;
        
        // Determinar color según puntuación
        let color = '#f6c23e'; // Amarillo (default)
        if (score >= 80) {
            color = '#1cc88a'; // Verde
        } else if (score >= 60) {
            color = '#4e73df'; // Azul
        } else if (score < 40) {
            color = '#e74a3b'; // Rojo
        }
        
        new Chart(gaugeCtx, {
            type: 'doughnut',
            data: {
                datasets: [{
                    data: [score, 100 - score],
                    backgroundColor: [color, '#e9ecef'],
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
        
        // Gráfico de Radar para las dimensiones
        const radarCtx = document.getElementById('dimensionsRadarChart').getContext('2d');
        
        // Preparar datos para el radar chart - limitar a 8 dimensiones para mayor claridad
        <?php 
        $topDimensions = array_slice($dimensiones, 0, 8);
        $dimensionLabels = array_map(function($dim) { return $dim['nombre']; }, $topDimensions);
        $dimensionValues = array_map(function($dim) { return round($dim['promedio']); }, $topDimensions);
        ?>
        
        new Chart(radarCtx, {
            type: 'radar',
            data: {
                labels: <?php echo json_encode($dimensionLabels); ?>,
                datasets: [{
                    label: 'Puntuación',
                    data: <?php echo json_encode($dimensionValues); ?>,
                    backgroundColor: 'rgba(78, 115, 223, 0.2)',
                    borderColor: '#4e73df',
                    pointBackgroundColor: '#4e73df',
                    pointBorderColor: '#fff',
                    pointHoverBackgroundColor: '#fff',
                    pointHoverBorderColor: '#4e73df',
                    pointRadius: 3
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
                        beginAtZero: true,
                        max: 100,
                        ticks: {
                            stepSize: 20
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
        
        // Crear gráficos para cada categoría
        <?php foreach ($dimensionesCategoria as $categoria => $categoriaDimensiones): 
            $categoriaId = str_replace(' ', '', ucwords($categoria));
            $labels = array_map(function($dim) { return $dim['nombre']; }, $categoriaDimensiones);
            $valores = array_map(function($dim) { return round($dim['promedio']); }, $categoriaDimensiones);
            $colores = array_map(function($dim) { 
                switch($dim['clase_nivel']) {
                    case 'success': return '#1cc88a';
                    case 'primary': return '#4e73df';
                    case 'info': return '#36b9cc';
                    case 'warning': return '#f6c23e';
                    case 'danger': return '#e74a3b';
                    default: return '#858796';
                }
            }, $categoriaDimensiones);
        ?>
        
        // Solo crear gráfico si existe el canvas
        const ctx<?php echo $categoriaId; ?> = document.getElementById('chart<?php echo $categoriaId; ?>');
        if (ctx<?php echo $categoriaId; ?>) {
new Chart(ctx<?php echo $categoriaId; ?>, {
                type: 'bar',
                data: {
                    labels: <?php echo json_encode($labels); ?>,
                    datasets: [{
                        label: '<?php echo ucfirst(htmlspecialchars($categoria)); ?>',
                        data: <?php echo json_encode($valores); ?>,
                        backgroundColor: <?php echo json_encode($colores); ?>,
                        borderColor: <?php echo json_encode($colores); ?>,
                        borderWidth: 1
                    }]
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        x: {
                            beginAtZero: true,
                            max: 100,
                            grid: {
                                drawBorder: false
                            }
                        },
                        y: {
                            grid: {
                                display: false,
                                drawBorder: false
                            }
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
        <?php endforeach; ?>
        
        // Manejar eventos para botones de exportación y email
        document.getElementById('exportPDF').addEventListener('click', function() {
            alert('Exportando a PDF... Funcionalidad en desarrollo.');
            // Aquí iría la lógica de exportación a PDF
        });
        
        document.getElementById('sendByEmail').addEventListener('click', function() {
            $('#emailModal').modal('show');
        });
    });
    </script>
</body>
</html>