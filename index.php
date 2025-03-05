<?php
// Configuración básica
$site_title = "Solfis - Precisión Financiera";
$site_description = "Servicios contables, financieros y empresariales en República Dominicana";
$base_path = 'sections/';
$assets_path = 'assets/';

// Detectar si es un dispositivo móvil para carga optimizada
$is_mobile = false;
if (isset($_SERVER['HTTP_USER_AGENT'])) {
    $is_mobile = preg_match('/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows ce|xda|xiino/i', $_SERVER['HTTP_USER_AGENT']);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php echo $site_description; ?>">
    <title><?php echo $site_title; ?></title>

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="<?php echo $assets_path; ?>img/favicon.ico">
    
    <!-- CSS Base -->
    <link rel="stylesheet" href="<?php echo $assets_path; ?>/css/normalize.css">
    <link rel="stylesheet" href="<?php echo $assets_path; ?>/css/main.css">
    <link rel="stylesheet" href="<?php echo $assets_path; ?>/css/fixes.css">
    <link rel="stylesheet" href="<?php echo $assets_path; ?>/css/components/dropdown-menu.css">
    <link rel="stylesheet" href="<?php echo $assets_path; ?>/css/mobile-layout.css">
    

    <!-- CSS Componentes -->
	<link rel="stylesheet" href="<?php echo $assets_path; ?>/css/components/hero-banner.css">
    <link rel="stylesheet" href="<?php echo $assets_path; ?>/css/components/nav.css">
    <link rel="stylesheet" href="<?php echo $assets_path; ?>/css/components/hero.css">
    <link rel="stylesheet" href="<?php echo $assets_path; ?>/css/components/services.css">
    <link rel="stylesheet" href="<?php echo $assets_path; ?>/css/components/stats.css">
    <link rel="stylesheet" href="<?php echo $assets_path; ?>/css/components/why-us.css">
    <link rel="stylesheet" href="<?php echo $assets_path; ?>/css/components/testimonials.css">
    <link rel="stylesheet" href="<?php echo $assets_path; ?>/css/components/testimonials-compact.css">
    <link rel="stylesheet" href="<?php echo $assets_path; ?>/css/components/blog-section.css">
    <link rel="stylesheet" href="<?php echo $assets_path; ?>/css/components/contact-section.css">
    <link rel="stylesheet" href="<?php echo $assets_path; ?>/css/components/newsletter.css">
    <link rel="stylesheet" href="<?php echo $assets_path; ?>/css/components/footer.css">
    <link rel="stylesheet" href="<?php echo $assets_path; ?>/css/components/industries.css">
    <link rel="stylesheet" href="<?php echo $assets_path; ?>/css/components/success-cases.css">
    <link rel="stylesheet" href="<?php echo $assets_path; ?>/css/components/hero-cubes.css">
    <link rel="stylesheet" href="<?php echo $assets_path; ?>/css/components/text-contrast-fixes.css">
    <link rel="stylesheet" href="<?php echo $assets_path; ?>/css/components/popup.css">
    <link rel="stylesheet" href="<?php echo $assets_path; ?>/css/mobile-optimizations.css">
	<link rel="stylesheet" href="<?php echo $assets_path; ?>/css/components/industries-tabs.css">
    
    <!-- Fuentes -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- AOS - Animate On Scroll -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css">
</head>
<body class="<?php echo $is_mobile ? 'mobile-view' : 'desktop-view'; ?>">
    <!-- Preloader -->
    <div class="preloader">
        <div class="loader"></div>
    </div>

    <!-- Navbar -->
    <?php include $base_path . 'navbar.html'; ?>

    <!-- Main Content -->
    <main>
        <!-- Hero Section -->
        <?php include $base_path . 'hero-banner.html'; ?>

        <!-- Services Section - Actualizada para usar nuestra nueva sección de servicios -->
		<?php include $base_path . 'services.html'; ?>

        <!-- Stats Section -->
        <?php include $base_path . 'stats.html'; ?>
		
		<!-- Why-Us -->
        <?php include $base_path . 'why-us.html'; ?>
		
		<!-- Industrias -->
        <?php include $base_path . 'industries.html'; ?>
		
		<!-- Casos -->
        <?php include $base_path . 'success-cases.html'; ?>
		
		<!-- Contacto -->
        <?php include $base_path . 'contact-section.html'; ?>
		

    </main>

    <!-- Footer -->
    <?php include $base_path . 'footer.html'; ?>
    
    <!-- Popup -->
    <?php include $base_path . 'popup.html'; ?>

    <!-- Botón flotante de WhatsApp para móvil -->
    <a href="https://wa.me/18095550123" class="whatsapp-btn mobile-only">
        <i class="fab fa-whatsapp"></i>
    </a>

    <!-- Scripts -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
    
    <!-- Scripts Componentes -->
    <script src="<?php echo $assets_path; ?>/js/main.js"></script>
    <script src="<?php echo $assets_path; ?>/js/components/navbar.js"></script>
    <script src="<?php echo $assets_path; ?>/js/components/stats.js"></script>
    <script src="<?php echo $assets_path; ?>/js/components/why-us.js"></script>
    <script src="<?php echo $assets_path; ?>/js/components/testimonials.js"></script>
    <script src="<?php echo $assets_path; ?>/js/components/contact.js"></script>
    <script src="<?php echo $assets_path; ?>/js/components/footer.js"></script>
    <script src="<?php echo $assets_path; ?>/js/components/success-cases.js"></script>
    <script src="<?php echo $assets_path; ?>/js/components/hero-cubes.js"></script>
    <script src="<?php echo $assets_path; ?>/js/components/popup.js"></script>
	<script src="<?php echo $assets_path; ?>/js/components/hero-banner.js"></script>
	<script src="<?php echo $assets_path; ?>/js/components/industries-tabs.js"></script>

    <!-- Inicialización de AOS -->
    <script>
        AOS.init({
            duration: 800,
            once: true,
            offset: 50,
            disable: window.innerWidth < 768 // Desactivar AOS en móvil para mejor rendimiento
        });
        
        // Script para mostrar/ocultar elementos según el scroll en móvil
        if (window.innerWidth < 768) {
            const priorityElements = document.querySelectorAll('.mobile-priority-2');
            let scrollThreshold = window.innerHeight * 0.8;
            
            window.addEventListener('scroll', function() {
                if (window.pageYOffset > scrollThreshold) {
                    priorityElements.forEach(element => {
                        element.classList.add('visible');
                    });
                }
            });
        }
    </script>
</body>
</html>