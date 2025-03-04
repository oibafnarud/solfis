<?php
$site_title = "Servicios Empresariales - Solfis";
$site_description = "Acompañamiento integral en todos los aspectos legales y administrativos para su empresa";
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
                    <h1>Servicios Empresariales</h1>
                    <p>Acompañamiento integral en todos los aspectos legales y administrativos para la constitución y gestión eficiente de su empresa</p>
                    <a href="../contacto" class="btn-primary">Solicitar Servicio</a>
                </div>
                <div class="hero-image">
                    <img src="img/services/empresarial-hero.jpg" alt="Servicios Empresariales Solfis">
                </div>
            </div>
        </section>

        <!-- Descripción del Servicio -->
        <section class="service-overview">
            <div class="container">
                <div class="overview-grid">
                    <div class="overview-content">
                        <h2>¿Qué ofrecemos?</h2>
                        <p>Nuestros servicios empresariales proporcionan un soporte completo en la gestión legal y administrativa de su empresa. Desde la constitución inicial hasta la obtención de permisos especiales, nuestro equipo de expertos le guiará en cada paso del proceso para garantizar el cumplimiento normativo y la operación eficiente.</p>
                        
                        <div class="key-features">
                            <h3>Características Principales</h3>
                            <ul>
                                <li>
                                    <i class="fas fa-check-circle"></i>
                                    <div>
                                        <h4>Constitución de Empresas</h4>
                                        <p>Asesoramiento y gestión completa del proceso de formación de sociedades.</p>
                                    </div>
                                </li>
                                <li>
                                    <i class="fas fa-check-circle"></i>
                                    <div>
                                        <h4>Registro Comercial</h4>
                                        <p>Tramitación de nombres comerciales y registros mercantiles.</p>
                                    </div>
                                </li>
                                <li>
                                    <i class="fas fa-check-circle"></i>
                                    <div>
                                        <h4>Gestión de RPE</h4>
                                        <p>Registro y mantenimiento como Proveedor del Estado (RPE).</p>
                                    </div>
                                </li>
                                <li>
                                    <i class="fas fa-check-circle"></i>
                                    <div>
                                        <h4>Asesoría Legal</h4>
                                        <p>Consultoría en aspectos legales y corporativos para su operación diaria.</p>
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
                                    <i class="fas fa-hourglass-half"></i>
                                </div>
                                <h4>Ahorro de Tiempo</h4>
                                <p>Gestión eficiente de trámites burocráticos y procedimientos legales.</p>
                            </div>
                            <div class="benefit-card">
                                <div class="benefit-icon">
                                    <i class="fas fa-gavel"></i>
                                </div>
                                <h4>Seguridad Jurídica</h4>
                                <p>Cumplimiento con todos los requisitos legales vigentes.</p>
                            </div>
                            <div class="benefit-card">
                                <div class="benefit-icon">
                                    <i class="fas fa-handshake"></i>
                                </div>
                                <h4>Oportunidades de Negocio</h4>
                                <p>Acceso a licitaciones estatales y nuevos mercados.</p>
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
                        <h3>Consulta Inicial</h3>
                        <p>Evaluación de necesidades y planificación estratégica.</p>
                    </div>
                    <div class="step">
                        <div class="step-number">2</div>
                        <h3>Documentación</h3>
                        <p>Preparación y recopilación de documentos necesarios.</p>
                    </div>
                    <div class="step">
                        <div class="step-number">3</div>
                        <h3>Gestión</h3>
                        <p>Tramitación ante las instituciones correspondientes.</p>
                    </div>
                    <div class="step">
                        <div class="step-number">4</div>
                        <h3>Seguimiento</h3>
                        <p>Monitoreo y actualización periódica de registros y permisos.</p>
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
                        <h3>¿Cuánto tiempo toma constituir una empresa?</h3>
                        <p>El proceso completo generalmente toma entre 3-6 semanas, dependiendo del tipo de sociedad y los requisitos específicos.</p>
                    </div>
                    <div class="faq-item">
                        <h3>¿Qué documentos necesito para registrarme como Proveedor del Estado?</h3>
                        <p>Se requieren documentos societarios, certificaciones fiscales al día, referencias comerciales y bancarias, entre otros requisitos específicos según su actividad.</p>
                    </div>
                    <div class="faq-item">
                        <h3>¿Ofrecen servicios de mantenimiento para cumplimiento corporativo?</h3>
                        <p>Sí, contamos con servicios de mantenimiento anual que incluyen actualización de registros, renovación de permisos y asesoría continua en cumplimiento corporativo.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA Section -->
        <section class="service-cta">
            <div class="container">
                <div class="cta-content">
                    <h2>¿Listo para formalizar o fortalecer su empresa?</h2>
                    <p>Solicite una consulta gratuita y descubra cómo podemos ayudarle</p>
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