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

// Inicializar arrays de pruebas y contadores
$pruebasPendientes = [];
$pruebasEnProgreso = [];
$pruebasCompletadas = [];
$pruebasPendientesCount = 0;
$pruebasEnProgresoCount = 0;
$pruebasCompletadasCount = 0;
$totalPruebas = 0;
$progresoGeneral = 0;

// Intentar cargar el TestManager
$testManager = null;
$error = null;

// Verificar si existe TestManager.php
if (file_exists(__DIR__ . '/../includes/TestManager.php')) {
    require_once __DIR__ . '/../includes/TestManager.php';
    if (class_exists('TestManager')) {
        try {
            $testManager = new TestManager();
            
            // Obtener todas las pruebas del candidato
            $pruebasPendientes = $testManager->getPendingTests($candidato_id);
            $pruebasEnProgreso = $testManager->getInProgressTests($candidato_id);
            $pruebasCompletadas = $testManager->getCompletedTests($candidato_id);
            
            // Asegurar que todos son arrays
            $pruebasPendientes = is_array($pruebasPendientes) ? $pruebasPendientes : [];
            $pruebasEnProgreso = is_array($pruebasEnProgreso) ? $pruebasEnProgreso : [];
            $pruebasCompletadas = is_array($pruebasCompletadas) ? $pruebasCompletadas : [];
            
            // Log para depuración
            error_log("Pruebas pendientes: " . count($pruebasPendientes));
            error_log("Pruebas en progreso: " . count($pruebasEnProgreso));
            error_log("Pruebas completadas: " . count($pruebasCompletadas));
        } catch (Exception $e) {
            error_log("Error al obtener pruebas: " . $e->getMessage());
            $error = "Hubo un problema al cargar las evaluaciones. Por favor, intente más tarde.";
        }
    } else {
        $error = "El sistema de evaluaciones no está disponible en este momento (Clase no encontrada).";
    }
} else {
    $error = "El sistema de evaluaciones no está disponible en este momento (Archivo no encontrado).";
}

// Si no hay TestManager o hubo un error, intentar obtener las pruebas completadas directamente
if (empty($pruebasCompletadas) && isset($candidato_id) && $candidato_id > 0) {
    try {
        $db = Database::getInstance();
        
        // Consulta directa a la base de datos para pruebas completadas
        $sql = "SELECT s.id as sesion_id, s.prueba_id, s.estado, s.fecha_inicio, s.fecha_fin, s.resultado_global,
                       p.titulo as prueba_titulo, p.descripcion as prueba_descripcion,
                       c.nombre as categoria_nombre
                FROM sesiones_prueba s
                JOIN pruebas p ON s.prueba_id = p.id
                LEFT JOIN pruebas_categorias c ON p.categoria_id = c.id
                WHERE s.candidato_id = $candidato_id
                AND s.estado = 'completada'
                ORDER BY s.fecha_fin DESC";
                
        $result = $db->query($sql);
        
        if ($result && $result->num_rows > 0) {
            $directCompletadas = [];
            while ($row = $result->fetch_assoc()) {
                // Asegurar que los campos necesarios estén presentes
                if (!isset($row['sesion_id']) && isset($row['id'])) {
                    $row['sesion_id'] = $row['id'];
                }
                $directCompletadas[] = $row;
            }
            
            if (!empty($directCompletadas)) {
                $pruebasCompletadas = $directCompletadas;
                error_log("Pruebas completadas obtenidas directamente: " . count($pruebasCompletadas));
            }
        }
        
        // Consulta para pruebas en progreso
        if (empty($pruebasEnProgreso)) {
            $sql = "SELECT s.id as sesion_id, s.prueba_id, s.estado, s.fecha_inicio, s.fecha_fin,
                           p.titulo as prueba_titulo, p.descripcion as prueba_descripcion,
                           c.nombre as categoria_nombre
                    FROM sesiones_prueba s
                    JOIN pruebas p ON s.prueba_id = p.id
                    LEFT JOIN pruebas_categorias c ON p.categoria_id = c.id
                    WHERE s.candidato_id = $candidato_id
                    AND s.estado = 'en_progreso'
                    ORDER BY s.fecha_inicio DESC";
                    
            $result = $db->query($sql);
            
            if ($result && $result->num_rows > 0) {
                $directEnProgreso = [];
                while ($row = $result->fetch_assoc()) {
                    $directEnProgreso[] = $row;
                }
                
                if (!empty($directEnProgreso)) {
                    $pruebasEnProgreso = $directEnProgreso;
                    error_log("Pruebas en progreso obtenidas directamente: " . count($pruebasEnProgreso));
                }
            }
        }
    } catch (Exception $e) {
        error_log("Error en consulta directa: " . $e->getMessage());
    }
}

// Calcular conteos y progreso
$pruebasPendientesCount = count($pruebasPendientes);
$pruebasEnProgresoCount = count($pruebasEnProgreso);
$pruebasCompletadasCount = count($pruebasCompletadas);

$totalPruebas = $pruebasPendientesCount + $pruebasEnProgresoCount + $pruebasCompletadasCount;
$progresoGeneral = $totalPruebas > 0 ? round(($pruebasCompletadasCount / $totalPruebas) * 100) : 0;

// Título de la página
$pageTitle = "Mis Evaluaciones - SolFis Talentos";

// Determinar la pestaña activa
$tab = isset($_GET['tab']) ? $_GET['tab'] : 'completadas'; // Por defecto mostrar completadas
if ($tab !== 'pendientes' && $tab !== 'progreso' && $tab !== 'completadas') {
    $tab = 'completadas';
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
        
        
        /* Estilo para enlaces de tab */
        .tab-item {
            cursor: pointer;
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
                                <div class="stat-value"><?php echo $pruebasCompletadasCount; ?></div>
                                <div class="stat-label">Completadas</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-value"><?php echo $pruebasEnProgresoCount; ?></div>
                                <div class="stat-label">En progreso</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-value"><?php echo $pruebasPendientesCount; ?></div>
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
                
                <!-- Botón para depuración -->
                <div class="text-center mt-4">
                    <a href="debug-pruebas.php" class="btn btn-outline-primary">
                        <i class="fas fa-bug"></i> Depurar sistema de pruebas
                    </a>
                </div>
                <?php else: ?>
                
                <div class="tests-tabs">
                    <div class="tab-item <?php echo $tab === 'pendientes' ? 'active' : ''; ?>" data-tab="pendientes">
                        <i class="fas fa-hourglass-half"></i> Pendientes (<?php echo $pruebasPendientesCount; ?>)
                    </div>
                    <div class="tab-item <?php echo $tab === 'progreso' ? 'active' : ''; ?>" data-tab="progreso">
                        <i class="fas fa-spinner"></i> En Progreso (<?php echo $pruebasEnProgresoCount; ?>)
                    </div>
                    <div class="tab-item <?php echo $tab === 'completadas' ? 'active' : ''; ?>" data-tab="completadas">
                        <i class="fas fa-check-circle"></i> Completadas (<?php echo $pruebasCompletadasCount; ?>)
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
                                    <span>Tipo: <?php echo isset($prueba['categoria_nombre']) ? htmlspecialchars($prueba['categoria_nombre']) : 'Evaluación'; ?></span>
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
                            <h2><?php echo isset($prueba['prueba_titulo']) ? htmlspecialchars($prueba['prueba_titulo']) : 'Evaluación'; ?></h2>
                            <span class="test-status progress">En Progreso</span>
                        </div>
                        <div class="test-card-body">
                            <div class="test-description">
                                <?php echo isset($prueba['prueba_descripcion']) && !empty($prueba['prueba_descripcion']) ? htmlspecialchars($prueba['prueba_descripcion']) : 'Sin descripción disponible.'; ?>
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
                            <div class="test-result">
                                <div class="result-score"><?php echo isset($prueba['resultado_global']) ? $prueba['resultado_global'] : 0; ?>%</div>
                                <div class="result-label">Resultado global</div>
                            </div>
                            
                            <div class="test-meta">
                                <div class="test-meta-item">
                                    <i class="fas fa-calendar-check"></i>
                                    <span>Completada: <?php echo isset($prueba['fecha_fin']) ? date('d/m/Y', strtotime($prueba['fecha_fin'])) : date('d/m/Y'); ?></span>
                                </div>
                                <?php if (isset($prueba['fecha_inicio']) && isset($prueba['fecha_fin'])): ?>
                                <div class="test-meta-item">
                                    <i class="fas fa-clock"></i>
                                    <span>Duración: <?php 
                                        try {
                                            $inicio = new DateTime($prueba['fecha_inicio']);
                                            $fin = new DateTime($prueba['fecha_fin']);
                                            $interval = $inicio->diff($fin);
                                            echo $interval->format('%h horas %i minutos');
                                        } catch (Exception $e) {
                                            echo "No disponible";
                                        }
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

    <!-- Código JavaScript para manejar las pestañas -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Funcionalidad de las pestañas
        const tabItems = document.querySelectorAll('.tab-item');
        const tabContents = document.querySelectorAll('.tab-content');
        
        // Log para depuración
        console.log('Tabs encontradas:', tabItems.length);
        console.log('Contenidos de tab encontrados:', tabContents.length);
        
        tabItems.forEach(tab => {
            tab.addEventListener('click', function() {
                const tabId = this.getAttribute('data-tab');
                console.log('Tab clickeada:', tabId);
                
                // Actualizar URL
                const url = new URL(window.location);
                url.searchParams.set('tab', tabId);
                window.history.pushState({}, '', url);
                
                // Activar pestaña y contenido
                tabItems.forEach(item => {
                    item.classList.remove('active');
                    console.log('Removiendo active de', item.getAttribute('data-tab'));
                });
                tabContents.forEach(content => {
                    content.classList.remove('active');
                    console.log('Removiendo active de', content.id);
                });
                
                this.classList.add('active');
                console.log('Agregando active a tab', tabId);
                
                const contentEl = document.getElementById('tab-' + tabId);
                if (contentEl) {
                    contentEl.classList.add('active');
                    console.log('Agregando active a contenido', 'tab-' + tabId);
                } else {
                    console.error('No se encontró el contenido:', 'tab-' + tabId);
                }
            });
        });
        
        // Verificar las pestañas al cargar
        console.log('Pestañas activas al cargar:', document.querySelectorAll('.tab-item.active').length);
        console.log('Contenidos activos al cargar:', document.querySelectorAll('.tab-content.active').length);
    });
    </script>
</body>
</html>