<?php
$site_title = "Servicios Fiscales - Solfis";
$site_description = "Asesoría y gestión fiscal especializada para empresas en República Dominicana";
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
                    <h1>Servicios Fiscales</h1>
                    <p>Asesoría fiscal especializada para optimizar su planificación tributaria y garantizar el cumplimiento con las regulaciones vigentes</p>
                    <a href="../contacto" class="btn-primary">Solicitar Servicio</a>
                </div>
                <div class="hero-image">
                    <img src="img/services/fiscal-hero.jpg" alt="Servicios Fiscales Solfis">
                </div>
            </div>
        </section>

        <!-- Descripción del Servicio -->
        <section class="service-overview">
            <div class="container">
                <div class="overview-grid">
                    <div class="overview-content">
                        <h2>¿Qué ofrecemos?</h2>
                        <p>Nuestros servicios fiscales están diseñados para ayudarle a navegar eficientemente por el complejo panorama tributario dominicano. Combinamos experiencia técnica con conocimiento profundo de las regulaciones locales para maximizar beneficios fiscales legales y minimizar riesgos.</p>
                        
                        <div class="key-features">
                            <h3>Características Principales</h3>
                            <ul>
                                <li>
                                    <i class="fas fa-check-circle"></i>
                                    <div>
                                        <h4>Declaraciones Juradas</h4>
                                        <p>Preparación y presentación de declaraciones juradas IR-1, IR-2 y otras obligaciones fiscales.</p>
                                    </div>
                                </li>
                                <li>
                                    <i class="fas fa-check-circle"></i>
                                    <div>
                                        <h4>Reportes Fiscales</h4>
                                        <p>Elaboración y presentación de reportes 606, 607, 608, 609, 623 y otros formatos exigidos.</p>
                                    </div>
                                </li>
                                <li>
                                    <i class="fas fa-check-circle"></i>
                                    <div>
                                        <h4>Planificación Fiscal</h4>
                                        <p>Estrategias personalizadas para optimizar su carga fiscal dentro del marco legal.</p>
                                    </div>
                                </li>
                                <li>
                                    <i class="fas fa-check-circle"></i>
                                    <div>
                                        <h4>Consultoría Tributaria</h4>
                                        <p>Asesoramiento en temas fiscales complejos y resolución de consultas específicas.</p>
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
                                    <i class="fas fa-file-invoice-dollar"></i>
                                </div>
                                <h4>Tranquilidad</h4>
                                <p>Cumplimiento total con las obligaciones fiscales dentro de los plazos establecidos.</p>
                            </div>
                            <div class="benefit-card">
                                <div class="benefit-icon">
                                    <i class="fas fa-hand-holding-usd"></i>
                                </div>
                                <h4>Ahorro Fiscal</h4>
                                <p>Identificación de oportunidades legales para minimizar su carga tributaria.</p>
                            </div>
                            <div class="benefit-card">
                                <div class="benefit-icon">
                                    <i class="fas fa-shield-alt"></i>
                                </div>
                                <h4>Reducción de Riesgos</h4>
                                <p>Prevención de multas y sanciones por incumplimientos fiscales.</p>
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
                        <h3>Diagnóstico</h3>
                        <p>Análisis detallado de su situación fiscal actual.</p>
                    </div>
                    <div class="step">
                        <div class="step-number">2</div>
                        <h3>Planificación</h3>
                        <p>Diseño de estrategias fiscales personalizadas.</p>
                    </div>
                    <div class="step">
                        <div class="step-number">3</div>
                        <h3>Implementación</h3>
                        <p>Ejecución de procesos y presentación de declaraciones.</p>
                    </div>
                    <div class="step">
                        <div class="step-number">4</div>
                        <h3>Seguimiento</h3>
                        <p>Monitoreo continuo y ajustes según cambios regulatorios.</p>
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
                        <h3>¿Cuáles son los plazos principales para las declaraciones fiscales?</h3>
                        <p>Los plazos varían según el tipo de impuesto y el régimen fiscal de su empresa. Trabajamos con un calendario fiscal personalizado para garantizar el cumplimiento oportuno.</p>
                    </div>
                    <div class="faq-item">
                        <h3>¿Cómo pueden ayudarme a reducir mi carga fiscal legalmente?</h3>
                        <p>Implementamos estrategias como la correcta aplicación de deducciones, incentivos fiscales disponibles y planificación de operaciones con impacto fiscal favorable.</p>
                    </div>
                    <div class="faq-item">
                        <h3>¿Qué sucede si enfrento una auditoría fiscal?</h3>
                        <p>Le proporcionamos representación y acompañamiento durante todo el proceso, preparando la documentación necesaria y respondiendo efectivamente a los requerimientos fiscales.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA Section -->
        <section class="service-cta">
            <div class="container">
                <div class="cta-content">
                    <h2>¿Listo para optimizar su gestión fiscal?</h2>
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