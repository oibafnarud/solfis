<?php
// Archivo: candidato/includes/sidebar-fix.php
// Descripción: Solución para mantener un sidebar consistente en todas las páginas

// Obtener la ruta del archivo actual
$current_page = basename($_SERVER['PHP_SELF']);

// Iniciar sesión si no está iniciada
if (!isset($_SESSION)) {
    session_start();
}

// Verificar si estamos logueados
if (!isset($_SESSION['candidato_id'])) {
    header('Location: login.php');
    exit;
}

// Incluir archivos necesarios si aún no están incluidos
if (!class_exists('CandidateManager')) {
    if (file_exists(__DIR__ . '/../../includes/jobs-system.php')) {
        require_once __DIR__ . '/../../includes/jobs-system.php';
    }
}

// Inicializar contadores
$pruebasPendientesCount = 0;
$pruebasEnProgresoCount = 0;
$pruebasCompletadasCount = 0;

// Inicializar arrays
$pruebasPendientes = [];
$pruebasEnProgreso = [];
$pruebasCompletadas = [];

// Intentar cargar TestManager y obtener conteos de pruebas
try {
    if (file_exists(__DIR__ . '/../../includes/TestManager.php')) {
        require_once __DIR__ . '/../../includes/TestManager.php';
        
        if (class_exists('TestManager')) {
            $testManager = new TestManager();
            $candidato_id = $_SESSION['candidato_id'];
            
            // Verificar si existen los métodos necesarios
            if (method_exists($testManager, 'getPendingTests')) {
                $pruebasPendientes = $testManager->getPendingTests($candidato_id);
                if (!is_array($pruebasPendientes)) $pruebasPendientes = [];
                $pruebasPendientesCount = count($pruebasPendientes);
            }
            
            if (method_exists($testManager, 'getInProgressTests')) {
                $pruebasEnProgreso = $testManager->getInProgressTests($candidato_id);
                if (!is_array($pruebasEnProgreso)) $pruebasEnProgreso = [];
                $pruebasEnProgresoCount = count($pruebasEnProgreso);
            }
            
            if (method_exists($testManager, 'getCompletedTests')) {
                $pruebasCompletadas = $testManager->getCompletedTests($candidato_id);
                if (!is_array($pruebasCompletadas)) $pruebasCompletadas = [];
                $pruebasCompletadasCount = count($pruebasCompletadas);
            }
        }
    }
} catch (Exception $e) {
    // Registrar error pero continuar
    error_log("Error cargando pruebas para sidebar: " . $e->getMessage());
}

// Si no hay datos de pruebas completadas, intentar obtenerlas directamente
if ($pruebasCompletadasCount == 0 && isset($candidato_id) && $candidato_id > 0) {
    try {
        $db = Database::getInstance();
        
        $sql = "SELECT COUNT(DISTINCT id) as total FROM sesiones_prueba 
                WHERE candidato_id = $candidato_id AND estado = 'completada'";
        
        $result = $db->query($sql);
        if ($result && $row = $result->fetch_assoc()) {
            $pruebasCompletadasCount = $row['total'];
        }
    } catch (Exception $e) {
        error_log("Error en consulta directa para pruebas completadas: " . $e->getMessage());
    }
}

// Si no hay datos de pruebas en progreso, intentar obtenerlas directamente
if ($pruebasEnProgresoCount == 0 && isset($candidato_id) && $candidato_id > 0) {
    try {
        $db = Database::getInstance();
        
        $sql = "SELECT COUNT(DISTINCT id) as total FROM sesiones_prueba 
                WHERE candidato_id = $candidato_id AND estado = 'en_progreso'";
        
        $result = $db->query($sql);
        if ($result && $row = $result->fetch_assoc()) {
            $pruebasEnProgresoCount = $row['total'];
        }
    } catch (Exception $e) {
        error_log("Error en consulta directa para pruebas en progreso: " . $e->getMessage());
    }
}
?>

<!-- Estructura del Sidebar -->
<aside class="dashboard-sidebar">
    <ul class="sidebar-menu">
        <li class="sidebar-item">
            <a href="panel.php" class="sidebar-link <?php echo ($current_page == 'panel.php') ? 'active' : ''; ?>">
                <i class="fas fa-tachometer-alt"></i>
                <span>Panel Principal</span>
            </a>
        </li>
        
        <li class="sidebar-item">
            <a href="profile.php" class="sidebar-link <?php echo ($current_page == 'profile.php') ? 'active' : ''; ?>">
                <i class="fas fa-user"></i>
                <span>Mi Perfil</span>
            </a>
        </li>
        
        <li class="sidebar-category">Vacantes</li>
        
        <li class="sidebar-item">
            <a href="vacantes.php" class="sidebar-link <?php echo ($current_page == 'vacantes.php') ? 'active' : ''; ?>">
                <i class="fas fa-search"></i>
                <span>Explorar Vacantes</span>
            </a>
        </li>
        
        <li class="sidebar-item">
            <a href="aplicaciones.php" class="sidebar-link <?php echo ($current_page == 'aplicaciones.php' || $current_page == 'aplicacion.php') ? 'active' : ''; ?>">
                <i class="fas fa-briefcase"></i>
                <span>Mis Aplicaciones</span>
            </a>
        </li>
        
        <li class="sidebar-category">Evaluaciones</li>
        
        <li class="sidebar-item">
            <a href="pruebas.php" class="sidebar-link <?php echo ($current_page == 'pruebas.php' || $current_page == 'prueba.php' || $current_page == 'resultado-prueba.php') ? 'active' : ''; ?>">
                <i class="fas fa-clipboard-check"></i>
                <span>Mis Evaluaciones</span>
            </a>
        </li>
        
        <?php if ($pruebasPendientesCount > 0): ?>
        <li class="sidebar-item">
            <a href="pruebas.php?tab=pendientes" class="sidebar-link <?php echo ($current_page == 'pruebas.php' && isset($_GET['tab']) && $_GET['tab'] == 'pendientes') ? 'active' : ''; ?>">
                <i class="fas fa-hourglass-half"></i>
                <span>Pendientes (<?php echo $pruebasPendientesCount; ?>)</span>
            </a>
        </li>
        <?php endif; ?>
        
        <?php if ($pruebasEnProgresoCount > 0): ?>
        <li class="sidebar-item">
            <a href="pruebas.php?tab=progreso" class="sidebar-link <?php echo ($current_page == 'pruebas.php' && isset($_GET['tab']) && $_GET['tab'] == 'progreso') ? 'active' : ''; ?>">
                <i class="fas fa-spinner"></i>
                <span>En Progreso (<?php echo $pruebasEnProgresoCount; ?>)</span>
            </a>
        </li>
        <?php endif; ?>
        
        <?php if ($pruebasCompletadasCount > 0): ?>
        <li class="sidebar-item">
            <a href="pruebas.php?tab=completadas" class="sidebar-link <?php echo ($current_page == 'pruebas.php' && isset($_GET['tab']) && $_GET['tab'] == 'completadas') ? 'active' : ''; ?>">
                <i class="fas fa-chart-bar"></i>
                <span>Resultados (<?php echo $pruebasCompletadasCount; ?>)</span>
            </a>
        </li>
        <?php endif; ?>
        
        <li class="sidebar-category">Servicios</li>
        
        <li class="sidebar-item">
            <a href="premium.php" class="sidebar-link <?php echo ($current_page == 'premium.php') ? 'active' : ''; ?>">
                <i class="fas fa-star"></i>
                <span>Servicios Premium</span>
                <span class="badge-new">Nuevo</span>
            </a>
        </li>
        
        <li class="sidebar-category">Mi Cuenta</li>
        
        <li class="sidebar-item">
            <a href="configuracion.php" class="sidebar-link <?php echo ($current_page == 'configuracion.php') ? 'active' : ''; ?>">
                <i class="fas fa-cog"></i>
                <span>Configuración</span>
            </a>
        </li>
        
        <li class="sidebar-item">
            <a href="logout.php" class="sidebar-link">
                <i class="fas fa-sign-out-alt"></i>
                <span>Cerrar Sesión</span>
            </a>
        </li>
    </ul>
</aside>

<style>
/* Estilo para la insignia "Nuevo" */
.badge-new {
    display: inline-block;
    font-size: 0.7rem;
    background-color: #ff9900;
    color: white;
    padding: 2px 6px;
    border-radius: 10px;
    margin-left: 8px;
    font-weight: 600;
}
</style>