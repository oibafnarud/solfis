<?php
/**
 * Formulario de contacto para SolFis
 * Este archivo debe guardarse como contacto.php
 */

// Configuración básica
$site_title = "Contacto - Solfis";
$site_description = "Contáctenos para servicios contables, financieros y empresariales en República Dominicana";
$base_path = 'sections/';
$assets_path = 'assets/';
$page_canonical = "https://solfis.com.do/contacto.php";

// Claves de reCAPTCHA (reemplaza con tus claves reales de Google reCAPTCHA)
$recaptcha_site_key = '6LeIxAcTAAAAAJcZVRqyHh71UMIEGNQ_MXjiZKhI'; // Clave de prueba
$recaptcha_secret_key = '6LeIxAcTAAAAAGG-vFI1TnRWxMZNFuojJ4WifJWe'; // Clave de prueba

// Incluir archivos necesarios
require_once 'config.php';
require_once 'includes/blog-system.php';

// Asegurarnos de que exista la clase Contact
if (!class_exists('Contact')) {
    if (file_exists('includes/contact-class.php')) {
        include_once 'includes/contact-class.php';
    }
}

// Variables para control de formulario
$success = false;
$error = null;
$formData = [
    'name' => '',
    'company' => '',
    'email' => '',
    'phone' => '',
    'service' => '',
    'message' => ''
];

// Procesar el formulario si se envió
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obtener datos y sanitizarlos
    $formData = [
        'name' => htmlspecialchars(trim($_POST['name'] ?? '')),
        'company' => htmlspecialchars(trim($_POST['company'] ?? '')),
        'email' => filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL),
        'phone' => htmlspecialchars(trim($_POST['phone'] ?? '')),
        'service' => htmlspecialchars(trim($_POST['service'] ?? '')),
        'message' => htmlspecialchars(trim($_POST['message'] ?? ''))
    ];
    
    // Validar campos obligatorios
    if (empty($formData['name']) || empty($formData['email']) || empty($formData['phone']) || empty($formData['message'])) {
        $error = 'Por favor, complete todos los campos obligatorios.';
    } 
    // Validar email
    elseif (!filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
        $error = 'Por favor, ingrese un correo electrónico válido.';
    }
    // Verificar honeypot (campo oculto para evitar spam)
    elseif (!empty($_POST['website'])) {
        // Simular éxito pero no hacer nada (es un bot)
        $success = true;
    }
    // Verificar reCAPTCHA si está habilitado
    elseif (isset($_POST['g-recaptcha-response'])) {
        $recaptcha_response = $_POST['g-recaptcha-response'];
        
        // Verificar con el servidor de Google
        $verify_response = file_get_contents('https://www.google.com/recaptcha/api/siteverify?secret=' . 
                                          $recaptcha_secret_key . '&response=' . $recaptcha_response);
        $response_data = json_decode($verify_response);
        
        if (!$response_data->success) {
            $error = 'Por favor, complete el captcha correctamente.';
        } else {
            // reCAPTCHA válido, continuar procesamiento
            processContactForm($formData);
        }
    } else {
        // Si reCAPTCHA no está habilitado, procesar directamente
        processContactForm($formData);
    }
}

/**
 * Procesar el formulario de contacto
 */
function processContactForm($formData) {
    global $success, $error;
    
    try {
        // Preparar datos para guardar en la base de datos
        $contactData = [
            'name' => $formData['name'],
            'email' => $formData['email'],
            'phone' => $formData['phone'],
            'subject' => 'Solicitud de información sobre ' . $formData['service'],
            'message' => "Mensaje de: " . $formData['name'] . "\n" . 
                         "Empresa: " . $formData['company'] . "\n" . 
                         "Servicio de interés: " . $formData['service'] . "\n\n" . 
                         $formData['message']
        ];
        
        // Verificar si existe la clase Contact
        if (class_exists('Contact')) {
            // Guardar mensaje en la base de datos
            $contact = new Contact();
            $messageId = $contact->saveMessage($contactData);
            
            if ($messageId) {
                // Intentar enviar email si existe el archivo email-sender.php
                if (file_exists('includes/email-sender.php')) {
                    try {
                        require_once 'includes/email-sender.php';
                        if (class_exists('EmailSender')) {
                            $emailSender = new EmailSender();
                            $emailSender->sendContactMessage($contactData);
                        }
                    } catch (Exception $e) {
                        // Si falla el envío de correo, no mostrar error al usuario
                        // porque el mensaje ya se guardó en la base de datos
                        error_log('Error al enviar correo: ' . $e->getMessage());
                    }
                }
                
                $success = true;
                // Limpiar formulario después de éxito
                $GLOBALS['formData'] = [
                    'name' => '',
                    'company' => '',
                    'email' => '',
                    'phone' => '',
                    'service' => '',
                    'message' => ''
                ];
            } else {
                $error = 'Hubo un problema al procesar su mensaje. Por favor, inténtelo de nuevo.';
            }
        } else {
            // Si no existe la clase Contact, crear un archivo de log
            $logFile = 'logs/contact_messages.txt';
            $logDir = dirname($logFile);
            
            // Crear directorio de logs si no existe
            if (!file_exists($logDir)) {
                mkdir($logDir, 0755, true);
            }
            
            // Crear mensaje de log
            $logMessage = date('Y-m-d H:i:s') . " - Mensaje de contacto\n" .
                         "Nombre: " . $contactData['name'] . "\n" .
                         "Email: " . $contactData['email'] . "\n" .
                         "Teléfono: " . $contactData['phone'] . "\n" .
                         "Empresa: " . $formData['company'] . "\n" .
                         "Servicio: " . $formData['service'] . "\n" .
                         "Mensaje: " . $contactData['message'] . "\n\n";
            
            // Añadir al archivo de log (o crearlo si no existe)
            file_put_contents($logFile, $logMessage, FILE_APPEND);
            
            // Simular éxito para no confundir al usuario
            $success = true;
            $GLOBALS['formData'] = [
                'name' => '',
                'company' => '',
                'email' => '',
                'phone' => '',
                'service' => '',
                'message' => ''
            ];
        }
    } catch (Exception $e) {
        $error = 'Error: ' . $e->getMessage();
    }
}
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
    
    <!-- reCAPTCHA -->
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    
    <style>
    /* Estilos adicionales para mensajes de éxito y error */
    .alert {
        padding: 15px;
        margin-bottom: 20px;
        border-radius: 4px;
    }
    .alert-success {
        background-color: #d4edda;
        border-left: 4px solid #28a745;
        color: #155724;
    }
    .alert-danger {
        background-color: #f8d7da;
        border-left: 4px solid #dc3545;
        color: #721c24;
    }
    .form-error {
        border-color: #dc3545 !important;
    }
    .honeypot {
        display: none !important;
    }
    .g-recaptcha {
        margin-bottom: 20px;
    }
    </style>
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

                        <?php if ($success): ?>
                            <div class="alert alert-success">
                                <h4><i class="fas fa-check-circle"></i> ¡Mensaje enviado!</h4>
                                <p>Gracias por contactarnos. Hemos recibido su mensaje y nos pondremos en contacto lo antes posible.</p>
                            </div>
                        <?php else: ?>
                            <?php if ($error): ?>
                                <div class="alert alert-danger">
                                    <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                                </div>
                            <?php endif; ?>

                            <form id="contactForm" method="post" action="">
                                <div class="form-grid">
                                    <input type="text" name="name" placeholder="Nombre Completo" required value="<?php echo $formData['name']; ?>">
                                    <input type="text" name="company" placeholder="Empresa" value="<?php echo $formData['company']; ?>">
                                </div>

                                <div class="form-grid">
                                    <input type="email" name="email" placeholder="Correo Electrónico" required value="<?php echo $formData['email']; ?>">
                                    <input type="tel" name="phone" placeholder="Teléfono" required value="<?php echo $formData['phone']; ?>">
                                </div>

                                <select name="service" required>
                                    <option value="" <?php echo empty($formData['service']) ? 'selected' : ''; ?>>Servicio de Interés</option>
                                    <option value="contabilidad" <?php echo $formData['service'] == 'contabilidad' ? 'selected' : ''; ?>>Contabilidad Integrada</option>
                                    <option value="auditoria" <?php echo $formData['service'] == 'auditoria' ? 'selected' : ''; ?>>Auditoría de Empresas</option>
                                    <option value="fiscal" <?php echo $formData['service'] == 'fiscal' ? 'selected' : ''; ?>>Servicios Fiscales</option>
                                    <option value="sistemas" <?php echo $formData['service'] == 'sistemas' ? 'selected' : ''; ?>>Sistemas de Gestión</option>
                                    <option value="empresarial" <?php echo $formData['service'] == 'empresarial' ? 'selected' : ''; ?>>Servicios Empresariales</option>
                                    <option value="fronteriza" <?php echo $formData['service'] == 'fronteriza' ? 'selected' : ''; ?>>Ley Fronteriza</option>
                                </select>

                                <textarea name="message" rows="5" placeholder="Mensaje" required><?php echo $formData['message']; ?></textarea>
                                
                                <!-- Campo honeypot (oculto para evitar spam) -->
                                <div class="honeypot">
                                    <input type="text" name="website" placeholder="Sitio web (dejar en blanco)">
                                </div>
                                
                                <!-- reCAPTCHA -->
                                <div class="g-recaptcha" data-sitekey="<?php echo $recaptcha_site_key; ?>"></div>

                                <button type="submit" class="submit-btn">
                                    Enviar Mensaje
                                    <i class="fas fa-paper-plane"></i>
                                </button>
                            </form>
                        <?php endif; ?>
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
    <script src="assets/js/main.js"></script>
    <script src="<?php echo $assets_path; ?>js/components/nav.js"></script>
    <script src="<?php echo $assets_path; ?>js/components/footer.js"></script>
    <script>
    // Validación del formulario
    document.addEventListener('DOMContentLoaded', function() {
        const contactForm = document.getElementById('contactForm');
        if (contactForm) {
            contactForm.addEventListener('submit', function(e) {
                let valid = true;
                const requiredFields = this.querySelectorAll('[required]');
                
                // Verificar campos requeridos
                requiredFields.forEach(field => {
                    if (!field.value.trim()) {
                        field.classList.add('form-error');
                        valid = false;
                    } else {
                        field.classList.remove('form-error');
                    }
                });
                
                // Verificar email
                const emailField = this.querySelector('input[type="email"]');
                if (emailField && emailField.value) {
                    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                    if (!emailRegex.test(emailField.value)) {
                        emailField.classList.add('form-error');
                        valid = false;
                    }
                }
                
                // Verificar reCAPTCHA
                if (typeof grecaptcha !== 'undefined') {
                    const recaptchaResponse = grecaptcha.getResponse();
                    if (recaptchaResponse.length === 0) {
                        valid = false;
                        alert('Por favor, complete el captcha.');
                    }
                }
                
                if (!valid) {
                    e.preventDefault();
                }
            });
        }
    });
    </script>
</body>
</html>