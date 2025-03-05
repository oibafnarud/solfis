<?php
$site_title = "Sobre Nosotros - Solfis";
$site_description = "Conozca nuestro equipo de profesionales contables y financieros en República Dominicana";
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
    <link rel="stylesheet" href="<?php echo $assets_path; ?>css/components/nav.css">
    <link rel="stylesheet" href="<?php echo $assets_path; ?>css/components/dropdown-menu.css">
    <link rel="stylesheet" href="<?php echo $assets_path; ?>css/components/footer.css">
    <link rel="stylesheet" href="<?php echo $assets_path; ?>css/components/about-us.css">
    <link rel="stylesheet" href="<?php echo $assets_path; ?>css/mobile-optimizations.css">
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- AOS - Animate on Scroll -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css">
</head>
<body>
    <!-- Navbar -->
    <?php include $base_path . 'navbar.html'; ?>

    <main>
        <!-- Hero Section -->
        <section class="about-hero">
            <div class="container">
                <div class="hero-content">
                    <h1 data-aos="fade-up">Sobre SolFis</h1>
                    <p data-aos="fade-up" data-aos-delay="100">Equipo profesional dedicado a su éxito financiero desde 2009</p>
                </div>
            </div>
        </section>

        <!-- Nuestra Historia -->
        <section class="about-story">
            <div class="container">
                <div class="about-grid">
                    <div class="about-content" data-aos="fade-right">
                        <h2>Nuestra Historia</h2>
                        <p>En SolFis, somos un equipo de profesionales apasionados y expertos en contabilidad, finanzas y servicios empresariales dedicados a ofrecer soluciones integrales que impulsen el crecimiento sostenible de nuestros clientes.</p>
                        
                        <p>Desde nuestra fundación en 2009, nos hemos comprometido a proporcionar servicios de la más alta calidad, adaptándonos a las necesidades cambiantes del mercado y manteniendo siempre un enfoque ético y transparente en todas nuestras operaciones.</p>
                        
                        <p>Nuestro objetivo es ser más que un proveedor de servicios; aspiramos a ser socios estratégicos de nuestros clientes, ayudándoles a navegar por los desafíos financieros con confianza y eficacia.</p>

                        <div class="story-stats">
                            <div class="stat-item">
                                <div class="stat-number">15+</div>
                                <div class="stat-label">Años de Experiencia</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-number">1500+</div>
                                <div class="stat-label">Clientes Satisfechos</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-number">98%</div>
                                <div class="stat-label">Tasa de Retención</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="about-image" data-aos="fade-left">
                        <img src="img/about/about-story.jpg" alt="Historia de SolFis">
                        <div class="experience-badge">
                            <span>Desde</span>
                            <span>2009</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Misión, Visión y Valores -->
        <section class="about-mvv">
            <div class="container">
                <div class="section-header" data-aos="fade-up">
                    <h2>Misión, Visión y Valores</h2>
                    <p>Los pilares que guían nuestro compromiso con la excelencia</p>
                </div>
                
                <div class="mvv-grid">
                    <!-- Misión y Visión en la primera fila -->
                    <div class="mvv-row">
                        <!-- Misión -->
                        <div class="mvv-card" data-aos="fade-up">
                            <div class="mvv-icon">
                                <i class="fas fa-bullseye"></i>
                            </div>
                            <h3>Misión</h3>
                            <p>Nuestra misión en SolFis es empoderar a las empresas y empresarios a alcanzar su máximo potencial mediante servicios contables y financieros de primer nivel. Nos esforzamos por ofrecer soluciones personalizadas que aseguren la salud financiera y fomenten el crecimiento de nuestros clientes, basándonos en la precisión, la integridad y la innovación continua.</p>
                        </div>
                        
                        <!-- Visión -->
                        <div class="mvv-card" data-aos="fade-up" data-aos-delay="100">
                            <div class="mvv-icon">
                                <i class="fas fa-eye"></i>
                            </div>
                            <h3>Visión</h3>
                            <p>Nuestra visión es ser líderes en el sector de contabilidad y servicios financieros en la región, reconocidos por nuestra capacidad para innovar y adaptarnos a las dinámicas del mercado. Aspiramos a establecer estándares de excelencia en el servicio que ayuden a nuestros clientes a superar sus expectativas de éxito, garantizando una relación de confianza y colaboración a largo plazo.</p>
                        </div>
                    </div>
                    
                    <!-- Valores en la segunda fila -->
                    <div class="values-row" data-aos="fade-up" data-aos-delay="200">
                        <div class="mvv-card values-card">
                            <div class="mvv-icon">
                                <i class="fas fa-heart"></i>
                            </div>
                            <h3>Valores</h3>
                            <div class="values-columns">
                                <ul class="values-list">
                                    <li>
                                        <span class="value-name">Integridad</span>
                                        <p>Nos adherimos a los más altos estándares éticos en todas nuestras acciones y decisiones, asegurando transparencia y honestidad en nuestra relación con clientes y colaboradores.</p>
                                    </li>
                                    <li>
                                        <span class="value-name">Compromiso</span>
                                        <p>Estamos comprometidos con el éxito de nuestros clientes y trabajamos incansablemente para ofrecer soluciones que no solo satisfagan, sino que superen sus expectativas.</p>
                                    </li>
                                    <li>
                                        <span class="value-name">Innovación</span>
                                        <p>Creemos en la mejora y la innovación constante como pilares fundamentales para ofrecer servicios que anticipen y adapten a las necesidades del mercado.</p>
                                    </li>
                                </ul>
                                <ul class="values-list">
                                    <li>
                                        <span class="value-name">Excelencia</span>
                                        <p>Nos esforzamos por la excelencia en cada aspecto de nuestro trabajo, asegurando calidad y precisión en cada servicio que ofrecemos.</p>
                                    </li>
                                    <li>
                                        <span class="value-name">Colaboración</span>
                                        <p>Fomentamos un ambiente de trabajo colaborativo tanto internamente entre nuestros empleados como externamente con nuestros clientes y socios.</p>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Nuestro Equipo -->
        <section class="about-team">
            <div class="container">
                <div class="section-header" data-aos="fade-up">
                    <h2>Nuestro Equipo Directivo</h2>
                    <p>Profesionales comprometidos con la excelencia en cada proyecto</p>
                </div>
                
                <div class="team-grid">
                    <!-- Primera fila: 2 miembros -->
                    <div class="team-row">
                        <!-- Miembro 1 -->
                        <div class="team-member" data-aos="fade-up">
                            <div class="member-photo">
                                <img src="img/team/member1.jpg" alt="Zuleiny Reyes">
                                <div class="member-social">
                                    <a href="#" class="social-link"><i class="fab fa-linkedin-in"></i></a>
                                    <a href="#" class="social-link"><i class="fab fa-twitter"></i></a>
                                    <a href="#" class="social-link"><i class="far fa-envelope"></i></a>
                                </div>
                            </div>
                            <div class="member-info">
                                <h3>Zuly Reyes</h3>
                                <span class="member-position">Directora General - Founder</span>
                                <p>Contador Público Autorizado con más de 20 años de experiencia en asesoría financiera y estratégica para empresas de diversos sectores.</p>
                            </div>
                        </div>
                        
                        <!-- Miembro 2 -->
                        <div class="team-member" data-aos="fade-up" data-aos-delay="100">
                            <div class="member-photo">
                                <img src="img/team/member2.jpg" alt="Jennifer Guerra">
                                <div class="member-social">
                                    <a href="#" class="social-link"><i class="fab fa-linkedin-in"></i></a>
                                    <a href="#" class="social-link"><i class="fab fa-twitter"></i></a>
                                    <a href="#" class="social-link"><i class="far fa-envelope"></i></a>
                                </div>
                            </div>
                            <div class="member-info">
                                <h3>Jennifer Guerra</h3>
                                <span class="member-position">Directora de Contabilidad</span>
                                <p>Especialista en contabilidad corporativa y auditoría interna con amplia experiencia en implementación de sistemas contables eficientes.</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Segunda fila: 2 miembros -->
                    <div class="team-row">
                        <!-- Miembro 3 -->
                        <div class="team-member" data-aos="fade-up" data-aos-delay="200">
                            <div class="member-photo">
                                <img src="img/team/member3.jpg" alt="Fabio Duran">
                                <div class="member-social">
                                    <a href="#" class="social-link"><i class="fab fa-linkedin-in"></i></a>
                                    <a href="#" class="social-link"><i class="fab fa-twitter"></i></a>
                                    <a href="#" class="social-link"><i class="far fa-envelope"></i></a>
                                </div>
                            </div>
                            <div class="member-info">
                                <h3>Fabio Durán</h3>
                                <span class="member-position">Director de Tecnología - Co-Founder</span>
                                <p>Ingeniero con especialización en sistemas financieros y amplia experiencia en implementación de soluciones tecnológicas para el sector contable. Experto en optimización de procesos, seguridad de datos y cumplimiento normativo.</p>
                            </div>
                        </div>
                        
                        <!-- Miembro 4 -->
                        <div class="team-member" data-aos="fade-up" data-aos-delay="300">
                            <div class="member-photo">
                                <img src="img/team/member4.jpg" alt="Laura Martínez">
                                <div class="member-social">
                                    <a href="#" class="social-link"><i class="fab fa-linkedin-in"></i></a>
                                    <a href="#" class="social-link"><i class="fab fa-twitter"></i></a>
                                    <a href="#" class="social-link"><i class="far fa-envelope"></i></a>
                                </div>
                            </div>
                            <div class="member-info">
                                <h3>Laura Martínez</h3>
                                <span class="member-position">Directora de Servicios Fiscales</span>
                                <p>Especialista en planificación fiscal y cumplimiento tributario con profundo conocimiento de la legislación dominicana y tratados internacionales.</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="team-cta" data-aos="fade-up">
                    <h3>¿Listo para trabajar con nuestro equipo?</h3>
                    <a href="contacto.php" class="btn btn-primary">Contáctenos Hoy</a>
                </div>
            </div>
        </section>

        <!-- Certificaciones -->
        <section class="about-certifications">
            <div class="container">
                <div class="section-header" data-aos="fade-up">
                    <h2>Nuestras Certificaciones</h2>
                    <p>Comprometidos con los más altos estándares de calidad y profesionalismo</p>
                </div>
                
                <div class="certifications-grid">
                    <div class="certification-item" data-aos="fade-up">
                        <img src="img/certifications/cert1.png" alt="Certificación ICPARD">
                        <h3>Instituto de Contadores Públicos</h3>
                        <p>Miembros activos del Instituto de Contadores Públicos Autorizados de la República Dominicana.</p>
                    </div>
                    
                    <div class="certification-item" data-aos="fade-up" data-aos-delay="100">
                        <img src="img/certifications/cert2.png" alt="Certificación ISO">
                        <h3>ISO 9001:2015</h3>
                        <p>Certificados en Sistema de Gestión de Calidad, garantizando procesos eficientes y orientados al cliente.</p>
                    </div>
                    
                    <div class="certification-item" data-aos="fade-up" data-aos-delay="200">
                        <img src="img/certifications/cert3.png" alt="Certificación DGII">
                        <h3>Dirección General de Impuestos Internos</h3>
                        <p>Reconocidos como proveedores autorizados de servicios fiscales por la DGII.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA Final -->
        <section class="about-cta">
            <div class="container">
                <div class="cta-content" data-aos="fade-up">
                    <h2>Transformamos Desafíos en Oportunidades</h2>
                    <p>Permítanos ayudarle a alcanzar sus objetivos financieros con soluciones personalizadas y efectivas</p>
                    <div class="cta-buttons">
                        <a href="contacto.php" class="btn btn-primary">
                            Solicitar Consulta
                            <i class="fas fa-arrow-right"></i>
                        </a>
                        <a href="servicios.php" class="btn btn-secondary">
                            Explorar Servicios
                        </a>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- Footer -->
    <?php include $base_path . 'footer.html'; ?>

    <!-- Scripts -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>
    <script src="/js/main.js"></script>
    <script src="<?php echo $assets_path; ?>js/components/nav.js"></script>
    <script src="<?php echo $assets_path; ?>js/components/footer.js"></script>
    
    <!-- Inicialización de AOS -->
    <script>
        AOS.init({
            duration: 800,
            once: true,
            offset: 50
        });
    </script>
</body>
</html>