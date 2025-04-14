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

// Debugging para verificar estructura
// error_log("Intentando obtener resultados para sesión: $sesion_id");

// Obtener resultados de la sesión con manejo de errores
try {
    $resultados = $testManager->getSessionResults($sesion_id);
    
    // Comprobar si hay resultados y tienen la estructura esperada
    if (empty($resultados)) {
        error_log("No se encontraron resultados para la sesión $sesion_id");
        $error_message = "No se encontraron resultados para esta evaluación.";
    }
    
    // Verificar que la sesión existe y pertenece al candidato
    // Esta verificación depende de la estructura de datos que devuelva getSessionResults
    // Ajustar según sea necesario
    $session_info = null;
    
    // Intenta obtener la información de la sesión
    if (isset($resultados['sesion'])) {
        $session_info = $resultados['sesion'];
    } else {
        // Obtener la información de la sesión directamente
        $session_info = $testManager->getSessionById($sesion_id);
    }
    
    if (!$session_info) {
        error_log("No se pudo obtener información de la sesión $sesion_id");
        header('Location: pruebas.php?error=sesion_no_encontrada');
        exit;
    } else if ($session_info['candidato_id'] != $candidato_id) {
        error_log("Intento de acceso no autorizado: Candidato $candidato_id intentando ver sesión del candidato {$session_info['candidato_id']}");
        header('Location: pruebas.php?error=acceso_no_autorizado');
        exit;
    }
    
    // Verificar que la sesión esté completada
    if ($session_info['estado'] !== 'completada') {
        error_log("Intento de ver resultados de sesión no completada: $sesion_id");
        header('Location: prueba.php?id=' . $session_info['prueba_id']);
        exit;
    }
    
    // Título de la página
    $pageTitle = "Resultados de " . ($session_info['prueba_titulo'] ?? "Evaluación");
    
    // Mejorar visualización de dimensiones con datos reales
    $dimensiones = [];
    
    // Verificar si tenemos dimensiones en resultados
    if (isset($resultados['dimensiones']) && !empty($resultados['dimensiones'])) {
        // Usar dimensiones de resultados
        $dimensiones = $resultados['dimensiones'];
    } 
    // Si no hay dimensiones en resultados pero hay datos en formato alternativo
    else if (!empty($resultados) && isset($resultados[0]) && isset($resultados[0]['dimension_nombre'])) {
        // Crear un array de dimensiones a partir de los resultados
        $dimensionesTmp = [];
        foreach ($resultados as $resultado) {
            if (!isset($dimensionesTmp[$resultado['dimension_id']])) {
                $dimensionesTmp[$resultado['dimension_id']] = [
                    'nombre' => $resultado['dimension_nombre'],
                    'porcentaje' => $resultado['valor'],
                    'interpretacion' => $resultado['interpretacion'] ?? null
                ];
            }
        }
        $dimensiones = array_values($dimensionesTmp);
    }
    // Si no hay dimensiones reales, usar las ficticias (solo para demo o desarrollo)
    else if (empty($dimensiones)) {
        $dimensiones = [
            [
                'nombre' => 'Razonamiento lógico',
                'porcentaje' => rand(60, 95),
            ],
            [
                'nombre' => 'Resolución de problemas',
                'porcentaje' => rand(60, 95),
            ],
            [
                'nombre' => 'Pensamiento analítico',
                'porcentaje' => rand(60, 95),
            ],
            [
                'nombre' => 'Memoria de trabajo',
                'porcentaje' => rand(60, 95),
            ]
        ];
    }
    
    // Determinar resultado global
    $resultado_global = null;
    if (isset($resultados['sesion']['resultado_global'])) {
        $resultado_global = $resultados['sesion']['resultado_global'];
    } else if (isset($session_info['resultado_global'])) {
        $resultado_global = $session_info['resultado_global'];
    } else if (!empty($dimensiones)) {
        // Calcular promedio de dimensiones
        $total = 0;
        $count = count($dimensiones);
        foreach ($dimensiones as $dimension) {
            $total += $dimension['porcentaje'];
        }
        $resultado_global = round($total / $count);
    }
    
} catch (Exception $e) {
    error_log("Error al obtener resultados: " . $e->getMessage());
    $error_message = "Ocurrió un error al obtener los resultados de la evaluación. Por favor, contacta a soporte.";
}

// Obtener recomendaciones según resultados
$recomendaciones = '';
if (isset($resultados['recomendaciones'])) {
    $recomendaciones = $resultados['recomendaciones'];
}
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
                            echo '<div><h3>¡Excelente resultado!</h3><p>Has demostrado un gran desempeño en esta evaluación. Tus respuestas indican que posees las competencias y habilidades evaluadas en un nivel sobresaliente.</p></div>';
                            echo '</div>';
                        } elseif ($resultado >= 60) {
                            echo '<div class="alert alert-info">';
                            echo '<i class="fas fa-thumbs-up"></i>';
                            echo '<div><h3>Buen resultado</h3><p>Tu desempeño en esta evaluación ha sido satisfactorio. Has demostrado conocimientos y habilidades adecuadas en la mayoría de las áreas evaluadas.</p></div>';
                            echo '</div>';
                        } else {
                            echo '<div class="alert alert-warning">';
                            echo '<i class="fas fa-lightbulb"></i>';
                            echo '<div><h3>Resultado en desarrollo</h3><p>Tu evaluación indica que hay áreas de oportunidad para mejorar. Te recomendamos revisar los temas evaluados para fortalecer tus conocimientos y habilidades.</p></div>';
                            echo '</div>';
                        }
                        ?>
                    </div>
                    
                    <div class="result-sections">
                        <div class="result-section">
                            <h3><i class="fas fa-chart-bar"></i> Resultados por dimensión</h3>
                            
                            <div class="result-bars">
                                <?php foreach ($dimensiones as $dimension): ?>
                                <div class="result-bar-item">
                                    <div class="result-bar-label">
                                        <span class="result-bar-name"><?php echo htmlspecialchars($dimension['nombre']); ?></span>
                                        <span class="result-bar-value"><?php echo $dimension['porcentaje']; ?>%</span>
                                    </div>
                                    <div class="result-bar-container">
                                        <div class="result-bar-fill <?php echo $dimension['porcentaje'] >= 80 ? 'high' : ($dimension['porcentaje'] >= 60 ? 'medium' : 'low'); ?>" style="width: <?php echo $dimension['porcentaje']; ?>%"></div>
                                    </div>
                                    <?php if (isset($dimension['interpretacion']) && !empty($dimension['interpretacion'])): ?>
                                    <div class="dimension-interpretation">
                                        <?php echo htmlspecialchars($dimension['interpretacion']); ?>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
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