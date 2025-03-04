<?php
$site_title = "Sistemas de Gestión - Solfis";
$site_description = "Soluciones tecnológicas integrales para la automatización y control de procesos empresariales";
$base_path = 'sections/';
$assets_path = 'assets/';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $site_title; ?></title>
    <meta name="description" content="<?php echo $site_description; ?>">
    <link rel="canonical" href="<?php echo $page_canonical; ?>">
    
    <!-- Open Graph -->
    <meta property="og:title" content="<?php echo $site_title; ?>">
    <meta property="og:description" content="<?php echo $site_description; ?>">
    <meta property="og:url" content="<?php echo $page_canonical; ?>">
    <meta property="og:type" content="website">
    
    <!-- CSS -->
    <link rel="stylesheet" href="assets/css/normalize.css">
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="<?php echo $assets_path; ?>/css/components/nav.css">
    <link rel="stylesheet" href="<?php echo $assets_path; ?>/css/components/service-detail.css">
    <link rel="stylesheet" href="<?php echo $assets_path; ?>/css/components/footer.css">
	<link rel="stylesheet" href="<?php echo $assets_path; ?>/css/components/text-contrast-fixes.css">
	<link rel="stylesheet" href="<?php echo $assets_path; ?>/css/components/dropdown-menu.css">
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <!-- Navbar -->
    <?php include $base_path . 'navbar.html'; ?>

    <main>
        <!-- Hero Section -->
        <section class="service-hero">
            <div class="container">
                <div class="hero-content">
                    <h1>Sistemas de Gestión</h1>
                    <p>Soluciones tecnológicas integrales para la automatización y control de sus procesos empresariales</p>
                    <a href="../contacto" class="btn-primary">Solicitar Servicio</a>
                </div>
                <div class="hero-image">
                    <img src="img/services/sistemas-hero.jpg" alt="Sistemas de Gestión Solfis">
                </div>
            </div>
        </section>

        <!-- Descripción del Servicio -->
        <section class="service-overview">
            <div class="container">
                <div class="overview-grid">
                    <div class="overview-content">
                        <h2>¿Qué ofrecemos?</h2>
                        <p>Nuestras soluciones de sistemas de gestión están diseñadas para transformar y optimizar los procesos operativos de su empresa. Implementamos tecnologías modernas adaptadas a sus necesidades específicas para mejorar la eficiencia, control y toma de decisiones.</p>
                        
                        <div class="key-features">
                            <h3>Características Principales</h3>
                            <ul>
                                <li>
                                    <i class="fas fa-check-circle"></i>
                                    <div>
                                        <h4>Facturación Electrónica</h4>
                                        <p>Sistemas de facturación automatizados compatibles con requisitos fiscales locales.</p>
                                    </div>
                                </li>
                                <li>
                                    <i class="fas fa-check-circle"></i>
                                    <div>
                                        <h4>Control de Inventario</h4>
                                        <p>Gestión integral de inventarios con trazabilidad en tiempo real.</p>
                                    </div>
                                </li>
                                <li>
                                    <i class="fas fa-check-circle"></i>
                                    <div>
                                        <h4>Reportes Financieros</h4>
                                        <p>Generación automática de informes financieros personalizados.</p>
                                    </div>
                                </li>
                                <li>
                                    <i class="fas fa-check-circle"></i>
                                    <div>
                                        <h4>Integración Contable</h4>
                                        <p>Conexión entre sistemas operativos y plataformas contables.</p>
                                    </div>
                                </li>
                            </ul>
                        </div>
                    </div>
                    
                    <div class="service-benefits">
                        <h3>Beneficios</h3>
                        <div class="benefits-grid">
                            <div class="benefit-card">
                                <div class="benefit-icon">
                                    <i class="fas fa-tachometer-alt"></i>
                                </div>
                                <h4>Mayor Eficiencia</h4>
                                <p>Automatización de procesos repetitivos y reducción de tareas manuales.</p>
                            </div>
                            <div class="benefit-card">
                                <div class="benefit-icon">
                                    <i class="fas fa-chart-bar"></i>
                                </div>
                                <h4>Mejor Análisis</h4>
                                <p>Acceso a datos en tiempo real para toma de decisiones informadas.</p>
                            </div>
                            <div class="benefit-card">
                                <div class="benefit-icon">
                                    <i class="fas fa-lock"></i>
                                </div>
                                <h4>Control Integral</h4>
                                <p>Monitoreo completo de operaciones con trazabilidad avanzada.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Proceso de Trabajo -->
        <section class="work-process">
            <div class="container">
                <h2>Nuestro Proceso</h2>
                <div class="process-steps">
                    <div class="step">
                        <div class="step-number">1</div>
                        <h3>Análisis</h3>
                        <p>Diagnóstico de necesidades y procesos actuales.</p>
                    </div>
                    <div class="step">
                        <div class="step-number">2</div>
                        <h3>Diseño</h3>
                        <p>Configuración de soluciones adaptadas a su empresa.</p>
                    </div>
                    <div class="step">
                        <div class="step-number">3</div>
                        <h3>Implementación</h3>
                        <p>Instalación y configuración de sistemas y capacitación.</p>
                    </div>
                    <div class="step">
                        <div class="step-number">4</div>
                        <h3>Soporte</h3>
                        <p>Asistencia continua y actualizaciones periódicas.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- FAQs -->
        <section class="service-faqs">
            <div class="container">
                <h2>Preguntas Frecuentes</h2>
                <div class="faq-grid">
                    <div class="faq-item">
                        <h3>¿Cuánto tiempo toma implementar un sistema de gestión?</h3>
                        <p>El tiempo de implementación varía según la complejidad y tamaño de su empresa, típicamente entre 2-8 semanas para una implementación completa.</p>
                    </div>
                    <div class="faq-item">
                        <h3>¿Puedo integrar sistemas existentes con sus soluciones?</h3>
                        <p>Sí, nuestras soluciones son diseñadas con capacidades de integración para conectarse con sistemas existentes y otras plataformas.</p>
                    </div>
                    <div class="faq-item">
                        <h3>¿Qué nivel de personalización ofrecen?</h3>
                        <p>Adaptamos nuestras soluciones a los procesos específicos de su negocio, desde personalizaciones básicas hasta desarrollos a medida según sus necesidades.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA Section -->
        <section class="service-cta">
            <div class="container">
                <div class="cta-content">
                    <h2>¿Listo para transformar digitalmente su empresa?</h2>
                    <p>Solicite una demostración gratuita y descubra cómo podemos optimizar sus procesos</p>
                    <a href="../contacto" class="btn-primary">
                        Contactar Ahora
                        <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            </div>
        </section>
    </main>

    <!-- Footer -->
    <?php include $base_path . 'footer.html'; ?>

    <!-- Scripts -->
    <script src="/js/main.js"></script>
    <script src="<?php echo $assets_path; ?>/js/components/nav.js"></script>
    <script src="<?php echo $assets_path; ?>/js/components/footer.js"></script>
</body>
</html>