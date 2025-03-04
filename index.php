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
        <?php include $base_path . 'hero.html'; ?>

        <!-- Services Section - Actualizada para usar nuestra nueva sección de servicios -->
        <section id="servicios" class="services">
            <div class="container">
                <div class="section-header" data-aos="fade-up">
                    <h2>Nuestros Servicios</h2>
                    <p>Soluciones integrales diseñadas para impulsar el éxito de su empresa</p>
                </div>
                
                <div class="services-grid">
                    <!-- Contabilidad Integrada -->
                    <div class="service-card" data-aos="fade-up">
                        <div class="service-icon">
                            <i class="fas fa-calculator"></i>
                        </div>
                        <h3>Contabilidad Integrada</h3>
                        <ul class="service-features">
                            <li>Estados Financieros</li>
                            <li>Registros Contables</li>
                            <li>Conciliaciones Bancarias</li>
                            <li>Nóminas y Prestaciones</li>
                        </ul>
                        <a href="contabilidad.php" class="service-link">
                            Explorar Servicio 
                            <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>

                    <!-- Auditoría -->
                    <div class="service-card" data-aos="fade-up" data-aos-delay="100">
                        <div class="service-icon">
                            <i class="fas fa-search-dollar"></i>
                        </div>
                        <h3>Auditoría de Empresas</h3>
                        <ul class="service-features">
                            <li>Auditoría Operacional</li>
                            <li>Auditoría Fiscal</li>
                            <li>Auditoría Financiera</li>
                            <li>Auditoría Integral</li>
                        </ul>
                        <a href="auditoria.php" class="service-link">
                            Explorar Servicio
                            <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>

                    <!-- Servicios Fiscales -->
                    <div class="service-card" data-aos="fade-up" data-aos-delay="200">
                        <div class="service-icon">
                            <i class="fas fa-file-invoice-dollar"></i>
                        </div>
                        <h3>Servicios Fiscales</h3>
                        <ul class="service-features">
                            <li>Declaraciones Juradas</li>
                            <li>Reportes Fiscales</li>
                            <li>Planificación Fiscal</li>
                            <li>Cumplimiento Tributario</li>
                        </ul>
                        <a href="fiscal.php" class="service-link">
                            Explorar Servicio
                            <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>

                    <!-- Sistemas de Gestión -->
                    <div class="service-card mobile-hidden" data-aos="fade-up" data-aos-delay="300">
                        <div class="service-icon">
                            <i class="fas fa-laptop-code"></i>
                        </div>
                        <h3>Sistemas de Gestión</h3>
                        <ul class="service-features">
                            <li>Facturación Electrónica</li>
                            <li>Control de Inventario</li>
                            <li>Reportes Financieros</li>
                            <li>Integración Contable</li>
                        </ul>
                        <a href="sistemas.php" class="service-link">
                            Explorar Servicio
                            <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>

                    <!-- Servicios Empresariales -->
                    <div class="service-card mobile-hidden" data-aos="fade-up" data-aos-delay="400">
                        <div class="service-icon">
                            <i class="fas fa-building"></i>
                        </div>
                        <h3>Servicios Empresariales</h3>
                        <ul class="service-features">
                            <li>Constitución de Empresas</li>
                            <li>Registro Comercial</li>
                            <li>Gestión de RPE</li>
                            <li>Asesoría Legal</li>
                        </ul>
                        <a href="empresarial.php" class="service-link">
                            Explorar Servicio
                            <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                    
                    <!-- Ley Fronteriza (NUEVO) -->
                    <div class="service-card special-service mobile-hidden" data-aos="fade-up" data-aos-delay="500">
                        <div class="service-icon">
                            <i class="fas fa-percentage"></i>
                        </div>
                        <div class="special-badge">Nuevo</div>
                        <h3>Ley Fronteriza</h3>
                        <ul class="service-features">
                            <li>Exención 100% de Impuestos</li>
                            <li>Aplicable a Múltiples Sectores</li>
                            <li>Beneficios por hasta 20 años</li>
                            <li>Asesoría Especializada</li>
                        </ul>
                        <a href="fronteriza.php" class="service-link">
                            Explorar Servicio
                            <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </div>

                <!-- Botón móvil para ver más servicios -->
                <div class="mobile-more-services">
                    <a href="servicios.php" class="mobile-more-btn">
                        Ver Todos los Servicios <i class="fas fa-arrow-right"></i>
                    </a>
                </div>

                <div class="services-cta desktop-only" data-aos="fade-up">
                    <a href="servicios.php" class="btn btn-secondary">
                        Ver Todos los Servicios
                        <i class="fas fa-arrow-right ml-2"></i>
                    </a>
                </div>
            </div>
        </section>

        <!-- Stats Section -->
        <?php include $base_path . 'stats.html'; ?>

        <!-- Industries Section - Versión simplificada para móvil -->
        <section class="industries">
            <div class="container">
                <div class="section-header" data-aos="fade-up">
                    <h2>Sectores Empresariales</h2>
                    <p>Experiencia especializada en diferentes industrias</p>
                </div>

                <div class="industries-grid">
                    <!-- Comercio -->
                    <div class="industry-card mobile-priority-1" data-aos="fade-up">
                        <div class="industry-icon">
                            <i class="fas fa-store"></i>
                        </div>
                        <h3>Comercio y Distribución</h3>
                        <ul class="industry-features">
                            <li>Retail y Mayoristas</li>
                            <li>E-commerce</li>
                            <li>Importación/Exportación</li>
                            <li>Gestión de Inventarios</li>
                        </ul>
                        <div class="industry-stats">
                            <div class="stat">
                                <span class="stat-number">350+</span>
                                <span class="stat-label">Empresas</span>
                            </div>
                        </div>
                    </div>

                    <!-- Manufactura -->
                    <div class="industry-card mobile-priority-1" data-aos="fade-up" data-aos-delay="100">
                        <div class="industry-icon">
                            <i class="fas fa-industry"></i>
                        </div>
                        <h3>Manufactura e Industria</h3>
                        <ul class="industry-features">
                            <li>Producción Industrial</li>
                            <li>Control de Costos</li>
                            <li>Gestión de Calidad</li>
                            <li>Cadena de Suministro</li>
                        </ul>
                        <div class="industry-stats">
                            <div class="stat">
                                <span class="stat-number">200+</span>
                                <span class="stat-label">Empresas</span>
                            </div>
                        </div>
                    </div>

                    <!-- Servicios Profesionales - Solo visible en desktop o priorizado en móvil -->
                    <div class="industry-card mobile-priority-2" data-aos="fade-up" data-aos-delay="200">
                        <div class="industry-icon">
                            <i class="fas fa-briefcase"></i>
                        </div>
                        <h3>Servicios Profesionales</h3>
                        <ul class="industry-features">
                            <li>Consultoría</li>
                            <li>Servicios Legales</li>
                            <li>Tecnología</li>
                            <li>Marketing y Publicidad</li>
                        </ul>
                        <div class="industry-stats">
                            <div class="stat">
                                <span class="stat-number">450+</span>
                                <span class="stat-label">Empresas</span>
                            </div>
                        </div>
                    </div>

                    <!-- Construcción - Solo visible en desktop o priorizado en móvil -->
                    <div class="industry-card mobile-priority-2" data-aos="fade-up" data-aos-delay="300">
                        <div class="industry-icon">
                            <i class="fas fa-hard-hat"></i>
                        </div>
                        <h3>Construcción e Inmobiliaria</h3>
                        <ul class="industry-features">
                            <li>Proyectos Inmobiliarios</li>
                            <li>Control de Obras</li>
                            <li>Gestión de Contratos</li>
                            <li>Fiscalización de Obras</li>
                        </ul>
                        <div class="industry-stats">
                            <div class="stat">
                                <span class="stat-number">150+</span>
                                <span class="stat-label">Empresas</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Botón móvil para ver más industrias -->
                <div class="mobile-more-industries">
                    <a href="industrias.php" class="mobile-more-btn">
                        Ver Todas las Industrias <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            </div>
        </section>
        
        <!-- Casos de Éxito - Versión optimizada -->
        <section class="success-cases">
            <div class="container">
                <div class="section-header" data-aos="fade-up">
                    <h2>Casos de Éxito</h2>
                    <p>Empresas que han optimizado su gestión con nuestras soluciones</p>
                </div>

                <div class="success-cases-grid">
                    <!-- Caso 1 -->
                    <div class="case-card" data-aos="fade-up">
                        <div class="case-header">
                            <div class="case-logo">
                                <img src="img/cases/logo_jaz.png" alt="Logo Jaz Industrial">
                            </div>
                            <div class="case-badge">Comercio</div>
                        </div>
                        
                        <div class="case-content">
                            <h3>Jaz Industrial</h3>
                            <p class="case-description">
                                Optimización del proceso contable y reducción del 40% en tiempos operativos.
                            </p>
                            
                            <div class="case-results">
                                <div class="result-item">
                                    <div class="result-number">40%</div>
                                    <div class="result-label">Reducción de Costos</div>
                                </div>
                                <div class="result-item">
                                    <div class="result-number">2X</div>
                                    <div class="result-label">Eficiencia</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Caso 2 - Solo visible en desktop -->
                    <div class="case-card desktop-only" data-aos="fade-up" data-aos-delay="100">
                        <div class="case-header">
                            <div class="case-logo">
                                <img src="img/cases/logo_teamlogic.png" alt="Logo Constructora ABC">
                            </div>
                            <div class="case-badge">Logistica</div>
                        </div>
                        
                        <div class="case-content">
                            <h3>Team Logic Dominicana</h3>
                            <p class="case-description">
                                Implementación de sistema de gestión integral y mejora en control de proyectos.
                            </p>
                            
                            <div class="case-results">
                                <div class="result-item">
                                    <div class="result-number">60%</div>
                                    <div class="result-label">Mejor Control</div>
                                </div>
                                <div class="result-item">
                                    <div class="result-number">3X</div>
                                    <div class="result-label">ROI</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Botón móvil para ver más casos de éxito -->
                <div class="mobile-more-cases">
                    <a href="casos.php" class="mobile-more-btn">
                        Ver Todos los Casos <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            </div>
        </section>

        <!-- Why Choose Us Section - Versión optimizada -->
        <section class="why-us">
            <div class="container">
                <div class="section-header" data-aos="fade-up">
                    <h2>¿Por qué elegir SolFis?</h2>
                    <p>Descubra lo que nos hace diferentes y cómo podemos ayudar a su empresa a alcanzar el siguiente nivel</p>
                </div>

                <div class="why-us-grid">
                    <!-- Características en una versión optimizada para móvil -->
                    <div class="why-us-features">
                        <!-- Experiencia - Prioridad 1 -->
                        <div class="feature-card mobile-priority-1" data-aos="fade-up" style="--animation-order: 1">
                            <div class="feature-icon">
                                <i class="fas fa-medal"></i>
                            </div>
                            <h3>Experiencia Comprobada</h3>
                            <p>Más de 15 años brindando soluciones financieras de excelencia a empresas de diversos sectores.</p>
                        </div>

                        <!-- Tecnología - Prioridad 1 -->
                        <div class="feature-card mobile-priority-1" data-aos="fade-up" data-aos-delay="100" style="--animation-order: 2">
                            <div class="feature-icon">
                                <i class="fas fa-laptop-code"></i>
                            </div>
                            <h3>Tecnología Avanzada</h3>
                            <p>Utilizamos las últimas herramientas y software para garantizar precisión y eficiencia en nuestros servicios.</p>
                        </div>

                        <!-- Atención Personalizada - Prioridad 2 -->
                        <div class="feature-card mobile-priority-2" data-aos="fade-up" data-aos-delay="200" style="--animation-order: 3">
                            <div class="feature-icon">
                                <i class="fas fa-user-tie"></i>
                            </div>
                            <h3>Atención Personalizada</h3>
                            <p>Cada cliente recibe un servicio adaptado a sus necesidades específicas, con atención dedicada y continua.</p>
                        </div>

                        <!-- Garantía de Calidad - Solo en desktop -->
                        <div class="feature-card desktop-only" data-aos="fade-up" data-aos-delay="300" style="--animation-order: 4">
                            <div class="feature-icon">
                                <i class="fas fa-shield-alt"></i>
                            </div>
                            <h3>Garantía de Calidad</h3>
                            <p>Comprometidos con la excelencia y la mejora continua en todos nuestros servicios.</p>
                        </div>
                    </div>

                    <!-- Imagen - Se hace más pequeña en móvil -->
                    <div class="why-us-image-wrapper" data-aos="fade-left">
                        <div class="why-us-image">
                            <img src="img/why-us.jpg" alt="Equipo Solfis en acción">
                            <div class="image-overlay"></div>
                        </div>
                        <div class="experience-badge">
                            <span>15+</span>
                            <span>Años de Experiencia</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Testimonios - Versión optimizada, especialmente para móvil -->
        <section class="testimonials">
            <div class="container">
                <div class="section-header">
                    <h2>Lo que Dicen Nuestros Clientes</h2>
                    <p>Experiencias reales de empresas que confían en nosotros</p>
                </div>

                <div class="testimonials-slider">
                    <div class="testimonials-track">
                        <!-- Solamente mostramos un testimonio a la vez en móvil -->
                        <div class="testimonial-card active">
                            <div class="testimonial-content">
                                <i class="fas fa-quote-left quote-icon"></i>
                                <p>Solfis ha transformado completamente nuestra gestión financiera. Su equipo profesional y dedicado ha sido fundamental en nuestro crecimiento empresarial.</p>
                                <div class="testimonial-author">
                                    <img src="img/testimonials/client1.jpg" alt="Carlos Rodríguez">
                                    <div class="author-info">
                                        <h4>Carlos Rodríguez</h4>
                                        <p>Director Financiero, Grupo ABC</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Otros testimonios -->
                        <div class="testimonial-card">
                            <div class="testimonial-content">
                                <i class="fas fa-quote-left quote-icon"></i>
                                <p>La experiencia y profesionalismo de Solfis es excepcional. Sus servicios han ayudado a optimizar nuestros procesos significativamente.</p>
                                <div class="testimonial-author">
                                    <img src="img/testimonials/client2.jpg" alt="María Sánchez">
                                    <div class="author-info">
                                        <h4>María Sánchez</h4>
                                        <p>CEO, Innovación Tecnológica S.R.L.</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="testimonial-card">
                            <div class="testimonial-content">
                                <i class="fas fa-quote-left quote-icon"></i>
                                <p>La implementación del sistema de gestión financiera nos ha permitido tener un control total de nuestras operaciones.</p>
                                <div class="testimonial-author">
                                    <img src="img/testimonials/client3.jpg" alt="Roberto Méndez">
                                    <div class="author-info">
                                        <h4>Roberto Méndez</h4>
                                        <p>Gerente General, Constructora RMC</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Controles de navegación simplificados para móvil -->
                    <div class="slider-controls">
                        <button class="slider-arrow prev" aria-label="Anterior">
                            <i class="fas fa-chevron-left"></i>
                        </button>
                        <div class="slider-dots">
                            <span class="dot active"></span>
                            <span class="dot"></span>
                            <span class="dot"></span>
                        </div>
                        <button class="slider-arrow next" aria-label="Siguiente">
                            <i class="fas fa-chevron-right"></i>
                        </button>
                    </div>
                </div>
            </div>
        </section>

        <!-- Blog optimizado para móvil -->
        <section class="blog-section">
            <div class="container">
                <div class="blog-header">
                    <h2>Últimas Publicaciones</h2>
                    <p>Manténgase informado sobre las últimas novedades fiscales y financieras</p>
                </div>

                <div class="blog-grid">
                    <!-- Post 1 - Siempre visible -->
                    <div class="blog-post mobile-priority-1">
                        <div class="post-label">Fiscal</div>
                        <h3>Principales Cambios Fiscales para 2025</h3>
                        <p>Análisis detallado de las nuevas regulaciones fiscales y su impacto en las empresas dominicanas.</p>
                        <div class="post-meta">
                            <div class="author">
                                <img src="img/team/author1.jpg" alt="Juan Pérez">
                                <span>Juan Pérez</span>
                            </div>
                        </div>
                        <a href="#" class="read-more">Leer más →</a>
                    </div>

                    <!-- Post 2 - Siempre visible -->
                    <div class="blog-post mobile-priority-1">
                        <div class="post-label">Contabilidad</div>
                        <h3>Transformación Digital en la Contabilidad</h3>
                        <p>Cómo la tecnología está revolucionando los procesos contables tradicionales.</p>
                        <div class="post-meta">
                            <div class="author">
                                <img src="img/team/author2.jpg" alt="María Gómez">
                                <span>María Gómez</span>
                            </div>
                        </div>
                        <a href="#" class="read-more">Leer más →</a>
                    </div>

                    <!-- Post 3 - Solo desktop -->
                    <div class="blog-post desktop-only">
                        <div class="post-label">Finanzas</div>
                        <h3>Optimización de la Gestión Financiera</h3>
                        <p>Estrategias efectivas para mejorar el control financiero de su empresa.</p>
                        <span class="date">5 Feb 2025</span>
                        <a href="#" class="read-more">Leer más →</a>
                    </div>

                    <!-- Post 4 - Solo desktop -->
                    <div class="blog-post desktop-only">
                        <div class="post-label">Auditoría</div>
                        <h3>Importancia de la Auditoría Preventiva</h3>
                        <p>Beneficios de mantener un programa de auditoría constante en su empresa.</p>
                        <span class="date">1 Feb 2025</span>
                        <a href="#" class="read-more">Leer más →</a>
                    </div>
                </div>

                <div class="blog-footer">
                    <a href="blog.php" class="see-all">Ver Todas las Publicaciones →</a>
                </div>
            </div>
        </section>

        <!-- Contacto - Formulario simplificado para móvil -->
        <section class="contact">
            <div class="container">
                <div class="section-header">
                    <h2>Contáctenos</h2>
                    <p>Estamos aquí para ayudarle con sus necesidades empresariales</p>
                </div>

                <div class="contact-grid">
                    <!-- Panel de Información - Ajustado para móvil -->
                    <div class="info-panel mobile-priority-2">
                        <h3>Información de Contacto</h3>
                        <p>Comuníquese con nosotros y descubra cómo podemos ayudar a su empresa</p>

                        <div class="contact-info">
                            <!-- Solo mostramos la información esencial en móvil -->
                            <div class="info-item">
                                <i class="fas fa-map-marker-alt"></i>
                                <div>
                                    <h4>Ubicación</h4>
                                    <p>Santo Domingo, República Dominicana</p>
                                </div>
                            </div>

                            <div class="info-item">
                                <i class="fas fa-phone-alt"></i>
                                <div>
                                    <h4>Teléfonos</h4>
                                    <p>+1 (809) 555-0123</p>
                                </div>
                            </div>

                            <div class="info-item">
                                <i class="fas fa-envelope"></i>
                                <div>
                                    <h4>Email</h4>
                                    <p>contacto@solfis.com.do</p>
                                </div>
                            </div>
                        </div>

                        <!-- Iconos de redes sociales -->
                        <div class="social-links">
                            <a href="#" class="social-link"><i class="fab fa-facebook-f"></i></a>
                            <a href="#" class="social-link"><i class="fab fa-linkedin-in"></i></a>
                            <a href="#" class="social-link"><i class="fab fa-instagram"></i></a>
                            <a href="#" class="social-link"><i class="fab fa-twitter"></i></a>
                        </div>
                    </div>

                    <!-- Formulario - Prioridad 1 en móvil -->
                    <div class="form-panel mobile-priority-1">
                        <h3>Envíenos un Mensaje</h3>
                        <p>Complete el formulario y nos pondremos en contacto pronto</p>

                        <form id="contactForm">
                            <!-- Formulario simplificado para móvil -->
                            <div class="form-grid">
                                <input type="text" placeholder="Nombre Completo" required>
                                <input type="text" placeholder="Empresa" class="desktop-only">
                            </div>

                            <div class="form-grid">
                                <input type="email" placeholder="Correo Electrónico" required>
                                <input type="tel" placeholder="Teléfono" required>
                            </div>

                            <select required>
                                <option value="">Servicio de Interés</option>
                                <option value="contabilidad">Contabilidad Integrada</option>
                                <option value="auditoria">Auditoría de Empresas</option>
                                <option value="fiscal">Servicios Fiscales</option>
                                <option value="sistemas">Sistemas de Gestión</option>
                                <option value="empresarial">Servicios Empresariales</option>
                                <option value="fronteriza">Ley Fronteriza</option>
                            </select>

                            <textarea rows="5" placeholder="Mensaje" required></textarea>

                            <button type="submit" class="submit-btn">
                                Enviar Mensaje
                                <i class="fas fa-paper-plane"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </section>

        <!-- Newsletter simplificado -->
        <section class="newsletter">
            <div class="container">
                <div class="newsletter-content">
                    <div class="text-content">
                        <h2>Manténgase Informado</h2>
                        <p>Suscríbase a nuestro boletín para recibir actualizaciones fiscales y financieras</p>
                    </div>
                    
                    <form class="newsletter-form">
                        <div class="form-group">
                            <input type="email" placeholder="Su correo electrónico" required>
                            <button type="submit" class="subscribe-btn">
                                <span>Suscribirse</span>
                                <i class="fas fa-paper-plane"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </section>
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
    <script src="<?php echo $assets_path; ?>/js/components/hero.js"></script>
    <script src="<?php echo $assets_path; ?>/js/components/stats.js"></script>
    <script src="<?php echo $assets_path; ?>/js/components/why-us.js"></script>
    <script src="<?php echo $assets_path; ?>/js/components/testimonials.js"></script>
    <script src="<?php echo $assets_path; ?>/js/components/contact.js"></script>
    <script src="<?php echo $assets_path; ?>/js/components/footer.js"></script>
    <script src="<?php echo $assets_path; ?>/js/components/industries.js"></script>
    <script src="<?php echo $assets_path; ?>/js/components/success-cases.js"></script>
    <script src="<?php echo $assets_path; ?>/js/components/hero-cubes.js"></script>
    <script src="<?php echo $assets_path; ?>/js/components/popup.js"></script>

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