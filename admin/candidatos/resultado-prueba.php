<?php
session_start();

// Verificar que el usuario esté autenticado como candidato
if (!isset($_SESSION['candidato_id'])) {
    header('Location: login.php');
    exit;
}

// Incluir archivos necesarios
require_once '../includes/jobs-system.php';

// Obtener ID de la sesión de prueba
$sesion_id = isset($_GET['sesion_id']) ? (int)$_GET['sesion_id'] : 0;

// Si no se especificó una sesión, redirigir al panel
if ($sesion_id === 0) {
    header('Location: panel.php');
    exit;
}

// Instanciar gestores necesarios
$candidateManager = new CandidateManager();
$testManager = new TestManager();

// Obtener datos del candidato
$candidato_id = $_SESSION['candidato_id'];
$candidato = $candidateManager->getCandidateById($candidato_id);

// Obtener información de la sesión de prueba
$sesion = $testManager->getSessionById($sesion_id);

// Verificar si la sesión existe y pertenece al candidato
if (!$sesion || $sesion['candidato_id'] != $candidato_id) {
    header('Location: panel.php?error=sesion_no_encontrada');
    exit;
}

// Verificar si la prueba está completada
if ($sesion['estado'] !== 'completada') {
    header('Location: prueba.php?id=' . $sesion['prueba_id']);
    exit;
}

// Obtener información de la prueba
$prueba = $testManager->getTestById($sesion['prueba_id']);

// Obtener resultados de la prueba
$resultados = $testManager->getSessionResults($sesion_id);

// Obtener estadísticas generales
$estadisticas = $testManager->getSessionStats($sesion_id);

// Título de la página
$pageTitle = 'Resultados: ' . $prueba['titulo'];
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
    <link rel="stylesheet" href="css/resultados.css">
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <!-- Navbar -->
    <?php include 'includes/navbar.php'; ?>
    
    <div class="dashboard-container">
        <!-- Sidebar -->
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="dashboard-content">
            <div class="content-header">
                <h1>Resultados: <?php echo htmlspecialchars($prueba['titulo']); ?></h1>
                <div class="header-actions">
                    <a href="panel.php" class="btn-outline">
                        <i class="fas fa-arrow-left"></i> Volver al Panel
                    </a>
                    <a href="#" class="btn-primary" onclick="window.print()">
                        <i class="fas fa-print"></i> Imprimir Resultados
                    </a>
                </div>
            </div>
            
            <div class="results-container">
                <div class="results-overview">
                    <div class="overview-card">
                        <div class="overview-icon">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        <div class="overview-info">
                            <h4>Fecha de Realización</h4>
                            <p><?php echo date('d/m/Y', strtotime($sesion['fecha_fin'])); ?></p>
                        </div>
                    </div>
                    
                    <div class="overview-card">
                        <div class="overview-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="overview-info">
                            <h4>Tiempo Total</h4>
                            <p><?php 
                                $inicio = new DateTime($sesion['fecha_inicio']);
                                $fin = new DateTime($sesion['fecha_fin']);
                                $intervalo = $inicio->diff($fin);
                                echo $intervalo->format('%H:%I:%S');
                            ?></p>
                        </div>
                    </div>
                    
                    <div class="overview-card">
                        <div class="overview-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="overview-info">
                            <h4>Preguntas Respondidas</h4>
                            <p><?php echo $estadisticas['respondidas']; ?> de <?php echo $estadisticas['total']; ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="results-summary">
                    <h2>Resumen de Resultados</h2>
                    <p class="results-description">
                        Esta evaluación muestra tus resultados en diferentes dimensiones evaluadas en la prueba 
                        <strong><?php echo htmlspecialchars($prueba['titulo']); ?></strong>. 
                        Los resultados te ayudan a conocer mejor tus fortalezas y áreas de desarrollo. 
                        Recuerda que no hay respuestas "buenas" o "malas", sino perfiles que pueden ser más 
                        adecuados para diferentes roles y entornos laborales.
                    </p>
                </div>
                
                <!-- Gráfica de resultados -->
                <div class="results-chart-container">
                    <canvas id="resultsChart"></canvas>
                </div>
                
                <!-- Detalles de resultados por dimensión -->
                <div class="dimensions-results">
                    <h2>Detalles por Dimensión</h2>
                    
                    <?php foreach ($resultados as $resultado): ?>
                    <div class="dimension-card">
                        <div class="dimension-header">
                            <h3><?php echo htmlspecialchars($resultado['dimension_nombre']); ?></h3>
                            <div class="dimension-score">
                                <div class="score-bar">
                                    <div class="score-fill" style="width: <?php echo $resultado['valor']; ?>%"></div>
                                </div>
                                <span class="score-value"><?php echo $resultado['valor']; ?>/100</span>
                            </div>
                        </div>
                        
                        <div class="dimension-description">
                            <p><?php echo htmlspecialchars($resultado['dimension_descripcion']); ?></p>
                        </div>
                        
                        <div class="dimension-interpretation">
                            <h4>Tu Resultado: <?php echo htmlspecialchars($resultado['nivel']); ?></h4>
                            <p><?php echo htmlspecialchars($resultado['interpretacion']); ?></p>
                        </div>
                        
                        <?php if (!empty($resultado['recomendacion'])): ?>
                        <div class="dimension-recommendation">
                            <h4>Recomendación</h4>
                            <p><?php echo htmlspecialchars($resultado['recomendacion']); ?></p>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Próximas pruebas -->
                <?php
                $proximasPruebas = $testManager->getPendingTests($candidato_id);
                if (!empty($proximasPruebas)):
                ?>
                <div class="next-steps">
                    <h2>Próximas Evaluaciones</h2>
                    <p>Para completar tu perfil, te recomendamos realizar las siguientes evaluaciones:</p>
                    
                    <div class="pending-tests">
                        <?php foreach ($proximasPruebas as $prueba): ?>
                        <div class="pending-test-card">
                            <div class="test-info">
                                <h3><?php echo htmlspecialchars($prueba['titulo']); ?></h3>
                                <p><?php echo htmlspecialchars($prueba['descripcion']); ?></p>
                                <span class="test-duration">
                                    <i class="fas fa-clock"></i> <?php echo $prueba['tiempo_estimado']; ?> minutos
                                </span>
                            </div>
                            <a href="prueba.php?id=<?php echo $prueba['id']; ?>" class="btn-primary">
                                Iniciar Prueba
                            </a>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Más información y ayuda -->
                <div class="help-info">
                    <h2>¿Necesitas Ayuda?</h2>
                    <p>Si tienes alguna pregunta sobre tus resultados o el proceso de evaluación, no dudes en contactarnos:</p>
                    <div class="contact-options">
                        <div class="contact-option">
                            <i class="fas fa-envelope"></i>
                            <span>rrhh@solfis.com.do</span>
                        </div>
                        <div class="contact-option">
                            <i class="fas fa-phone"></i>
                            <span>+1 (809) 555-1234</span>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <script>
        // Datos para el gráfico
        const dimensions = [
            <?php foreach ($resultados as $resultado): ?>
            '<?php echo addslashes($resultado['dimension_nombre']); ?>',
            <?php endforeach; ?>
        ];
        
        const scores = [
            <?php foreach ($resultados as $resultado): ?>
            <?php echo $resultado['valor']; ?>,
            <?php endforeach; ?>
        ];
        
        const colors = [
            'rgba(54, 162, 235, 0.7)',
            'rgba(255, 99, 132, 0.7)',
            'rgba(75, 192, 192, 0.7)',
            'rgba(255, 159, 64, 0.7)',
            'rgba(153, 102, 255, 0.7)',
            'rgba(255, 205, 86, 0.7)',
            'rgba(201, 203, 207, 0.7)',
            'rgba(255, 99, 132, 0.7)',
        ];
        
        // Crear el gráfico
        const ctx = document.getElementById('resultsChart').getContext('2d');
        const resultsChart = new Chart(ctx, {
            type: 'radar',
            data: {
                labels: dimensions,
                datasets: [{
                    label: 'Tus Resultados',
                    data: scores,
                    backgroundColor: 'rgba(54, 162, 235, 0.2)',
                    borderColor: 'rgb(54, 162, 235)',
                    borderWidth: 2,
                    pointBackgroundColor: 'rgb(54, 162, 235)',
                    pointBorderColor: '#fff',
                    pointHoverBackgroundColor: '#fff',
                    pointHoverBorderColor: 'rgb(54, 162, 235)'
                }]
            },
            options: {
                elements: {
                    line: {
                        tension: 0.1
                    }
                },
                scales: {
                    r: {
                        angleLines: {
                            display: true
                        },
                        suggestedMin: 0,
                        suggestedMax: 100
                    }
                },
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                if (context.parsed.r !== null) {
                                    label += context.parsed.r + '/100';
                                }
                                return label;
                            }
                        }
                    }
                }
            }
        });
        
        // Responsive print styles
        window.onbeforeprint = function() {
                            // Ajustar estilos para impresión
            document.querySelectorAll('.dashboard-container, .dashboard-content').forEach(el => {
                el.style.padding = '0';
                el.style.margin = '0';
                el.style.width = '100%';
            });
            
            // Ocultar elementos innecesarios para impresión
            document.querySelector('.dashboard-sidebar').style.display = 'none';
            document.querySelector('.dashboard-navbar').style.display = 'none';
            document.querySelector('.header-actions').style.display = 'none';
            document.querySelector('.help-info').style.display = 'none';
            document.querySelector('.next-steps').style.display = 'none';
        };
        
        window.onafterprint = function() {
            // Restaurar estilos después de imprimir
            location.reload();
        };
    </script>
</body>
</html>