<?php
// Obtener la ruta del archivo actual
$current_page = basename($_SERVER['PHP_SELF']);

// Verificar si existe TestManager y obtener conteos de pruebas
$pruebasPendientes = [];
$pruebasCompletadas = [];
$testManager = null;

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

// Cargar TestManager y obtener pruebas
if (!class_exists('TestManager')) {
    // Primero intentamos la ruta relativa desde este archivo
    $testManagerPath = __DIR__ . '/../../includes/TestManager.php';
    
    if (file_exists($testManagerPath)) {
        require_once $testManagerPath;
        
        if (class_exists('TestManager')) {
            $testManager = new TestManager();
            
            // Solo obtener pruebas si el candidato existe y la clase tiene los métodos
            if (isset($_SESSION['candidato_id']) && 
                method_exists($testManager, 'getPendingTests') && 
                method_exists($testManager, 'getCompletedTests')) {
                    
                $candidato_id = $_SESSION['candidato_id'];
                $pruebasPendientes = $testManager->getPendingTests($candidato_id);
                $pruebasCompletadas = $testManager->getCompletedTests($candidato_id);
                
                // Debug - podemos comentar estas líneas después
                error_log('Pruebas pendientes: ' . count($pruebasPendientes));
                error_log('Pruebas completadas: ' . count($pruebasCompletadas));
            }
        }
    }
}
?>
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
            <a href="pruebas.php" class="sidebar-link <?php echo ($current_page == 'pruebas.php') ? 'active' : ''; ?>">
                <i class="fas fa-clipboard-check"></i>
                <span>Mis Evaluaciones</span>
            </a>
        </li>
        
        <?php if (!empty($pruebasPendientes)): ?>
        <li class="sidebar-item">
            <a href="pruebas-pendientes.php" class="sidebar-link <?php echo ($current_page == 'pruebas-pendientes.php') ? 'active' : ''; ?>">
                <i class="fas fa-hourglass-half"></i>
                <span>Pendientes (<?php echo count($pruebasPendientes); ?>)</span>
            </a>
        </li>
        <?php endif; ?>
        
        <?php if (!empty($pruebasCompletadas)): ?>
        <li class="sidebar-item">
            <a href="resultados.php" class="sidebar-link <?php echo ($current_page == 'resultados.php') ? 'active' : ''; ?>">
                <i class="fas fa-chart-bar"></i>
                <span>Mis Resultados</span>
            </a>
        </li>
        <?php endif; ?>
        
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