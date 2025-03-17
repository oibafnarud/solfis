<?php
$site_title = "Nuestro Proceso - Solfis";
$site_description = "Conoce cómo trabajamos en SolFis para brindarte el mejor servicio contable y fiscal";
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
    
    <!-- CSS -->
    <link rel="stylesheet" href="assets/css/normalize.css">
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="<?php echo $assets_path; ?>/css/components/nav.css">
    <link rel="stylesheet" href="<?php echo $assets_path; ?>/css/components/footer.css">
    <link rel="stylesheet" href="<?php echo $assets_path; ?>/css/components/dropdown-menu.css">
    <link rel="stylesheet" href="<?php echo $assets_path; ?>/css/components/process.css">
    <link rel="stylesheet" href="<?php echo $assets_path; ?>/css/text-contrast-fixes.css">
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- AOS - Animate On Scroll -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css">
</head>
<body>
    <!-- Navbar -->
    <?php include $base_path . 'navbar.html'; ?>

    <main>
        <!-- Hero Section -->
        <section class="process-hero">
            <div class="container">
                <div class="hero-content">
                    <h1>Nuestro Proceso</h1>
                    <p>Conoce cómo trabajamos para garantizar la calidad y eficiencia en todos nuestros servicios</p>
                </div>
            </div>
        </section>

        <!-- Metodología -->
        <section class="methodology-section">
            <div class="container">
                <div class="section-header">
                    <h2>Metodología de Trabajo</h2>
                    <p>En SolFis entendemos que cada cliente es único, por eso implementamos un enfoque personalizado que garantiza resultados óptimos para su empresa.</p>
                </div>
                
                <div class="methodology-grid">
                    <div class="methodology-item" data-aos="fade-up" data-aos-delay="100">
                        <div class="icon-container">
                            <i class="fas fa-search"></i>
                        </div>
                        <h3>Diagnóstico</h3>
                        <p>Evaluamos la situación actual de su empresa, identificando áreas de oportunidad y estableciendo objetivos claros.</p>
                    </div>
                    
                    <div class="methodology-item" data-aos="fade-up" data-aos-delay="200">
                        <div class="icon-container">
                            <i class="fas fa-lightbulb"></i>
                        </div>
                        <h3>Soluciones a Medida</h3>
                        <p>Desarrollamos estrategias personalizadas que se adaptan a las necesidades específicas de su negocio.</p>
                    </div>
                    
                    <div class="methodology-item" data-aos="fade-up" data-aos-delay="300">
                        <div class="icon-container">
                            <i class="fas fa-cogs"></i>
                        </div>
                        <h3>Implementación</h3>
                        <p>Ponemos en marcha las soluciones diseñadas siguiendo un plan de acción estructurado y eficiente.</p>
                    </div>
                    
                    <div class="methodology-item" data-aos="fade-up" data-aos-delay="400">
                        <div class="icon-container">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <h3>Seguimiento</h3>
                        <p>Monitoreamos constantemente los resultados para realizar ajustes y optimizaciones en tiempo real.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Proceso paso a paso -->
        <section class="step-process-section">
            <div class="container">
                <div class="section-header">
                    <h2>Proceso Paso a Paso</h2>
                    <p>Nuestro enfoque metódico garantiza resultados consistentes y de alta calidad para cada cliente.</p>
                </div>
                
                <div class="process-timeline">
                    <div class="timeline-item" data-aos="fade-right">
                        <div class="timeline-number">1</div>
                        <div class="timeline-content">
                            <h3>Consulta Inicial</h3>
                            <p>Realizamos una evaluación completa de su situación financiera y fiscal actual para entender sus necesidades específicas.</p>
                            <ul>
                                <li>Análisis de estados financieros</li>
                                <li>Evaluación de estructura organizacional</li>
                                <li>Identificación de áreas de oportunidad</li>
                            </ul>
                        </div>
                    </div>
                    
                    <div class="timeline-item" data-aos="fade-left">
                        <div class="timeline-number">2</div>
                        <div class="timeline-content">
                            <h3>Propuesta Personalizada</h3>
                            <p>Desarrollamos una propuesta detallada con servicios y soluciones adaptadas específicamente a sus necesidades.</p>
                            <ul>
                                <li>Definición de alcance de servicios</li>
                                <li>Establecimiento de objetivos claros</li>
                                <li>Presentación de estrategias a implementar</li>
                            </ul>
                        </div>
                    </div>
                    
                    <div class="timeline-item" data-aos="fade-right">
                        <div class="timeline-number">3</div>
                        <div class="timeline-content">
                            <h3>Implementación</h3>
                            <p>Ejecutamos los servicios acordados siguiendo metodologías probadas y adaptadas a su empresa.</p>
                            <ul>
                                <li>Puesta en marcha del plan de acción</li>
                                <li>Integración con sus sistemas actuales</li>
                                <li>Capacitación a su equipo cuando es necesario</li>
                            </ul>
                        </div>
                    </div>
                    
                    <div class="timeline-item" data-aos="fade-left">
                        <div class="timeline-number">4</div>
                        <div class="timeline-content">
                            <h3>Seguimiento Continuo</h3>
                            <p>Realizamos monitoreo constante para asegurar que se cumplan los objetivos establecidos.</p>
                            <ul>
                                <li>Reportes periódicos de resultados</li>
                                <li>Reuniones de revisión programadas</li>
                                <li>Ajustes y optimizaciones según sea necesario</li>
                            </ul>
                        </div>
                    </div>
                    
                    <div class="timeline-item" data-aos="fade-right">
                        <div class="timeline-number">5</div>
                        <div class="timeline-content">
                            <h3>Mejora Continua</h3>
                            <p>Implementamos un enfoque de mejora continua para optimizar constantemente nuestros servicios.</p>
                            <ul>
                                <li>Evaluación periódica de resultados</li>
                                <li>Actualización de estrategias según cambios normativos</li>
                                <li>Implementación de nuevas tecnologías y metodologías</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Garantía de Calidad -->
        <section class="quality-section">
            <div class="container">
                <div class="quality-content">
                    <div class="quality-text" data-aos="fade-right">
                        <h2>Nuestra Garantía de Calidad</h2>
                        <p>En SolFis nos comprometemos a ofrecer servicios de la más alta calidad, cumpliendo los más altos estándares profesionales y éticos.</p>
                        
                        <div class="quality-points">
                            <div class="quality-point">
                                <div class="icon-container">
                                    <i class="fas fa-check-circle"></i>
                                </div>
                                <div class="point-text">
                                    <h4>Profesionalismo</h4>
                                    <p>Nuestro equipo está compuesto por profesionales altamente capacitados y con amplia experiencia en sus áreas.</p>
                                </div>
                            </div>
                            
                            <div class="quality-point">
                                <div class="icon-container">
                                    <i class="fas fa-check-circle"></i>
                                </div>
                                <div class="point-text">
                                    <h4>Confidencialidad</h4>
                                    <p>Manejamos su información con la más estricta confidencialidad y seguridad.</p>
                                </div>
                            </div>
                            
                            <div class="quality-point">
                                <div class="icon-container">
                                    <i class="fas fa-check-circle"></i>
                                </div>
                                <div class="point-text">
                                    <h4>Actualización Constante</h4>
                                    <p>Nos mantenemos al día con las últimas normativas y tecnologías para ofrecer siempre lo mejor.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="quality-image" data-aos="fade-left">
                        <img src="img/quality-assurance.jpg" alt="Garantía de Calidad SolFis">
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA Section -->
        <section class="process-cta">
            <div class="container">
                <div class="cta-content" data-aos="zoom-in">
                    <h2>¿Listo para comenzar?</h2>
                    <p>Contáctanos hoy mismo y descubre cómo podemos ayudarte a optimizar la gestión financiera y fiscal de tu empresa.</p>
                    <a href="contacto.php" class="btn-primary">
                        Solicitar Consulta
                        <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            </div>
        </section>
    </main>

    <!-- Footer -->
    <?php include $base_path . 'footer.html'; ?>

    <!-- Scripts -->
    <script src="js/main.js"></script>
    <script src="<?php echo $assets_path; ?>/js/components/nav.js"></script>
    <script src="<?php echo $assets_path; ?>/js/components/footer.js"></script>
    
    <!-- AOS - Animate On Scroll -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>
    <script>
        AOS.init({
            duration: 800,
            once: true,
            offset: 100
        });
    </script>
</body>
</html>