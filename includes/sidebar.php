<?php
// Obtener la ruta del archivo actual
$current_page = basename($_SERVER['PHP_SELF']);

// Verificar si existe TestManager y obtener conteos de pruebas
$pruebasPendientes = [];
$pruebasCompletadas = [];
$testManager = null;

if (file_exists(__DIR__ . '/../../includes/TestManager.php') && class_exists('TestManager')) {
    $testManager = new TestManager();
    $pruebasPendientes = $testManager->getPendingTests($_SESSION['candidato_id'] ?? 0);
    $pruebasCompletadas = $testManager->getCompletedTests($_SESSION['candidato_id'] ?? 0);
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
        
        <li class="sidebar-category">Evaluaciones</li>
        
        <li class="sidebar-item">
            <a href="pruebas.php" class="sidebar-link <?php echo ($current_page == 'pruebas.php') ? 'active' : ''; ?>">
                <i class="fas fa-clipboard-check"></i>
                <span>Mis Evaluaciones</span>
            </a>
        </li>
        
        <?php if ($testManager && count($pruebasPendientes) > 0): ?>
        <li class="sidebar-item">
            <a href="pruebas-pendientes.php" class="sidebar-link <?php echo ($current_page == 'pruebas-pendientes.php') ? 'active' : ''; ?>">
                <i class="fas fa-hourglass-half"></i>
                <span>Pendientes (<?php echo count($pruebasPendientes); ?>)</span>
            </a>
        </li>
        <?php endif; ?>
        
        <?php if ($testManager && count($pruebasCompletadas) > 0): ?>
        <li class="sidebar-item">
            <a href="resultados.php" class="sidebar-link <?php echo ($current_page == 'resultados.php') ? 'active' : ''; ?>">
                <i class="fas fa-chart-bar"></i>
                <span>Mis Resultados</span>
            </a>
        </li>
        <?php endif; ?>
        
        <li class="sidebar-category">Empleo</li>
        
        <li class="sidebar-item">
            <a href="aplicaciones.php" class="sidebar-link <?php echo ($current_page == 'aplicaciones.php') ? 'active' : ''; ?>">
                <i class="fas fa-briefcase"></i>
                <span>Mis Aplicaciones</span>
            </a>
        </li>
        
        <li class="sidebar-item">
            <a href="../vacantes/index.php" class="sidebar-link">
                <i class="fas fa-search"></i>
                <span>Buscar Vacantes</span>
            </a>
        </li>
        
        <li class="sidebar-category">Cuenta</li>
        
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

<!-- CSS básico para sidebar si no está incluido en el archivo principal -->
<style>
.dashboard-sidebar {
    width: 250px;
    background-color: white;
    box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
    padding: 20px 0;
    position: fixed;
    height: calc(100vh - 60px);
    overflow-y: auto;
}

.sidebar-menu {
    list-style: none;
    padding: 0;
    margin: 0;
}

.sidebar-item {
    margin-bottom: 5px;
}

.sidebar-link {
    display: flex;
    align-items: center;
    padding: 12px 20px;
    color: #333;
    text-decoration: none;
    transition: all 0.3s;
    border-left: 3px solid transparent;
}

.sidebar-link i {
    margin-right: 10px;
    width: 20px;
    text-align: center;
}

.sidebar-link:hover {
    background-color: #f5f5f5;
    border-left-color: #0088cc;
}

.sidebar-link.active {
    background-color: #f5f5f5;
    border-left-color: #003366;
    color: #003366;
    font-weight: 500;
}

.sidebar-category {
    font-size: 12px;
    text-transform: uppercase;
    color: #666;
    padding: 15px 20px 5px;
    letter-spacing: 1px;
}

@media (max-width: 992px) {
    .dashboard-sidebar {
        width: 70px;
        overflow: visible;
    }
    
    .sidebar-link span {
        display: none;
    }
    
    .sidebar-link i {
        margin-right: 0;
        font-size: 20px;
    }
    
    .sidebar-category {
        display: none;
    }
}

@media (max-width: 768px) {
    .dashboard-sidebar {
        display: none;
    }
}
</style>