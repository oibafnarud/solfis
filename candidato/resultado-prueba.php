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

// Obtener resultados de la sesión
$resultados = $testManager->getSessionResults($sesion_id);

// Verificar que la sesión existe y pertenece al candidato
if (!$resultados || $resultados['sesion']['candidato_id'] != $candidato_id) {
    header('Location: pruebas.php?error=sesion_no_encontrada');
    exit;
}

// Verificar que la sesión esté completada
if ($resultados['sesion']['estado'] !== 'completada') {
    header('Location: prueba.php?id=' . $resultados['sesion']['prueba_id']);
    exit;
}

// Título de la página
$pageTitle = "Resultados de " . $resultados['sesion']['prueba_titulo'];
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
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <!-- Navbar -->
    <?php include 'includes/navbar.php'; ?>
    
    <div class="dashboard-container">
        <!-- Sidebar -->
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="dashboard-content">
            <div class="tests-container">
                <div class="breadcrumbs mb-3">
                    <a href="panel.php">Panel</a> 
                    <span class="separator">/</span>
                    <a href="pruebas.php">Evaluaciones</a>
                    <span class="separator">/</span>
                    <span class="current">Resultados</span>
                </div>
                
                <div class="result-overview animate-fade-in">
                    <div class="result-overview-header">
                        <div class="result-overview-title">
                            <h2><?php echo htmlspecialchars($resultados['sesion']['prueba_titulo']); ?></h2>
                            <div class="completed-date">
                                <i class="fas fa-calendar-check"></i> Completada el <?php echo date('d/m/Y', strtotime($resultados['sesion']['fecha_fin'])); ?>
                            </div>
                        </div>
                        
                        <?php if (isset($resultados['sesion']['resultado_global'])): ?>
                        <div class="result-overview-score">
                            <div class="score-value"><?php echo $resultados['sesion']['resultado_global']; ?>%</div>
                            <div class="score-label">Resultado global</div>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="result-message">
                        <?php 
                        $resultado = isset($resultados['sesion']['resultado_global']) ? $resultados['sesion']['resultado_global'] : 0;
                        
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
                            
                            <?php
                            // Esta es una implementación simplificada
                            // En un sistema real, tendrías que agrupar por dimensiones
                            
                            // Ejemplo de dimensiones ficticias para demo
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
                            ?>
                            
                            <div class="result-bars">
                                <?php foreach ($dimensiones as $dimension): ?>
                                <div class="result-bar-item">
                                    <div class="result-bar-label">
                                        <span class="result-bar-name"><?php echo $dimension['nombre']; ?></span>
                                        <span class="result-bar-value"><?php echo $dimension['porcentaje']; ?>%</span>
                                    </div>
                                    <div class="result-bar-container">
                                        <div class="result-bar-fill <?php echo $dimension['porcentaje'] >= 80 ? 'high' : ($dimension['porcentaje'] >= 60 ? 'medium' : 'low'); ?>" style="width: <?php echo $dimension['porcentaje']; ?>%"></div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                        <div class="result-section">
                            <h3><i class="fas fa-comment-dots"></i> Retroalimentación general</h3>
                            <p>Basado en tus resultados, te recomendamos continuar desarrollando tus habilidades y conocimientos en las áreas evaluadas. Esta evaluación es solo una herramienta para ayudarte a identificar tus fortalezas y áreas de oportunidad.</p>
                            
                            <p>Recuerda que las empresas valoran tanto las competencias técnicas como las habilidades blandas, por lo que te recomendamos trabajar en ambos aspectos para incrementar tus posibilidades de éxito profesional.</p>
                        </div>
                        
                        <div class="result-section">
                            <h3><i class="fas fa-file-alt"></i> Certificado de finalización</h3>
                            <p>Has completado con éxito esta evaluación. Este resultado ha sido registrado en tu perfil y será visible para los reclutadores cuando apliques a vacantes compatibles con tu perfil.</p>
                            
                            <div class="text-center mt-4">
                                <a href="#" class="btn btn-primary">
                                    <i class="fas fa-download"></i> Descargar certificado
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                
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
        });
    </script>
</body>
</html>