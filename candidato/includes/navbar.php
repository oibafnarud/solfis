<?php
// Obtener datos del candidato si no están disponibles
if (!isset($candidato) && isset($_SESSION['candidato_id'])) {
    require_once '../includes/jobs-system.php';
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
                <a href="vacantes.php" class="nav-link">
                    <i class="fas fa-briefcase"></i> Vacantes
                </a>
            </div>
            
            <div class="nav-item">
                <a href="#" class="nav-link notifications-link">
                    <i class="fas fa-bell"></i>
                    <span class="notifications-count" style="display: none;">0</span>
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

<script>
// Toggle para el menú desplegable de usuario
document.addEventListener('DOMContentLoaded', function() {
    const userDropdown = document.getElementById('userDropdown');
    const userMenu = document.getElementById('userMenu');
    
    if (userDropdown && userMenu) {
        userDropdown.addEventListener('click', function(e) {
            e.preventDefault();
            userMenu.classList.toggle('show');
        });
        
        // Cerrar menú al hacer clic fuera
        document.addEventListener('click', function(event) {
            if (!userDropdown.contains(event.target) && !userMenu.contains(event.target)) {
                userMenu.classList.remove('show');
            }
        });
    }
});
</script>