<?php
session_start();

// Verificar que el usuario esté autenticado como candidato
if (!isset($_SESSION['candidato_id'])) {
    header('Location: login.php');
    exit;
}

// Incluir archivos necesarios
require_once '../includes/jobs-system.php';

// Obtener ID de la sesión
$sesion_id = isset($_GET['sesion_id']) ? (int)$_GET['sesion_id'] : 0;

// Si no se especificó una sesión, redirigir al panel
if ($sesion_id === 0) {
    header('Location: pruebas.php');
    exit;
}

// Instanciar gestores necesarios
$candidateManager = new CandidateManager();
$testManager = new TestManager();

// Obtener datos del candidato
$candidato_id = $_SESSION['candidato_id'];
$candidato = $candidateManager->getCandidateById($candidato_id);

// Verificar si existe la sesión y pertenece al candidato
$session_info = $testManager->getSessionById($sesion_id);

if (!$session_info) {
    header('Location: pruebas.php?error=sesion_no_encontrada');
    exit;
} else if ($session_info['candidato_id'] != $candidato_id) {
    header('Location: pruebas.php?error=acceso_no_autorizado');
    exit;
}

// Verificar que la sesión esté completada
if ($session_info['estado'] !== 'completada') {
    header('Location: prueba.php?id=' . $session_info['prueba_id']);
    exit;
}

// Título de la página
$pageTitle = "Resultados de " . ($session_info['prueba_titulo'] ?? "Evaluación");

// Obtener resultados de la sesión
try {
    $resultados = $testManager->getSessionResults($sesion_id);
    
    // Verificar si hay resultados
    if (empty($resultados)) {
        $error_message = "No se encontraron resultados para esta evaluación.";
    }
    
    // Determinar resultado global PRIMERO
    $resultado_global = null;
    if (isset($resultados['sesion']['resultado_global'])) {
        $resultado_global = $resultados['sesion']['resultado_global'];
    } else if (isset($session_info['resultado_global'])) {
        $resultado_global = $session_info['resultado_global'];
    }

    // Mejorar visualización de dimensiones con datos reales
    $dimensiones = [];
	
    // Determinar el tipo de prueba basado en su estructura
    $db = Database::getInstance();
    $sql = "SELECT COUNT(*) as count FROM preguntas 
            WHERE prueba_id = {$session_info['prueba_id']} AND tipo_pregunta = 'pares'";
    $result_tipo = $db->query($sql);
    $es_prueba_pares = ($result_tipo && $result_tipo->fetch_assoc()['count'] > 0);

    if ($es_prueba_pares) {
        // Procesamiento especial para pruebas tipo "pares" (CMV, IPL)
        $sql = "SELECT o.dimension_id, d.nombre, COUNT(*) as count
                FROM respuestas r
                JOIN opciones_respuesta o ON r.opcion_id = o.id
                JOIN dimensiones d ON o.dimension_id = d.id
                WHERE r.sesion_id = $sesion_id
                GROUP BY o.dimension_id, d.nombre";
        
        $result = $db->query($sql);
        $total_respuestas = 0;
        $conteo_dimensiones = [];
        
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $conteo_dimensiones[$row['dimension_id']] = [
                    'nombre' => $row['nombre'],
                    'count' => $row['count']
                ];
                $total_respuestas += $row['count'];
            }
            
            // Calcular porcentajes
            foreach ($conteo_dimensiones as $dim_id => $data) {
                $porcentaje = round(($data['count'] / $total_respuestas) * 100);
                $dimensiones[] = [
                    'nombre' => $data['nombre'],
                    'porcentaje' => $porcentaje
                ];
            }
        }
    } else {
        // Consultar directamente la base de datos para obtener dimensiones
        try {
            $db = Database::getInstance();
            
            // Solo obtener resultados con valor > 0
            $sql = "SELECT r.*, d.nombre as dimension_nombre 
                    FROM resultados r
                    JOIN dimensiones d ON r.dimension_id = d.id
                    WHERE r.sesion_id = $sesion_id AND r.valor > 0
                    ORDER BY r.valor DESC";
            
            $result = $db->query($sql);
            
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $dimensiones[] = [
                        'nombre' => $row['dimension_nombre'],
                        'porcentaje' => $row['valor'],
                        'interpretacion' => $row['interpretacion'] ?? null
                    ];
                }
            }
        } catch (Exception $e) {
            error_log("Error al obtener dimensiones: " . $e->getMessage());
        }
        
        // Si la consulta directa no devolvió resultados, probar otros métodos
        if (empty($dimensiones)) {
            // Si existe la estructura dimensiones en resultados
            if (isset($resultados['dimensiones']) && !empty($resultados['dimensiones'])) {
                $dimensiones = $resultados['dimensiones'];
            } 
            // Si los resultados tienen el formato alternativo
            elseif (!empty($resultados) && isset($resultados[0]) && isset($resultados[0]['dimension_nombre'])) {
                $dimensionesTmp = [];
                foreach ($resultados as $resultado) {
                    if (!isset($dimensionesTmp[$resultado['dimension_id']]) && ($resultado['valor'] > 0 || $resultado['valor_normalizado'] > 0)) {
                        $dimensionesTmp[$resultado['dimension_id']] = [
                            'nombre' => $resultado['dimension_nombre'],
                            'porcentaje' => $resultado['valor'] ?? $resultado['valor_normalizado'] ?? 0,
                            'interpretacion' => $resultado['interpretacion'] ?? null
                        ];
                    }
                }
                $dimensiones = array_values($dimensionesTmp);
            }
        }
        
        // Si todavía no hay dimensiones pero tenemos resultado global, crear una dimensión genérica
        if (empty($dimensiones) && $resultado_global !== null && $resultado_global > 0) {
            $dimensiones = [
                [
                    'nombre' => 'Motivación General',
                    'porcentaje' => $resultado_global,
                    'interpretacion' => 'Nivel general de motivación basado en todas las áreas evaluadas.'
                ]
            ];
        }
        
        // Si todavía no tenemos resultado global pero tenemos dimensiones, calcularlo
        if (($resultado_global === null || $resultado_global === 0) && !empty($dimensiones)) {
            $total = 0;
            $count = count($dimensiones);
            foreach ($dimensiones as $dimension) {
                $total += $dimension['porcentaje'];
            }
            $resultado_global = round($total / $count);
        }
        
        // Si todavía no tenemos resultados, intentar forzar la consulta más general
        if (empty($dimensiones)) {
            try {
                $db = Database::getInstance();
                $sql = "SELECT r.*, d.nombre as dimension_nombre 
                        FROM resultados r
                        JOIN dimensiones d ON r.dimension_id = d.id
                        WHERE r.sesion_id = $sesion_id
                        ORDER BY r.valor DESC
                        LIMIT 8";
                
                $result = $db->query($sql);
                
                if ($result && $result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $dimensiones[] = [
                            'nombre' => $row['dimension_nombre'],
                            'porcentaje' => max(1, $row['valor']), // Asegurar un valor mínimo visible
                            'interpretacion' => $row['interpretacion'] ?? null
                        ];
                    }
                }
            } catch (Exception $e) {
                error_log("Error en consulta final para dimensiones: " . $e->getMessage());
            }
        }
        
        // Si todavía no hay dimensiones, intentar obtenerlas directamente de resultados
        if (empty($dimensiones)) {
            $sql = "SELECT r.*, d.nombre as dimension_nombre
                    FROM resultados r
                    JOIN dimensiones d ON r.dimension_id = d.id
                    WHERE r.sesion_id = $sesion_id AND r.valor > 0
                    ORDER BY r.valor DESC";
            
            $result = $db->query($sql);
            
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $dimensiones[] = [
                        'nombre' => $row['dimension_nombre'],
                        'porcentaje' => $row['valor'],
                        'interpretacion' => $row['interpretacion'] ?? null
                    ];
                }
            }
        }
    }
    
    // Verificar si el usuario tiene premium
    $isPremium = isset($candidato['premium']) && $candidato['premium'] == 1;
    
} catch (Exception $e) {
    $error_message = "Ocurrió un error al obtener los resultados de la evaluación. Por favor, contacta a soporte.";
}

// Obtener recomendaciones
$recomendaciones = isset($resultados['recomendaciones']) ? $resultados['recomendaciones'] : '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - SolFis Talentos</title>
    
    <!-- CSS -->
    <link rel="stylesheet" href="../assets/css/normalize.css">
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="css/candidato.css">
    <link rel="stylesheet" href="css/pruebas.css">
    
    <!-- Estilos adicionales para la página de resultados -->
    <style>
    .result-overview {
        background-color: white;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        padding: 30px;
        margin-bottom: 30px;
    }
    
    .result-overview-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 20px;
        padding-bottom: 20px;
        border-bottom: 1px solid #e9ecef;
    }
    
    .result-overview-title h2 {
        margin: 0 0 10px;
        color: #003366;
        font-size: 1.8rem;
    }
    
    .completed-date {
        color: #6c757d;
        font-size: 0.9rem;
    }
    
    .completed-date i {
        margin-right: 5px;
        color: #28a745;
    }
    
    .result-overview-score {
        text-align: center;
        padding: 15px 25px;
        background-color: #f0f8ff;
        border-radius: 10px;
        color: #0088cc;
    }
    
    .score-value {
        font-size: 2.5rem;
        font-weight: 700;
        line-height: 1;
    }
    
    .score-label {
        font-size: 0.9rem;
        margin-top: 5px;
    }
    
    .result-bars {
        margin-top: 20px;
    }
    
    .result-bar-item {
        margin-bottom: 20px;
    }
    
    .result-bar-label {
        display: flex;
        justify-content: space-between;
        margin-bottom: 8px;
    }
    
    .result-bar-name {
        font-weight: 500;
    }
    
    .result-bar-value {
        font-weight: 700;
    }
    
    .result-bar-container {
        height: 12px;
        background-color: #e9ecef;
        border-radius: 6px;
        overflow: hidden;
    }
    
    .result-bar-fill {
        height: 100%;
        border-radius: 6px;
        transition: width 1s ease-in-out;
    }
    
    .result-bar-fill.high {
        background-color: #28a745;
    }
    
    .result-bar-fill.medium {
        background-color: #17a2b8;
    }
    
    .result-bar-fill.low {
        background-color: #ffc107;
    }
    
    .dimension-interpretation {
        margin-top: 8px;
        font-size: 0.9rem;
        color: #6c757d;
        padding-left: 10px;
        border-left: 3px solid #e9ecef;
    }
    
    .result-section {
        margin-bottom: 30px;
        padding-bottom: 20px;
        border-bottom: 1px solid #e9ecef;
    }
    
    .result-section:last-child {
        border-bottom: none;
        padding-bottom: 0;
    }
    
    .result-section h3 {
        color: #003366;
        font-size: 1.3rem;
        margin-bottom: 15px;
        display: flex;
        align-items: center;
    }
    
    .result-section h3 i {
        margin-right: 10px;
    }
    
    /* Nuevos estilos para premium */
    .premium-teaser {
        background: linear-gradient(135deg, #f8f9fa, #e9f5ff);
        border-radius: 12px;
        padding: 25px;
        margin: 30px 0;
        border: 1px solid #e0e0e0;
        position: relative;
    }
    
    .premium-teaser h3 {
        color: #0069d9;
        margin-top: 0;
        display: flex;
        align-items: center;
    }
    
    .premium-teaser h3 i {
        margin-right: 10px;
        color: #ffc107;
    }
    
    .premium-features {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 15px;
        margin: 20px 0;
    }
    
    .premium-feature {
        background: white;
        border-radius: 8px;
        padding: 15px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    }
    
    .premium-feature i {
        color: #ffc107;
        margin-right: 8px;
    }
    
    .premium-feature h4 {
        margin: 0 0 8px;
        font-size: 1rem;
    }
    
    .btn-premium {
        background: linear-gradient(135deg, #ffc107, #ff9800);
        color: white;
        border: none;
        padding: 10px 20px;
        border-radius: 5px;
        font-weight: 600;
        text-decoration: none;
        display: inline-block;
        transition: all 0.3s ease;
    }
    
    .btn-premium:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 10px rgba(255, 152, 0, 0.3);
        color: white;
    }
    
    .blurred-section {
        position: relative;
        overflow: hidden;
    }
    
    .blur-overlay {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        backdrop-filter: blur(5px);
        background-color: rgba(255, 255, 255, 0.7);
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        z-index: 10;
    }
    
    .blur-overlay-content {
        text-align: center;
        max-width: 80%;
    }
    
    .blur-overlay-content i {
        font-size: 2rem;
        color: #ffc107;
        margin-bottom: 15px;
    }
    
.blur-overlay-content h4 {
        margin-bottom: 10px;
    }
    
    @media (max-width: 768px) {
        .result-overview-header {
            flex-direction: column;
        }
        
        .result-overview-score {
            margin-top: 15px;
            align-self: stretch;
        }
    }
    </style>
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <!-- Navbar -->
    <?php include 'includes/navbar.php'; ?>
    
    <div class="dashboard-container">
        <!-- Sidebar -->
        <?php include 'includes/sidebar-fix.php'; ?>
        
        <main class="dashboard-content">
            <div class="tests-container">
                <div class="breadcrumbs mb-3">
                    <a href="panel.php">Panel</a> 
                    <span class="separator">/</span>
                    <a href="pruebas.php">Evaluaciones</a>
                    <span class="separator">/</span>
                    <span class="current">Resultados</span>
                </div>
                
                <?php if (isset($error_message)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <div><?php echo $error_message; ?></div>
                </div>
                <?php else: ?>
                
                <div class="result-overview animate-fade-in">
                    <div class="result-overview-header">
                        <div class="result-overview-title">
                            <h2><?php echo htmlspecialchars($resultados['sesion']['prueba_titulo'] ?? $session_info['prueba_titulo']); ?></h2>
                            <div class="completed-date">
                                <i class="fas fa-calendar-check"></i> Completada el <?php echo date('d/m/Y', strtotime($resultados['sesion']['fecha_fin'] ?? $session_info['fecha_fin'])); ?>
                            </div>
                        </div>
                        
                        <?php if ($resultado_global !== null): ?>
                        <div class="result-overview-score">
                            <div class="score-value"><?php echo $resultado_global; ?>%</div>
                            <div class="score-label">Resultado global</div>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="result-message">
                        <?php 
                        $resultado = $resultado_global ?? 0;
                        
                        if ($resultado >= 80) {
                            echo '<div class="alert alert-success">';
                            echo '<i class="fas fa-award"></i>';
                            echo '<div><h3>¡Excelente resultado!</h3>';
                            echo '<p>Has demostrado un gran desempeño en esta evaluación. ' . ($isPremium ? '' : 'Para conocer en detalle tus fortalezas específicas y cómo aprovecharlas al máximo, considera adquirir un plan premium.') . '</p></div>';
                            echo '</div>';
                        } elseif ($resultado >= 60) {
                            echo '<div class="alert alert-info">';
                            echo '<i class="fas fa-thumbs-up"></i>';
                            echo '<div><h3>Buen resultado</h3>';
                            echo '<p>Tu desempeño en esta evaluación ha sido satisfactorio. ' . ($isPremium ? '' : 'Para descubrir qué áreas específicas destacan y cuáles puedes mejorar, explora nuestros planes premium.') . '</p></div>';
                            echo '</div>';
                        } else {
                            echo '<div class="alert alert-warning">';
                            echo '<i class="fas fa-lightbulb"></i>';
                            echo '<div><h3>Resultado en desarrollo</h3>';
                            echo '<p>Tu evaluación indica que hay áreas de oportunidad para mejorar. ' . ($isPremium ? '' : 'Obtén recomendaciones personalizadas sobre cómo desarrollar tus habilidades con nuestros planes premium.') . '</p></div>';
                            echo '</div>';
                        }
						
					echo '<pre style="display:none;">';
					print_r($resultados);
					echo '</pre>';
                        ?>
                    </div>
					

                    
<!-- Después de la sección del mensaje de alerta -->

<div class="result-sections">
    <div class="result-section">
        <h3><i class="fas fa-chart-bar"></i> Resultados por dimensión</h3>
        
        <?php 
        // Debug detallado
        if (isset($_GET['debug']) && $_GET['debug'] == 1): 
        ?>
        <div style="background: #f8f9fa; padding: 10px; border: 1px solid #ddd; margin-bottom: 20px; font-family: monospace; font-size: 12px;">
            <h4>Debug Información:</h4>
            <p>Resultado global: <?php echo $resultado_global; ?></p>
            <p>Dimensiones encontradas: <?php echo count($dimensiones); ?></p>
            <pre><?php print_r($dimensiones); ?></pre>
            <p>Estructura de resultados:</p>
            <pre><?php print_r($resultados); ?></pre>
        </div>
        <?php endif; ?>
        
        <div class="result-bars">
            <?php 
            if (empty($dimensiones)): 
            ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i>
                <div>
                    <p>No se encontraron datos detallados de dimensiones para esta evaluación. Esto puede deberse a que:</p>
                    <ul>
                        <li>La evaluación es muy reciente y los resultados detallados están en procesamiento</li>
                        <li>El tipo de prueba no incluye análisis dimensional</li>
                    </ul>
                    <p>Si crees que esto es un error, por favor contáctanos.</p>
                </div>
            </div>
            <?php 
            else:
                // Si es usuario premium, mostrar todas las dimensiones
                if ($isPremium) {
                    $dimensionesToShow = $dimensiones;
                } else {
                    // Si no es premium, mostrar solo 2 dimensiones o todas si hay menos de 2
                    $dimensionesToShow = array_slice($dimensiones, 0, min(2, count($dimensiones)));
                }
				
				foreach ($dimensionesToShow as $key => $dimension) {
					// Asegurar que las claves necesarias estén presentes
					if (!isset($dimension['nombre'])) {
						$dimensionesToShow[$key]['nombre'] = 'Dimensión ' . ($key + 1);
					}
					if (!isset($dimension['porcentaje'])) {
						$dimensionesToShow[$key]['porcentaje'] = 0;
					}
				} ?>
                
<?php foreach ($dimensionesToShow as $dimension): ?>
<div class="result-bar-item">
    <div class="result-bar-label">
        <span class="result-bar-name"><?php echo htmlspecialchars($dimension['nombre'] ?? 'Dimensión sin nombre'); ?></span>
        <span class="result-bar-value"><?php echo isset($dimension['porcentaje']) ? $dimension['porcentaje'] : 0; ?>%</span>
    </div>
    <div class="result-bar-container">
        <?php 
        $porcentaje = isset($dimension['porcentaje']) ? $dimension['porcentaje'] : 0;
        $barClass = $porcentaje >= 80 ? 'high' : ($porcentaje >= 60 ? 'medium' : 'low');
        ?>
        <div class="result-bar-fill <?php echo $barClass; ?>" style="width: <?php echo $porcentaje; ?>%"></div>
    </div>
    <?php if ($isPremium && isset($dimension['interpretacion']) && !empty($dimension['interpretacion'])): ?>
    <div class="dimension-interpretation">
        <?php echo htmlspecialchars($dimension['interpretacion']); ?>
    </div>
    <?php endif; ?>
</div>
<?php endforeach; ?>
             <?php   
                if (!$isPremium && count($dimensiones) > 2): 
            ?>
            <!-- Sección difuminada para usuarios no premium -->
            <div class="blurred-section" style="margin-top: 30px;">
                <div class="blur-overlay">
                    <div class="blur-overlay-content">
                        <i class="fas fa-lock"></i>
                        <h4>Desbloquea todas las dimensiones</h4>
                        <p>Accede a tu perfil psicométrico completo con interpretaciones detalladas</p>
                        <a href="premium.php" class="btn btn-premium">Ver planes premium</a>
                    </div>
                </div>
                
                <!-- Mostrar dimensiones difuminadas como teaser -->
                <?php for ($i = 2; $i < min(5, count($dimensiones)); $i++): ?>
                <div class="result-bar-item">
                    <div class="result-bar-label">
                        <span class="result-bar-name"><?php echo htmlspecialchars($dimensiones[$i]['nombre']); ?></span>
                        <span class="result-bar-value">?? %</span>
                    </div>
                    <div class="result-bar-container">
                        <div class="result-bar-fill" style="width: 65%;"></div>
                    </div>
                </div>
                <?php endfor; ?>
            </div>
            <?php 
                endif;
            endif; 
            ?>
        </div>
    </div>
                        
                        <?php if ($isPremium): ?>
                        <!-- Mostrar retroalimentación detallada solo para usuarios premium -->
                        <div class="result-section">
                            <h3><i class="fas fa-comment-dots"></i> Retroalimentación general</h3>
                            <?php if (!empty($recomendaciones)): ?>
                                <div class="recommendation">
                                    <?php echo $recomendaciones; ?>
                                </div>
                            <?php else: ?>
                                <p>Basado en tus resultados, te recomendamos continuar desarrollando tus habilidades y conocimientos en las áreas evaluadas. Esta evaluación es solo una herramienta para ayudarte a identificar tus fortalezas y áreas de oportunidad.</p>
                                
                                <p>Recuerda que las empresas valoran tanto las competencias técnicas como las habilidades blandas, por lo que te recomendamos trabajar en ambos aspectos para incrementar tus posibilidades de éxito profesional.</p>
                            <?php endif; ?>
                        </div>
                        
                        <div class="result-section">
                            <h3><i class="fas fa-file-alt"></i> Certificado de finalización</h3>
                            <p>Has completado con éxito esta evaluación. Este resultado ha sido registrado en tu perfil y será visible para los reclutadores cuando apliques a vacantes compatibles con tu perfil.</p>
                            
                            <div class="text-center mt-4">
                                <a href="#" class="btn btn-primary" id="downloadCert">
                                    <i class="fas fa-download"></i> Descargar certificado
                                </a>
                            </div>
                        </div>
                        <?php elseif (count($dimensiones) > 2): ?>
                        <!-- Mostrar promoción premium para usuarios no premium -->
                        <div class="premium-teaser">
                            <h3><i class="fas fa-crown"></i> Desbloquea tu perfil psicométrico completo</h3>
                            <p>Con tu plan premium obtendrás acceso a análisis detallados que te ayudarán a impulsar tu carrera y destacar entre otros candidatos.</p>
                            
                            <div class="premium-features">
                                <div class="premium-feature">
                                    <h4><i class="fas fa-chart-line"></i> Análisis completo</h4>
                                    <p>Todas tus dimensiones evaluadas con interpretaciones detalladas</p>
                                </div>
                                <div class="premium-feature">
                                    <h4><i class="fas fa-lightbulb"></i> Recomendaciones</h4>
                                    <p>Consejos personalizados basados en tu perfil único</p>
                                </div>
                                <div class="premium-feature">
                                    <h4><i class="fas fa-briefcase"></i> Compatibilidad laboral</h4>
                                    <p>Descubre los roles donde más destacarías</p>
                                </div>
                                <div class="premium-feature">
                                    <h4><i class="fas fa-file-download"></i> Certificado</h4>
                                    <p>Descarga y comparte tus resultados profesionales</p>
                                </div>
                            </div>
                            
                            <div class="text-center mt-4">
                                <a href="premium.php" class="btn-premium">
                                    <i class="fas fa-unlock"></i> Ver planes premium
                                </a>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <?php endif; ?>
                
                <div class="text-center mt-4">
                    <a href="pruebas.php" class="btn btn-outline-primary">
                        <i class="fas fa-arrow-left"></i> Volver a mis evaluaciones
                    </a>
                </div>
            </div>
        </main>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Animación de barras de resultados
            const resultBars = document.querySelectorAll('.result-bar-fill');
            
            resultBars.forEach(bar => {
                const width = bar.style.width;
                bar.style.width = '0';
                
                setTimeout(() => {
                    bar.style.transition = 'width 1s ease-in-out';
                    bar.style.width = width;
                }, 300);
            });
            
            // Funcionalidad para el botón de certificado
            const certificateBtn = document.getElementById('downloadCert');
            if (certificateBtn) {
                certificateBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    alert('Esta función estará disponible próximamente.');
                });
            }
        });
    </script>
</body>
</html>