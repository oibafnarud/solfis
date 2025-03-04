<?php
$site_title = "Contacto - Solfis";
$site_description = "Contáctenos para servicios contables, financieros y empresariales en República Dominicana";
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
    <link rel="stylesheet" href="<?php echo $assets_path; ?>css/components/nav.css">
    <link rel="stylesheet" href="<?php echo $assets_path; ?>css/components/dropdown-menu.css">
    <link rel="stylesheet" href="<?php echo $assets_path; ?>css/components/footer.css">
    <link rel="stylesheet" href="<?php echo $assets_path; ?>css/components/contact-section.css">
    <link rel="stylesheet" href="<?php echo $assets_path; ?>css/text-contrast-fixes.css">
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <!-- Navbar -->
    <?php include $base_path . 'navbar.html'; ?>

    <main>
        <!-- Hero Section -->
        <section class="page-hero contact-hero">
            <div class="container">
                <div class="hero-content">
                    <h1>Contáctenos</h1>
                    <p>Estamos listos para ayudarle con sus necesidades financieras y empresariales</p>
                </div>
            </div>
        </section>

        <!-- Contact Form Section -->
        <section class="contact-main">
            <div class="container">
                <div class="contact-grid">
                    <!-- Info Panel -->
                    <div class="info-panel">
                        <h3>Información de Contacto</h3>
                        <p>Comuníquese con nosotros y descubra cómo podemos ayudar a su empresa</p>

                        <div class="contact-info">
                            <div class="info-item">
                                <i class="fas fa-map-marker-alt"></i>
                                <div>
                                    <h4>Ubicación</h4>
                                    <p>Santo Domingo, República Dominicana</p>
                                    <p>Av. Winston Churchill #954</p>
                                </div>
                            </div>

                            <div class="info-item">
                                <i class="fas fa-phone-alt"></i>
                                <div>
                                    <h4>Teléfonos</h4>
                                    <p>+1 (809) 555-0123</p>
                                    <p>+1 (809) 555-0124</p>
                                </div>
                            </div>

                            <div class="info-item">
                                <i class="fas fa-envelope"></i>
                                <div>
                                    <h4>Email</h4>
                                    <p>contacto@solfis.com.do</p>
                                    <p>info@solfis.com.do</p>
                                </div>
                            </div>
                        </div>

                        <div class="hours">
                            <div class="hours-item">
                                <h4>Lunes a Viernes</h4>
                                <p>8:00 AM - 6:00 PM</p>
                            </div>
                            <div class="hours-item">
                                <h4>Sábados</h4>
                                <p>9:00 AM - 1:00 PM</p>
                            </div>
                        </div>

                        <div class="social-links">
                            <a href="#" class="social-link"><i class="fab fa-facebook-f"></i></a>
                            <a href="#" class="social-link"><i class="fab fa-linkedin-in"></i></a>
                            <a href="#" class="social-link"><i class="fab fa-instagram"></i></a>
                            <a href="#" class="social-link"><i class="fab fa-twitter"></i></a>
                        </div>
                    </div>

                    <!-- Form Panel -->
                    <div class="form-panel">
                        <h3>Envíenos un Mensaje</h3>
                        <p>Complete el formulario y nos pondremos en contacto pronto</p>

                        <form id="contactForm">
                            <div class="form-grid">
                                <input type="text" placeholder="Nombre Completo" required>
                                <input type="text" placeholder="Empresa">
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

        <!-- Map Section -->
        <section class="map-section">
            <div class="container">
                <div class="section-header">
                    <h2>Nuestra Ubicación</h2>
                    <p>Visítenos en nuestras oficinas</p>
                </div>
                <div class="map-container">
                    <iframe 
                        src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d15100.927783291262!2d-69.9452941767635!3d18.4813441359723!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x8eaf89f1c52c9ae1%3A0x9f066a945a1c381f!2sSanto%20Domingo%2C%20Dominican%20Republic!5e0!3m2!1sen!2sus!4v1645740725036!5m2!1sen!2sus" 
                        width="100%" 
                        height="450" 
                        style="border:0;" 
                        allowfullscreen="" 
                        loading="lazy"
                        title="Ubicación de Solfis">
                    </iframe>
                </div>
            </div>
        </section>
    </main>

    <!-- Footer -->
    <?php include $base_path . 'footer.html'; ?>

    <!-- Scripts -->
    <script src="/js/main.js"></script>
    <script src="<?php echo $assets_path; ?>js/components/nav.js"></script>
    <script src="<?php echo $assets_path; ?>js/components/footer.js"></script>
    <script src="<?php echo $assets_path; ?>js/components/contact.js"></script>
</body>
</html>