<?php
$site_title = "Auditoría de Empresas - Solfis";
$site_description = "Servicios profesionales de auditoría para empresas en República Dominicana";
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
	<link rel="stylesheet" href="<?php echo $assets_path; ?>/css/components/dropdown-menu.css">
    <link rel="stylesheet" href="assets/css/normalize.css">
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="<?php echo $assets_path; ?>/css/components/nav.css">
    <link rel="stylesheet" href="<?php echo $assets_path; ?>/css/components/service-detail.css">
    <link rel="stylesheet" href="<?php echo $assets_path; ?>/css/components/footer.css">
	<link rel="stylesheet" href="<?php echo $assets_path; ?>/css/components/text-contrast-fixes.css">
    
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
                    <h1>Auditoría de Empresas</h1>
                    <p>Evaluación exhaustiva y objetiva de sus procesos financieros y operativos para garantizar el cumplimiento y la eficiencia</p>
                    <a href="../contacto" class="btn-primary">Solicitar Servicio</a>
                </div>
                <div class="hero-image">
                    <img src="img/services/auditoria-hero.jpg" alt="Auditoría Empresarial Solfis">
                </div>
            </div>
        </section>

        <!-- Descripción del Servicio -->
        <section class="service-overview">
            <div class="container">
                <div class="overview-grid">
                    <div class="overview-content">
                        <h2>¿Qué ofrecemos?</h2>
                        <p>Nuestro servicio de auditoría está diseñado para proporcionar una evaluación independiente y objetiva de sus operaciones financieras y procesos internos. Utilizamos metodologías avanzadas y herramientas especializadas para garantizar una revisión exhaustiva y precisa.</p>
                        
                        <div class="key-features">
                            <h3>Características Principales</h3>
                            <ul>
                                <li>
                                    <i class="fas fa-check-circle"></i>
                                    <div>
                                        <h4>Auditoría Financiera</h4>
                                        <p>Evaluación detallada de estados financieros y registros contables.</p>
                                    </div>
                                </li>
                                <li>
                                    <i class="fas fa-check-circle"></i>
                                    <div>
                                        <h4>Auditoría Operativa</h4>
                                        <p>Análisis de procesos y sistemas para optimizar la eficiencia.</p>
                                    </div>
                                </li>
                                <li>
                                    <i class="fas fa-check-circle"></i>
                                    <div>
                                        <h4>Auditoría de Cumplimiento</h4>
                                        <p>Verificación del cumplimiento normativo y regulatorio.</p>
                                    </div>
                                </li>
                                <li>
                                    <i class="fas fa-check-circle"></i>
                                    <div>
                                        <h4>Auditoría de Control Interno</h4>
                                        <p>Evaluación de sistemas y controles internos.</p>
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
                                    <i class="fas fa-shield-alt"></i>
                                </div>
                                <h4>Mayor Seguridad</h4>
                                <p>Identificación y mitigación de riesgos potenciales.</p>
                            </div>
                            <div class="benefit-card">
                                <div class="benefit-icon">
                                    <i class="fas fa-chart-line"></i>
                                </div>
                                <h4>Mejora Continua</h4>
                                <p>Recomendaciones para optimizar procesos y controles.</p>
                            </div>
                            <div class="benefit-card">
                                <div class="benefit-icon">
                                    <i class="fas fa-check-double"></i>
                                </div>
                                <h4>Cumplimiento</h4>
                                <p>Garantía de adhesión a normativas y regulaciones.</p>
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
                        <h3>Planificación</h3>
                        <p>Definición de alcance y objetivos de la auditoría.</p>
                    </div>
                    <div class="step">
                        <div class="step-number">2</div>
                        <h3>Ejecución</h3>
                        <p>Recopilación y análisis de información.</p>
                    </div>
                    <div class="step">
                        <div class="step-number">3</div>
                        <h3>Evaluación</h3>
                        <p>Análisis detallado y documentación de hallazgos.</p>
                    </div>
                    <div class="step">
                        <div class="step-number">4</div>
                        <h3>Reporte</h3>
                        <p>Presentación de resultados y recomendaciones.</p>
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
                        <h3>¿Cuál es la duración típica de una auditoría?</h3>
                        <p>El tiempo varía según el alcance y tamaño de la empresa, pero generalmente toma entre 2-4 semanas.</p>
                    </div>
                    <div class="faq-item">
                        <h3>¿Qué documentación se requiere?</h3>
                        <p>Se necesitan estados financieros, registros contables, políticas internas y documentación de procesos.</p>
                    </div>
                    <div class="faq-item">
                        <h3>¿Con qué frecuencia se recomienda realizar auditorías?</h3>
                        <p>Recomendamos auditorías anuales, aunque la frecuencia puede variar según requisitos regulatorios.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA Section -->
        <section class="service-cta">
            <div class="container">
                <div class="cta-content">
                    <h2>¿Listo para fortalecer el control de su empresa?</h2>
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