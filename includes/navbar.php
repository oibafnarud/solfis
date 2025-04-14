<?php
// Obtener datos del candidato si no están disponibles
if (!isset($candidato) && isset($_SESSION['candidato_id'])) {
    $candidateManager = new CandidateManager();
    $candidato = $candidateManager->getCandidateById($_SESSION['candidato_id']);
}
?>
<header class="dashboard-navbar">
    <div class="navbar-container">
        <a href="panel.php" class="navbar-brand">
            <img src="../assets/img/logo.png" alt="SolFis Logo">
        </a>
        
        <div class="navbar-nav">
            <div class="nav-item">
                <a href="../vacantes/index.php" class="nav-link">
                    <i class="fas fa-briefcase"></i> Vacantes
                </a>
            </div>
            
            <div class="nav-item">
                <a href="#" class="nav-link">
                    <i class="fas fa-bell"></i>
                </a>
            </div>
            
            <div class="nav-item dropdown">
                <div class="dropdown-toggle" id="userDropdown">
                    <?php if (isset($candidato) && !empty($candidato['foto_path'])): ?>
                    <img src="../uploads/profile_photos/<?php echo $candidato['foto_path']; ?>" alt="<?php echo $candidato['nombre']; ?>">
                    <?php else: ?>
                    <i class="fas fa-user-circle fa-2x"></i>
                    <?php endif; ?>
                    <span><?php echo isset($candidato) ? $candidato['nombre'] : 'Usuario'; ?></span>
                </div>
                
                <div class="dropdown-menu" id="userMenu">
                    <a href="profile.php" class="dropdown-item">
                        <i class="fas fa-user"></i> Mi Perfil
                    </a>
                    <a href="pruebas.php" class="dropdown-item">
                        <i class="fas fa-clipboard-check"></i> Mis Evaluaciones
                    </a>
                    <a href="aplicaciones.php" class="dropdown-item">
                        <i class="fas fa-briefcase"></i> Mis Aplicaciones
                    </a>
                    <div class="dropdown-divider"></div>
                    <a href="configuracion.php" class="dropdown-item">
                        <i class="fas fa-cog"></i> Configuración
                    </a>
                    <a href="logout.php" class="dropdown-item">
                        <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
                    </a>
                </div>
            </div>
        </div>
    </div>
</header>

<!-- CSS básico para navbar si no está incluido en el archivo principal -->
<style>
.dashboard-navbar {
    background-color: white;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    padding: 10px 0;
    position: fixed;
    top: 0;
    width: 100%;
    z-index: 100;
}

.navbar-container {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0 20px;
    max-width: 1200px;
    margin: 0 auto;
}

.navbar-brand img {
    height: 40px;
}

.navbar-nav {
    display: flex;
    align-items: center;
}

.nav-item {
    margin-left: 20px;
}

.nav-link {
    color: #333;
    text-decoration: none;
    font-weight: 500;
    transition: color 0.3s;
}

.nav-link:hover {
    color: #0088cc;
}

.dropdown {
    position: relative;
}

.dropdown-toggle {
    display: flex;
    align-items: center;
    cursor: pointer;
}

.dropdown-toggle img {
    width: 35px;
    height: 35px;
    border-radius: 50%;
    margin-right: 10px;
    object-fit: cover;
}

.dropdown-menu {
    position: absolute;
    top: 100%;
    right: 0;
    background-color: white;
    border-radius: 5px;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    padding: 10px 0;
    min-width: 200px;
    display: none;
    z-index: 1000;
}

.dropdown-menu.show {
    display: block;
}

.dropdown-item {
    display: block;
    padding: 8px 20px;
    color: #333;
    text-decoration: none;
    transition: background-color 0.3s;
}

.dropdown-item:hover {
    background-color: #f5f5f5;
}

.dropdown-divider {
    border-top: 1px solid #e0e0e0;
    margin: 5px 0;
}
</style>

<script>
// Toggle para el menú desplegable de usuario
document.addEventListener('DOMContentLoaded', function() {
    const userDropdown = document.getElementById('userDropdown');
    const userMenu = document.getElementById('userMenu');
    
    userDropdown.addEventListener('click', function() {
        userMenu.classList.toggle('show');
    });
    
    // Cerrar menú al hacer clic fuera
    document.addEventListener('click', function(event) {
        if (!userDropdown.contains(event.target) && !userMenu.contains(event.target)) {
            userMenu.classList.remove('show');
        }
    });
});
</script>