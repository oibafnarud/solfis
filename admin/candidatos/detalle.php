<?php
/**
 * Panel de Administración para SolFis
 * admin/candidatos/detalle.php - Ver detalles de un candidato
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
$applicationManager = new ApplicationManager();

// Obtener datos del candidato
$candidato = $candidateManager->getCandidateById($candidato_id);

if (!$candidato) {
    $_SESSION['error'] = "Candidato no encontrado";
    header('Location: index.php');
    exit;
}

// Obtener aplicaciones del candidato
$aplicaciones = $applicationManager->getApplicationsByCandidateId($candidato_id);

// Verificar si existe el TestManager para obtener resultados de pruebas
$testManager = null;
$hasTestManager = false;
$pruebasCompletadas = [];
$evaluationResults = [];
$perfilPsicometrico = [];
$promedioResultados = 0;

if (file_exists('../../includes/TestManager.php')) {
    require_once '../../includes/TestManager.php';
    if (class_exists('TestManager')) {
        $testManager = new TestManager();
        $hasTestManager = true;
        
        // Obtener pruebas completadas por el candidato
        try {
            $pruebasCompletadas = $testManager->getCompletedTests($candidato_id);
            
            // Calcular promedio de resultados y determinar perfil psicométrico
            if (!empty($pruebasCompletadas)) {
                $totalResultados = 0;
                $countResultados = 0;
                
                foreach ($pruebasCompletadas as $prueba) {
                    if (isset($prueba['resultado_global']) && is_numeric($prueba['resultado_global'])) {
                        $totalResultados += $prueba['resultado_global'];
                        $countResultados++;
                    }
                }
                
                if ($countResultados > 0) {
                    $promedioResultados = round($totalResultados / $countResultados);
                    
                    // Determinar perfil psicométrico
                    if ($promedioResultados >= 90) {
                        $perfilPsicometrico = [
                            'tipo' => 'Sobresaliente',
                            'descripcion' => 'Candidato con habilidades y competencias excepcionales en todas las áreas evaluadas.',
                            'fortalezas' => ['Capacidad analítica superior', 'Excelente comunicación', 'Toma de decisiones efectiva'],
                            'recomendaciones' => ['Posiciones de liderazgo', 'Roles con alta responsabilidad', 'Proyectos estratégicos'],
                            'class' => 'success'
                        ];
                    } elseif ($promedioResultados >= 75) {
                        $perfilPsicometrico = [
                            'tipo' => 'Avanzado',
                            'descripcion' => 'Candidato con alto nivel de competencias y habilidades bien desarrolladas.',
                            'fortalezas' => ['Buena capacidad analítica', 'Comunicación efectiva', 'Habilidades de resolución de problemas'],
                            'recomendaciones' => ['Posiciones técnicas avanzadas', 'Roles con responsabilidad moderada', 'Oportunidades de crecimiento'],
                            'class' => 'primary'
                        ];
                    } elseif ($promedioResultados >= 60) {
                        $perfilPsicometrico = [
                            'tipo' => 'Competente',
                            'descripcion' => 'Candidato con buen nivel de habilidades, con áreas destacadas y otras por desarrollar.',
                            'fortalezas' => ['Habilidades técnicas adecuadas', 'Trabajo en equipo', 'Capacidad de aprendizaje'],
                            'recomendaciones' => ['Posiciones técnicas intermedias', 'Entrenamiento específico en áreas más débiles', 'Mentoría'],
                            'class' => 'info'
                        ];
                    } elseif ($promedioResultados >= 40) {
                        $perfilPsicometrico = [
                            'tipo' => 'En desarrollo',
                            'descripcion' => 'Candidato con potencial, pero con áreas específicas que requieren desarrollo.',
                            'fortalezas' => ['Motivación para aprender', 'Adaptabilidad', 'Actitud positiva'],
                            'recomendaciones' => ['Posiciones juniors', 'Programas de entrenamiento', 'Seguimiento cercano'],
                            'class' => 'warning'
                        ];
                    } else {
                        $perfilPsicometrico = [
                            'tipo' => 'Inicial',
                            'descripcion' => 'Candidato en etapas iniciales de desarrollo de las habilidades evaluadas.',
                            'fortalezas' => ['Potencial de crecimiento', 'Disposición al aprendizaje'],
                            'recomendaciones' => ['Posiciones iniciales', 'Entrenamiento intensivo', 'Evaluación periódica de progreso'],
                            'class' => 'danger'
                        ];
                    }
                }
                
                // Obtener resultados por dimensiones (habilidades) si existen
                $db = Database::getInstance();
                
                $dimensionsQuery = "SELECT d.nombre, AVG(rd.valor) as promedio, 
                                    CASE 
                                        WHEN AVG(rd.valor) >= 90 THEN 'Alto' 
                                        WHEN AVG(rd.valor) >= 60 THEN 'Medio' 
                                        ELSE 'Bajo' 
                                    END as nivel
                                    FROM resultados_dimensiones rd
                                    JOIN dimensiones d ON rd.dimension_id = d.id
                                    JOIN sesiones_prueba s ON rd.sesion_id = s.id
                                    WHERE s.candidato_id = $candidato_id AND s.estado = 'completada'
                                    GROUP BY d.id
                                    ORDER BY promedio DESC";
                
                $result = $db->query($dimensionsQuery);
                if ($result && $result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $evaluationResults[] = $row;
                    }
                }
            }
        } catch (Exception $e) {
            // Si hay error, registrarlo pero continuar
            error_log("Error al obtener resultados de pruebas: " . $e->getMessage());
        }
    }
}

// Título de la página
$pageTitle = 'Detalle de Candidato - Panel de Administración';
?>

<?php include '../includes/header.php'; ?>

<div class="admin-main">
    <div class="container-fluid">
        <div class="row">
            <?php include '../includes/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Perfil del Candidato</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="index.php" class="btn btn-sm btn-outline-secondary me-2">
                            <i class="fas fa-arrow-left"></i> Volver
                        </a>
                        <a href="editar.php?id=<?php echo $candidato_id; ?>" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-edit"></i> Editar
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
                
                <!-- Información del candidato -->
                <div class="row mb-4">
                    <!-- Datos personales -->
                    <div class="col-md-4">
                        <div class="card profile-card mb-4">
                            <div class="card-body text-center">
                                <div class="profile-image mb-3">
                                    <?php if (!empty($candidato['foto_path'])): ?>
                                    <img src="<?php echo '../../uploads/profile_photos/' . $candidato['foto_path']; ?>" alt="Foto de perfil" class="rounded-circle profile-pic">
                                    <?php else: ?>
                                    <div class="profile-placeholder">
                                        <i class="fas fa-user"></i>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                
                                <h3 class="candidate-name"><?php echo $candidato['nombre'] . ' ' . $candidato['apellido']; ?></h3>
                                
                                <?php if (!empty($perfilPsicometrico)): ?>
                                <div class="badge bg-<?php echo $perfilPsicometrico['class']; ?> profile-badge mb-2">
                                    Perfil: <?php echo $perfilPsicometrico['tipo']; ?>
                                </div>
                                <?php endif; ?>
                                
                                <p class="text-muted mb-1">
                                    <?php
                                    $experiencia = '';
                                    switch ($candidato['experiencia_general']) {
                                        case 'sin-experiencia':
                                            $experiencia = 'Sin experiencia';
                                            break;
                                        case 'menos-1':
                                            $experiencia = 'Menos de 1 año';
                                            break;
                                        case '1-3':
                                            $experiencia = '1-3 años';
                                            break;
                                        case '3-5':
                                            $experiencia = '3-5 años';
                                            break;
                                        case '5-10':
                                            $experiencia = '5-10 años';
                                            break;
                                        case 'mas-10':
                                            $experiencia = 'Más de 10 años';
                                            break;
                                        default:
                                            $experiencia = 'No especificada';
                                    }
                                    echo $experiencia;
                                    ?>
                                </p>
                                
                                <p class="candidate-location mb-3">
                                    <i class="fas fa-map-marker-alt"></i> <?php echo $candidato['ubicacion'] ?: 'Ubicación no especificada'; ?>
                                </p>
                                
                                <div class="candidate-contact-info">
                                    <div class="contact-item mb-2">
                                        <i class="fas fa-envelope"></i> <a href="mailto:<?php echo $candidato['email']; ?>"><?php echo $candidato['email']; ?></a>
                                    </div>
                                    <div class="contact-item mb-2">
                                        <i class="fas fa-phone"></i> <?php echo $candidato['telefono'] ?: 'No disponible'; ?>
                                    </div>
                                    <?php if (!empty($candidato['linkedin'])): ?>
                                    <div class="contact-item mb-2">
                                        <i class="fab fa-linkedin"></i> <a href="<?php echo $candidato['linkedin']; ?>" target="_blank">Perfil LinkedIn</a>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                
                                <hr>
                                
                                <div class="candidate-actions d-grid gap-2">
                                    <?php if (!empty($candidato['cv_path'])): ?>
                                    <a href="<?php echo '../../uploads/resumes/' . $candidato['cv_path']; ?>" class="btn btn-outline-primary" target="_blank">
                                        <i class="fas fa-file-pdf"></i> Ver CV
                                    </a>
                                    <?php endif; ?>
                                    
                                    <?php if ($hasTestManager): ?>
                                    <a href="../pruebas/asignar.php?candidato_id=<?php echo $candidato_id; ?>" class="btn btn-outline-success">
                                        <i class="fas fa-clipboard-check"></i> Asignar Evaluación
                                    </a>
                                    <?php endif; ?>
                                    
                                    <a href="mailto:<?php echo $candidato['email']; ?>" class="btn btn-outline-info">
                                        <i class="fas fa-envelope"></i> Contactar
                                    </a>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Detalles de registro -->
                        <div class="card">
                            <div class="card-header bg-light">
                                <h5 class="card-title mb-0"><i class="fas fa-info-circle"></i> Información adicional</h5>
                            </div>
                            <div class="card-body">
                                <ul class="list-group list-group-flush">
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <span>Fecha de registro</span>
                                        <span class="text-muted"><?php echo date('d/m/Y', strtotime($candidato['created_at'])); ?></span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <span>Último acceso</span>
                                        <span class="text-muted"><?php echo !empty($candidato['last_login']) ? date('d/m/Y H:i', strtotime($candidato['last_login'])) : 'Nunca'; ?></span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <span>Aplicaciones</span>
                                        <span class="badge bg-primary rounded-pill"><?php echo count($aplicaciones); ?></span>
                                    </li>
                                    <?php if ($hasTestManager): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <span>Evaluaciones completadas</span>
                                        <span class="badge bg-success rounded-pill"><?php echo count($pruebasCompletadas); ?></span>
                                    </li>
                                    <?php endif; ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <span>Estado de la cuenta</span>
                                        <span class="badge <?php echo $candidato['activo'] ? 'bg-success' : 'bg-danger'; ?>">
                                            <?php echo $candidato['activo'] ? 'Activo' : 'Inactivo'; ?>
                                        </span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Información profesional y resultados -->
                    <div class="col-md-8">
                        <div class="row">
                            <!-- Info profesional -->
                            <div class="col-12">
                                <div class="card mb-4">
                                    <div class="card-header bg-light">
                                        <h5 class="card-title mb-0"><i class="fas fa-user-tie"></i> Perfil Profesional</h5>
                                    </div>
                                    <div class="card-body">
                                        <?php if (!empty($candidato['resumen_profesional'])): ?>
                                        <div class="mb-4">
                                            <h6>Resumen</h6>
                                            <p><?php echo nl2br(htmlspecialchars($candidato['resumen_profesional'])); ?></p>
                                        </div>
                                        <?php endif; ?>
                                        
                                        <div class="row mb-4">
                                            <div class="col-md-6">
                                                <h6>Información Académica</h6>
                                                <table class="table table-sm">
                                                    <tr>
                                                        <th>Nivel educativo:</th>
                                                        <td>
                                                            <?php
                                                            $nivel = '';
                                                            switch ($candidato['nivel_educativo']) {
                                                                case 'bachiller':
                                                                    $nivel = 'Bachiller';
                                                                    break;
                                                                case 'tecnico':
                                                                    $nivel = 'Técnico';
                                                                    break;
                                                                case 'grado':
                                                                    $nivel = 'Grado Universitario';
                                                                    break;
                                                                case 'postgrado':
                                                                    $nivel = 'Postgrado';
                                                                    break;
                                                                case 'maestria':
                                                                    $nivel = 'Maestría';
                                                                    break;
                                                                case 'doctorado':
                                                                    $nivel = 'Doctorado';
                                                                    break;
                                                                default:
                                                                    $nivel = 'No especificado';
                                                            }
                                                            echo $nivel;
                                                            ?>
                                                        </td>
                                                    </tr>
                                                </table>
                                            </div>
                                            <div class="col-md-6">
                                                <h6>Preferencias Laborales</h6>
                                                <table class="table table-sm">
                                                    <tr>
                                                        <th>Modalidad:</th>
                                                        <td>
                                                            <?php
                                                            $modalidad = '';
                                                            switch ($candidato['modalidad_preferida']) {
                                                                case 'presencial':
                                                                    $modalidad = 'Presencial';
                                                                    break;
                                                                case 'remoto':
                                                                    $modalidad = 'Remoto';
                                                                    break;
                                                                case 'hibrido':
                                                                    $modalidad = 'Híbrido';
                                                                    break;
                                                                default:
                                                                    $modalidad = 'No especificada';
                                                            }
                                                            echo $modalidad;
                                                            ?>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <th>Contrato:</th>
                                                        <td>
                                                            <?php
                                                            $contrato = '';
                                                            switch ($candidato['tipo_contrato_preferido']) {
                                                                case 'tiempo_completo':
                                                                    $contrato = 'Tiempo Completo';
                                                                    break;
                                                                case 'tiempo_parcial':
                                                                    $contrato = 'Tiempo Parcial';
                                                                    break;
                                                                case 'proyecto':
                                                                    $contrato = 'Por Proyecto';
                                                                    break;
                                                                case 'temporal':
                                                                    $contrato = 'Temporal';
                                                                    break;
                                                                default:
                                                                    $contrato = 'No especificado';
                                                            }
                                                            echo $contrato;
                                                            ?>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <th>Disponibilidad:</th>
                                                        <td>
                                                            <?php
                                                            $disponibilidad = '';
                                                            switch ($candidato['disponibilidad']) {
                                                                case 'inmediata':
                                                                    $disponibilidad = 'Inmediata';
                                                                    break;
                                                                case '2-semanas':
                                                                    $disponibilidad = '2 semanas';
                                                                    break;
                                                                case '1-mes':
                                                                    $disponibilidad = '1 mes';
                                                                    break;
                                                                case 'mas-1-mes':
                                                                    $disponibilidad = 'Más de 1 mes';
                                                                    break;
                                                                default:
                                                                    $disponibilidad = 'No especificada';
                                                            }
                                                            echo $disponibilidad;
                                                            ?>
                                                        </td>
                                                    </tr>
                                                </table>
                                            </div>
                                        </div>
                                        
                                        <?php if (!empty($candidato['habilidades_destacadas'])): ?>
                                        <div class="mb-4">
                                            <h6>Habilidades Destacadas</h6>
                                            <div class="skills-container">
                                                <?php
                                                $habilidades = explode(',', $candidato['habilidades_destacadas']);
                                                foreach ($habilidades as $habilidad):
                                                ?>
                                                <span class="skill-badge"><?php echo trim($habilidad); ?></span>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                        <?php endif; ?>
                                        
                                        <?php if (!empty($candidato['areas_interes'])): ?>
                                        <div>
                                            <h6>Áreas de Interés</h6>
                                            <div class="areas-container">
                                                <?php
                                                $areas_ids = explode(',', $candidato['areas_interes']);
                                                $db = Database::getInstance();
                                                
                                                // Consultar nombres de categorías
                                                if (!empty($areas_ids)) {
                                                    $areas_str = implode(',', $areas_ids);
                                                    $sql = "SELECT id, nombre FROM categorias_vacantes WHERE id IN ($areas_str)";
                                                    $result = $db->query($sql);
                                                    
                                                    if ($result && $result->num_rows > 0) {
                                                        while ($row = $result->fetch_assoc()) {
                                                            echo '<span class="area-badge">' . $row['nombre'] . '</span>';
                                                        }
                                                    }
                                                }
                                                ?>
                                            </div>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Evaluación psicométrica si hay resultados -->
                            <?php if ($hasTestManager && !empty($perfilPsicometrico)): ?>
                            <div class="col-12">
                                <div class="card mb-4">
                                    <div class="card-header bg-<?php echo $perfilPsicometrico['class']; ?> text-white">
                                        <h5 class="card-title mb-0"><i class="fas fa-chart-pie"></i> Evaluación Psicométrica</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="row mb-4">
                                            <div class="col-md-4 text-center">
                                                <div class="score-circle">
                                                    <span class="score-value"><?php echo $promedioResultados; ?>%</span>
                                                </div>
                                                <h6 class="mt-3">Perfil: <?php echo $perfilPsicometrico['tipo']; ?></h6>
                                                <p class="small"><?php echo $perfilPsicometrico['descripcion']; ?></p>
                                            </div>
                                            <div class="col-md-8">
                                                <?php if (!empty($evaluationResults)): ?>
                                                <h6>Resultados por Dimensiones</h6>
                                                <div class="table-responsive">
                                                    <table class="table table-sm">
                                                        <thead>
                                                            <tr>
                                                                <th>Dimensión</th>
                                                                <th>Nivel</th>
                                                                <th>Puntuación</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php foreach ($evaluationResults as $dimension): ?>
                                                            <tr>
                                                                <td><?php echo $dimension['nombre']; ?></td>
                                                                <td>
                                                                    <?php 
                                                                    $levelClass = '';
                                                                    switch($dimension['nivel']) {
                                                                        case 'Alto':
                                                                            $levelClass = 'bg-success';
                                                                            break;
                                                                        case 'Medio':
                                                                            $levelClass = 'bg-info';
                                                                            break;
                                                                        default:
                                                                            $levelClass = 'bg-warning';
                                                                    }
                                                                    ?>
                                                                    <span class="badge <?php echo $levelClass; ?>">
                                                                        <?php echo $dimension['nivel']; ?>
                                                                    </span>
                                                                </td>
                                                                <td>
                                                                    <div class="progress">
                                                                        <div class="progress-bar bg-<?php echo $levelClass; ?>" 
                                                                            role="progressbar" 
                                                                            style="width: <?php echo round($dimension['promedio']); ?>%" 
                                                                            aria-valuenow="<?php echo round($dimension['promedio']); ?>" 
                                                                            aria-valuemin="0" 
                                                                            aria-valuemax="100">
                                                                            <?php echo round($dimension['promedio']); ?>%
                                                                        </div>
                                                                    </div>
                                                                </td>
                                                            </tr>
                                                            <?php endforeach; ?>
                                                        </tbody>
                                                    </table>
                                                </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        
                                        <div class="row">
                                            <div class="col-md-6">
                                                <h6>Fortalezas</h6>
                                                <ul class="small">
                                                    <?php foreach ($perfilPsicometrico['fortalezas'] as $fortaleza): ?>
                                                    <li><?php echo $fortaleza; ?></li>
                                                    <?php endforeach; ?>
                                                </ul>
                                            </div>
                                            <div class="col-md-6">
                                                <h6>Recomendaciones</h6>
                                                <ul class="small">
                                                    <?php foreach ($perfilPsicometrico['recomendaciones'] as $recomendacion): ?>
                                                    <li><?php echo $recomendacion; ?></li>
                                                    <?php endforeach; ?>
                                                </ul>
                                            </div>
                                        </div>
                                        
                                        <!-- Botón para ver detalles de evaluaciones -->
                                        <div class="text-center mt-3">
                                            <a href="../pruebas/resultados.php?candidato_id=<?php echo $candidato_id; ?>" class="btn btn-outline-primary">
                                                <i class="fas fa-search"></i> Ver detalle de evaluaciones
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <!-- Aplicaciones del candidato -->
                            <div class="col-12">
                                <div class="card mb-4">
                                    <div class="card-header bg-light">
                                        <h5 class="card-title mb-0"><i class="fas fa-briefcase"></i> Aplicaciones a Vacantes (<?php echo count($aplicaciones); ?>)</h5>
                                    </div>
                                    <div class="card-body">
                                        <?php if (empty($aplicaciones)): ?>
                                        <div class="alert alert-info">
                                            Este candidato aún no ha aplicado a ninguna vacante.
                                        </div>
                                        <?php else: ?>
                                        <div class="table-responsive">
                                            <table class="table table-hover">
                                                <thead>
                                                    <tr>
                                                        <th>Vacante</th>
                                                        <th>Fecha</th>
                                                        <th>Estado</th>
                                                        <th>Acciones</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($aplicaciones as $aplicacion): 
                                                        // Determinar clases para el estado
                                                        $statusClass = 'bg-secondary';
                                                        $statusText = ucfirst($aplicacion['estado']);
                                                        
                                                        switch($aplicacion['estado']) {
                                                            case 'recibida':
                                                                $statusClass = 'bg-info';
                                                                $statusText = 'Recibida';
                                                                break;
                                                            case 'revision':
                                                                $statusClass = 'bg-primary';
                                                                $statusText = 'En revisión';
                                                                break;
                                                            case 'entrevista':
                                                                $statusClass = 'bg-warning';
                                                                $statusText = 'Entrevista';
                                                                break;
                                                            case 'seleccionado':
                                                                $statusClass = 'bg-success';
                                                                $statusText = 'Seleccionado';
                                                                break;
                                                            case 'rechazado':
                                                                $statusClass = 'bg-danger';
                                                                $statusText = 'Rechazado';
                                                                break;
                                                        }
                                                    ?>
                                                    <tr>
                                                        <td>
                                                            <a href="../vacantes/vacante-editar.php?id=<?php echo $aplicacion['vacante_id']; ?>" class="fw-bold text-decoration-none">
                                                                <?php echo $aplicacion['vacante_titulo']; ?>
                                                            </a>
                                                        </td>
                                                        <td><?php echo date('d/m/Y', strtotime($aplicacion['fecha_aplicacion'])); ?></td>
                                                        <td><span class="badge <?php echo $statusClass; ?>"><?php echo $statusText; ?></span></td>
                                                        <td>
                                                            <a href="../aplicaciones/detalle.php?id=<?php echo $aplicacion['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                                <i class="fas fa-eye"></i> Ver
                                                            </a>
                                                        </td>
                                                    </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Historial de evaluaciones si existen -->
                            <?php if ($hasTestManager && !empty($pruebasCompletadas)): ?>
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-header bg-light">
                                        <h5 class="card-title mb-0"><i class="fas fa-clipboard-check"></i> Historial de Evaluaciones</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-hover">
                                                <thead>
                                                    <tr>
                                                        <th>Evaluación</th>
                                                        <th>Fecha</th>
                                                        <th>Resultado</th>
                                                        <th>Acciones</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($pruebasCompletadas as $prueba): 
                                                        // Determinar clase de resultado
                                                        $resultClass = 'bg-secondary';
                                                        
                                                        if ($prueba['resultado_global'] >= 90) {
                                                            $resultClass = 'bg-success';
                                                        } elseif ($prueba['resultado_global'] >= 75) {
                                                            $resultClass = 'bg-primary';
                                                        } elseif ($prueba['resultado_global'] >= 60) {
                                                            $resultClass = 'bg-info';
                                                        } elseif ($prueba['resultado_global'] >= 40) {
                                                            $resultClass = 'bg-warning';
                                                        } else {
                                                            $resultClass = 'bg-danger';
                                                        }
                                                    ?>
                                                    <tr>
                                                        <td>
                                                            <?php echo htmlspecialchars($prueba['prueba_titulo']); ?>
                                                        </td>
                                                        <td><?php echo date('d/m/Y', strtotime($prueba['fecha_fin'])); ?></td>
                                                        <td><span class="badge <?php echo $resultClass; ?>"><?php echo $prueba['resultado_global']; ?>%</span></td>
                                                        <td>
                                                            <a href="../pruebas/resultados.php?session_id=<?php echo $prueba['sesion_id']; ?>" class="btn btn-sm btn-outline-primary">
                                                                <i class="fas fa-chart-bar"></i> Ver Resultados
                                                            </a>
                                                        </td>
                                                    </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
</div>

<!-- Agregar el archivo CSS para el admin -->
<link rel="stylesheet" href="../css/admin.css">

<style>
/* Estilos específicos para la página de detalle del candidato */
.profile-card {
    border-top: 4px solid #0088cc;
}

.profile-image {
    margin-top: 10px;
}

.profile-pic {
    width: 150px;
    height: 150px;
    object-fit: cover;
}

.profile-placeholder {
    width: 150px;
    height: 150px;
    background-color: #f8f9fa;
    color: #adb5bd;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    font-size: 4rem;
    margin: 0 auto;
}

.candidate-name {
    font-size: 1.6rem;
    font-weight: 600;
    margin-bottom: 0.25rem;
}

.candidate-location {
    color: #6c757d;
}

.profile-badge {
    padding: 5px 10px;
    border-radius: 20px;
    font-weight: 600;
}

.contact-item i {
    width: 20px;
    text-align: center;
    margin-right: 5px;
}

.skills-container, .areas-container {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    margin-top: 0.5rem;
}

.skill-badge, .area-badge {
    display: inline-block;
    padding: 0.35em 0.65em;
    font-size: 0.85em;
    font-weight: 600;
    line-height: 1;
    text-align: center;
    white-space: nowrap;
    vertical-align: baseline;
    border-radius: 50rem;
}

.skill-badge {
    background-color: #e2e8f0;
    color: #4a5568;
}

.area-badge {
    background-color: #bee3f8;
    color: #2b6cb0;
}

.score-circle {
    width: 150px;
    height: 150px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto;
    border: 10px solid;
    border-color: #0088cc;
    position: relative;
}

.score-value {
    font-size: 2.5rem;
    font-weight: 700;
    color: #0088cc;
}

.progress {
    height: 15px;
}

.progress-bar {
    font-size: 0.75rem;
    font-weight: 600;
}
</style>

<?php include '../includes/footer.php'; ?>