<?php
session_start();

// Verificar que el usuario esté autenticado como candidato
if (!isset($_SESSION['candidato_id'])) {
    header('Location: login.php');
    exit;
}

// Incluir archivos necesarios
require_once '../includes/jobs-system.php';

// Obtener ID del candidato
$candidato_id = $_SESSION['candidato_id'];

// Instanciar gestores necesarios
$candidateManager = new CandidateManager();
$candidato = $candidateManager->getCandidateById($candidato_id);

// Inicializar TestManager
$testManager = null;
$pruebasPendientes = [];
$pruebasEnProgreso = [];
$pruebasCompletadas = [];

if (file_exists(__DIR__ . '/../includes/TestManager.php')) {
    require_once __DIR__ . '/../includes/TestManager.php';
    if (class_exists('TestManager')) {
        $testManager = new TestManager();
        
        // Obtener todas las pruebas del candidato
        $pruebasPendientes = $testManager->getPendingTests($candidato_id);
        $pruebasEnProgreso = $testManager->getInProgressTests($candidato_id);
        $pruebasCompletadas = $testManager->getCompletedTests($candidato_id);
    }
}

// Si no hay testManager, mostrar un mensaje
$error = null;
if (!$testManager) {
    $error = "El sistema de evaluaciones no está disponible en este momento. Por favor, intente más tarde.";
}

// Calcular progreso general de pruebas
$totalPruebas = count($pruebasPendientes) + count($pruebasEnProgreso) + count($pruebasCompletadas);
$progresoGeneral = $totalPruebas > 0 ? round((count($pruebasCompletadas) / $totalPruebas) * 100) : 0;

// Título de la página
$pageTitle = "Mis Evaluaciones - SolFis Talentos";

// Determinar la pestaña activa
$tab = isset($_GET['tab']) ? $_GET['tab'] : 'pendientes';
if ($tab !== 'pendientes' && $tab !== 'progreso' && $tab !== 'completadas') {
    $tab = 'pendientes';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    
    <!-- CSS -->
    <link rel="stylesheet" href="../assets/css/normalize.css">
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="css/candidato.css">
    <link rel="stylesheet" href="css/pruebas.css">
    
    <!-- Estilos inline para elementos que faltan -->
    <style>
        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }
        
        .empty-state i {
            font-size: 3rem;
            color: var(--gray-400);
            margin-bottom: 1rem;
        }
        
        .empty-state h3 {
            color: var(--gray-700);
            margin-bottom: 0.5rem;
        }
        
        .empty-state p {
            color: var(--gray-600);
            max-width: 400px;
            margin-left: auto;
            margin-right: auto;
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
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="dashboard-content">
            <div class="tests-container">
                <div class="tests-header">
                    <h1>Mis Evaluaciones</h1>
                    <p>Completa las siguientes evaluaciones para aumentar tus posibilidades de ser seleccionado para las vacantes que mejor se ajusten a tu perfil.</p>
                
                    <?php if ($totalPruebas > 0): ?>
                    <div class="progress-overview">
                        <div class="overview-header">
                            <h2><i class="fas fa-chart-line"></i> Progreso General</h2>
                            <span class="progress-percentage"><?php echo $progresoGeneral; ?>% completado</span>
                        </div>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: <?php echo $progresoGeneral; ?>%"></div>
                        </div>
                        <div class="progress-stats">
                            <div class="stat-item">
                                <div class="stat-value"><?php echo count($pruebasCompletadas); ?></div>
                                <div class="stat-label">Completadas</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-value"><?php echo count($pruebasEnProgreso); ?></div>
                                <div class="stat-label">En progreso</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-value"><?php echo count($pruebasPendientes); ?></div>
                                <div class="stat-label">Pendientes</div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                
                <?php if ($error): ?>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    <div><?php echo $error; ?></div>
                </div>
                <?php else: ?>
                
                <div class="tests-tabs">
                    <div class="tab-item <?php echo $tab === 'pendientes' ? 'active' : ''; ?>" data-tab="pendientes">
                        <i class="fas fa-hourglass-half"></i> Pendientes (<?php echo count($pruebasPendientes); ?>)
                    </div>
                    <div class="tab-item <?php echo $tab === 'progreso' ? 'active' : ''; ?>" data-tab="progreso">
                        <i class="fas fa-spinner"></i> En Progreso (<?php echo count($pruebasEnProgreso); ?>)
                    </div>
                    <div class="tab-item <?php echo $tab === 'completadas' ? 'active' : ''; ?>" data-tab="completadas">
                        <i class="fas fa-check-circle"></i> Completadas (<?php echo count($pruebasCompletadas); ?>)
                    </div>
                </div>
                
                <!-- Tab de Pruebas Pendientes -->
                <div class="tab-content <?php echo $tab === 'pendientes' ? 'active' : ''; ?>" id="tab-pendientes">
                    <?php if (empty($pruebasPendientes)): ?>
                    <div class="empty-state">
                        <i class="fas fa-clipboard-check"></i>
                        <h3>No tienes evaluaciones pendientes</h3>
                        <p>Has completado todas tus evaluaciones asignadas. ¡Buen trabajo!</p>
                    </div>
                    <?php else: ?>
                    <?php foreach ($pruebasPendientes as $prueba): ?>
                    <div class="test-card animate-fade-in">
                        <div class="test-card-header">
                            <h2><?php echo isset($prueba['titulo']) ? htmlspecialchars($prueba['titulo']) : 'Evaluación'; ?></h2>
                            <span class="test-status pending">Pendiente</span>
                        </div>
                        <div class="test-card-body">
                            <div class="test-description">
                                <?php echo isset($prueba['descripcion']) && !empty($prueba['descripcion']) ? htmlspecialchars($prueba['descripcion']) : 'Sin descripción disponible.'; ?>
                            </div>
                            <div class="test-meta">
                                <div class="test-meta-item">
                                    <i class="fas fa-clock"></i>
                                    <span>Duración estimada: <?php echo isset($prueba['tiempo_estimado']) ? $prueba['tiempo_estimado'] : '30'; ?> minutos</span>
                                </div>
                                <div class="test-meta-item">
                                    <i class="fas fa-question-circle"></i>
                                    <span>Tipo: <?php echo isset($prueba['nombre']) ? htmlspecialchars($prueba['nombre']) : 'Evaluación'; ?></span>
                                </div>
                            </div>
                            <div class="test-card-actions">
                                <a href="prueba.php?id=<?php echo $prueba['id']; ?>" class="btn btn-primary">
                                    <i class="fas fa-play"></i> Iniciar Evaluación
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <!-- Tab de Pruebas En Progreso -->
                <div class="tab-content <?php echo $tab === 'progreso' ? 'active' : ''; ?>" id="tab-progreso">
                    <?php if (empty($pruebasEnProgreso)): ?>
                    <div class="empty-state">
                        <i class="fas fa-spinner"></i>
                        <h3>No tienes evaluaciones en progreso</h3>
                        <p>Cuando inicies una evaluación y la pauses, aparecerá en esta sección.</p>
                    </div>
                    <?php else: ?>
                    <?php foreach ($pruebasEnProgreso as $prueba): ?>
                    <div class="test-card animate-fade-in">
                        <div class="test-card-header">
                            <h2><?php echo isset($prueba['titulo']) ? htmlspecialchars($prueba['titulo']) : 'Evaluación'; ?></h2>
                            <span class="test-status progress">En Progreso</span>
                        </div>
                        <div class="test-card-body">
                            <div class="test-description">
                                <?php echo isset($prueba['descripcion']) && !empty($prueba['descripcion']) ? htmlspecialchars($prueba['descripcion']) : 'Sin descripción disponible.'; ?>
                            </div>
                            <div class="test-meta">
                                <div class="test-meta-item">
                                    <i class="fas fa-clock"></i>
                                    <span>Iniciada: <?php echo isset($prueba['fecha_inicio']) ? date('d/m/Y H:i', strtotime($prueba['fecha_inicio'])) : 'Fecha desconocida'; ?></span>
                                </div>
                                <div class="test-meta-item">
                                    <i class="fas fa-check"></i>
                                    <span>Respuestas: <?php echo isset($prueba['respuestas_count']) ? $prueba['respuestas_count'] : '0'; ?> de <?php echo isset($prueba['preguntas_count']) ? $prueba['preguntas_count'] : '?'; ?></span>
                                </div>
                            </div>
                            <div class="test-card-actions">
                                <a href="prueba.php?id=<?php echo isset($prueba['prueba_id']) ? $prueba['prueba_id'] : $prueba['id']; ?>" class="btn btn-primary">
                                    <i class="fas fa-sync-alt"></i> Continuar
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <!-- Tab de Pruebas Completadas -->
                <div class="tab-content <?php echo $tab === 'completadas' ? 'active' : ''; ?>" id="tab-completadas">
                    <?php if (empty($pruebasCompletadas)): ?>
                    <div class="empty-state">
                        <i class="fas fa-clipboard-check"></i>
                        <h3>Aún no has completado ninguna evaluación</h3>
                        <p>Cuando completes una evaluación, podrás ver sus resultados aquí.</p>
                    </div>
                    <?php else: ?>
                    <?php foreach ($pruebasCompletadas as $prueba): ?>
                    <div class="test-card animate-fade-in">
                        <div class="test-card-header">
                            <h2><?php echo isset($prueba['prueba_titulo']) ? htmlspecialchars($prueba['prueba_titulo']) : 'Evaluación completada'; ?></h2>
                            <span class="test-status completed">Completada</span>
                        </div>
                        <div class="test-card-body">
                            <?php if (isset($prueba['resultado_global'])): ?>
                            <div class="test-result">
                                <div class="result-score"><?php echo $prueba['resultado_global']; ?>%</div>
                                <div class="result-label">Resultado global</div>
                            </div>
                            <?php endif; ?>
                            
                            <div class="test-meta">
                                <div class="test-meta-item">
                                    <i class="fas fa-calendar-check"></i>
                                    <span>Completada: <?php echo isset($prueba['fecha_fin']) ? date('d/m/Y', strtotime($prueba['fecha_fin'])) : 'Fecha desconocida'; ?></span>
                                </div>
                                <?php if (isset($prueba['fecha_inicio']) && isset($prueba['fecha_fin'])): ?>
                                <div class="test-meta-item">
                                    <i class="fas fa-clock"></i>
                                    <span>Duración: <?php 
                                        $inicio = new DateTime($prueba['fecha_inicio']);
                                        $fin = new DateTime($prueba['fecha_fin']);
                                        $interval = $inicio->diff($fin);
                                        echo $interval->format('%h horas %i minutos');
                                    ?></span>
                                </div>
                                <?php endif; ?>
                            </div>
                            <div class="test-card-actions">
                                <a href="resultado-prueba.php?sesion_id=<?php echo isset($prueba['sesion_id']) ? $prueba['sesion_id'] : $prueba['id']; ?>" class="btn btn-primary">
                                    <i class="fas fa-chart-pie"></i> Ver Resultados
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Funcionalidad de las pestañas
            const tabItems = document.querySelectorAll('.tab-item');
            const tabContents = document.querySelectorAll('.tab-content');
            
            tabItems.forEach(tab => {
                tab.addEventListener('click', function() {
                    const tabId = this.getAttribute('data-tab');
                    
                    // Actualizar URL
                    const url = new URL(window.location);
                    url.searchParams.set('tab', tabId);
                    window.history.pushState({}, '', url);
                    
                    // Activar pestaña y contenido
                    tabItems.forEach(item => item.classList.remove('active'));
                    tabContents.forEach(content => content.classList.remove('active'));
                    
                    this.classList.add('active');
                    document.getElementById('tab-' + tabId).classList.add('active');
                });
            });
        });
    </script>
</body>
</html>