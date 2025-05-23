<?php
$site_title = "Aplicar a Vacante - SolFis";
$site_description = "Formulario de aplicación para vacantes en SolFis";
$base_path = '../sections/';
$assets_path = '../assets/';

// Incluir el sistema de vacantes
require_once '../includes/jobs-system.php';

// Verificar si se proporcionó un ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: index.php');
    exit;
}

// Obtener ID de la vacante
$id = (int)$_GET['id'];

// Instanciar gestores
$vacancyManager = new VacancyManager();
$applicationManager = new ApplicationManager();
$candidateManager = new CandidateManager();

// Obtener vacante por ID
$vacante = $vacancyManager->getVacancyById($id);

// Si la vacante no existe o no está publicada, redirigir
if (!$vacante || $vacante['estado'] !== 'publicada') {
    header('Location: index.php');
    exit;
}

// Procesar formulario
$success = false;
$error = '';
$formData = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validar datos requeridos
    $required_fields = ['nombre', 'apellido', 'email', 'telefono'];
    $formData = $_POST;
    $is_valid = true;
    
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            $is_valid = false;
            $error = 'Por favor complete todos los campos obligatorios.';
            break;
        }
    }
    
    // Validar email
    if ($is_valid && !filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
        $is_valid = false;
        $error = 'Por favor ingrese un email válido.';
    }
    
    // Validar CV
    if ($is_valid && (!isset($_FILES['cv']) || $_FILES['cv']['error'] !== UPLOAD_ERR_OK)) {
        $is_valid = false;
        $error = 'Por favor adjunte su CV.';
    } elseif ($is_valid) {
        // Verificar tipo de archivo
        $allowed_types = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
        $file_type = $_FILES['cv']['type'];
        
        if (!in_array($file_type, $allowed_types)) {
            $is_valid = false;
            $error = 'Solo se permiten archivos PDF, DOC o DOCX.';
        }
        
        // Verificar tamaño (max 5MB)
        $max_size = 5 * 1024 * 1024; // 5MB
        if ($_FILES['cv']['size'] > $max_size) {
            $is_valid = false;
            $error = 'El archivo excede el tamaño máximo permitido (5MB).';
        }
    }
    
    // Validar términos y condiciones
    if ($is_valid && empty($_POST['terminos'])) {
        $is_valid = false;
        $error = 'Debe aceptar los términos y condiciones.';
    }
    
    // Si todo está correcto, procesar la aplicación
    if ($is_valid) {
        // 1. Verificar si el candidato ya existe
        $candidateResult = $candidateManager->findCandidateByEmail($_POST['email']);
        
        if ($candidateResult['success'] && $candidateResult['exists']) {
            $candidato_id = $candidateResult['candidate']['id'];
        } else {
            // 2. Crear nuevo candidato
            
            // Procesar CV
            $cv_filename = '';
            if ($_FILES['cv']['error'] === UPLOAD_ERR_OK) {
                $tmp_name = $_FILES['cv']['tmp_name'];
                $name = basename($_FILES['cv']['name']);
                $extension = pathinfo($name, PATHINFO_EXTENSION);
                
                // Generar nombre único
                $cv_filename = uniqid() . '_' . time() . '.' . $extension;
                
                // Asegurarse de que el directorio existe
                $upload_dir = '../uploads/resumes/';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                
                // Mover archivo
                move_uploaded_file($tmp_name, $upload_dir . $cv_filename);
            }
            
            // Datos del candidato
            $candidatoData = [
                'nombre' => $_POST['nombre'],
                'apellido' => $_POST['apellido'],
                'email' => $_POST['email'],
                'telefono' => $_POST['telefono'],
                'ubicacion' => $_POST['ubicacion'] ?? '',
                'linkedin' => $_POST['linkedin'] ?? '',
                'cv_path' => $cv_filename
            ];
            
            // Crear candidato
            $createResult = $candidateManager->createCandidate($candidatoData);
            
            if (!$createResult['success']) {
                $is_valid = false;
                $error = 'Error al procesar su aplicación. Por favor intente nuevamente.';
            } else {
                $candidato_id = $createResult['id'];
            }
        }
        
        // 3. Crear aplicación
        if ($is_valid) {
            $aplicacionData = [
                'vacante_id' => $id,
                'candidato_id' => $candidato_id,
                'carta_presentacion' => $_POST['carta_presentacion'] ?? '',
                'experiencia' => $_POST['experiencia'] ?? '',
                'empresa_actual' => $_POST['empresa_actual'] ?? '',
                'cargo_actual' => $_POST['cargo_actual'] ?? '',
                'salario_esperado' => $_POST['salario_esperado'] ?? '',
                'disponibilidad' => $_POST['disponibilidad'] ?? '',
                'fuente' => $_POST['fuente'] ?? ''
            ];
            
            $applicationResult = $applicationManager->createApplication($aplicacionData);
            
            if ($applicationResult['success']) {
                $success = true;
            } else {
                $error = 'Error al enviar su aplicación. Por favor intente nuevamente.';
            }
        }
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
    
    <!-- CSS -->
    <link rel="stylesheet" href="<?php echo $assets_path; ?>css/normalize.css">
    <link rel="stylesheet" href="<?php echo $assets_path; ?>css/main.css">
    <link rel="stylesheet" href="<?php echo $assets_path; ?>css/components/nav.css">
    <link rel="stylesheet" href="<?php echo $assets_path; ?>css/components/dropdown-menu.css">
    <link rel="stylesheet" href="<?php echo $assets_path; ?>css/components/footer.css">
	<link rel="stylesheet" href="<?php echo $assets_path; ?>css/vacantes-base.css">
    <link rel="stylesheet" href="<?php echo $assets_path; ?>css/vacantes-aplicar.css">
    
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
        <section class="job-application">
            <div class="container">
                <div class="breadcrumbs" data-aos="fade-up">
                    <a href="../index.php">Inicio</a> <span class="separator">/</span>
                    <a href="index.php">Vacantes</a> <span class="separator">/</span>
                    <a href="detalle.php?id=<?php echo $vacante['id']; ?>"><?php echo htmlspecialchars($vacante['titulo']); ?></a> <span class="separator">/</span>
                    <span class="current">Aplicar</span>
                </div>
                
                <div class="application-header" data-aos="fade-up">
                    <h1>Aplicar para: <?php echo htmlspecialchars($vacante['titulo']); ?></h1>
                    <p>Complete el siguiente formulario para enviar su solicitud. Todos los campos marcados con <span class="required-mark">*</span> son obligatorios.</p>
                </div>
                
                <?php if ($success): ?>
                <div class="alert alert-success" data-aos="fade-up">
                    <i class="fas fa-check-circle"></i>
                    <div>
                        <h3>¡Aplicación enviada con éxito!</h3>
                        <p>Gracias por tu interés en trabajar con nosotros. Hemos recibido tu aplicación para la posición de <?php echo htmlspecialchars($vacante['titulo']); ?>.</p>
                        <p>Revisaremos tu información y nos pondremos en contacto contigo pronto.</p>
                        <div class="alert-actions">
                            <a href="index.php" class="btn-primary">Volver a Vacantes</a>
                        </div>
                    </div>
                </div>
                <?php elseif ($error): ?>
                <div class="alert alert-danger" data-aos="fade-up">
                    <i class="fas fa-exclamation-circle"></i>
                    <div>
                        <h3>Ha ocurrido un error</h3>
                        <p><?php echo $error; ?></p>
                        <p>Por favor revisa la información e intenta nuevamente.</p>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if (!$success): ?>
                <div class="application-layout" data-aos="fade-up">
                    <div class="application-main">
                        <form action="aplicar.php?id=<?php echo $id; ?>" method="POST" enctype="multipart/form-data" class="application-form" id="application-form">
                            <!-- Información Personal -->
                            <div class="form-section">
                                <h3>Información Personal</h3>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="nombre" class="form-label">Nombre <span class="required-mark">*</span></label>
                                        <input type="text" id="nombre" name="nombre" class="form-control" value="<?php echo htmlspecialchars($formData['nombre'] ?? ''); ?>" required>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="apellido" class="form-label">Apellido <span class="required-mark">*</span></label>
                                        <input type="text" id="apellido" name="apellido" class="form-control" value="<?php echo htmlspecialchars($formData['apellido'] ?? ''); ?>" required>
                                    </div>
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="email" class="form-label">Email <span class="required-mark">*</span></label>
                                        <input type="email" id="email" name="email" class="form-control" value="<?php echo htmlspecialchars($formData['email'] ?? ''); ?>" required>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="telefono" class="form-label">Teléfono <span class="required-mark">*</span></label>
                                        <input type="tel" id="telefono" name="telefono" class="form-control" value="<?php echo htmlspecialchars($formData['telefono'] ?? ''); ?>" required>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="ubicacion" class="form-label">Ubicación</label>
     <input type="text" id="ubicacion" name="ubicacion" class="form-control" value="<?php echo htmlspecialchars($formData['ubicacion'] ?? ''); ?>" placeholder="Ej: Santo Domingo, República Dominicana">
                                </div>
                                
                                <div class="form-group">
                                    <label for="linkedin" class="form-label">Perfil de LinkedIn</label>
                                    <input type="url" id="linkedin" name="linkedin" class="form-control" value="<?php echo htmlspecialchars($formData['linkedin'] ?? ''); ?>" placeholder="https://www.linkedin.com/in/tu-perfil">
                                </div>
                            </div>
                            
                            <!-- CV y Carta de Presentación -->
                            <div class="form-section">
                                <h3>Documentos</h3>
                                
                                <div class="form-group">
                                    <label for="cv" class="form-label">Curriculum Vitae (CV) <span class="required-mark">*</span></label>
                                    <div class="file-upload-container">
                                        <input type="file" id="cv" name="cv" accept=".pdf,.doc,.docx" required>
                                        <div class="file-upload-icon">
                                            <i class="fas fa-cloud-upload-alt"></i>
                                        </div>
                                        <div class="file-upload-text">
                                            <span id="file-name">Arrastra y suelta tu CV o haz clic para seleccionar</span>
                                        </div>
                                        <div class="file-format-text">
                                            Formatos aceptados: PDF, DOC, DOCX (Máx: 5MB)
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="carta_presentacion" class="form-label">Carta de Presentación</label>
                                    <textarea id="carta_presentacion" name="carta_presentacion" class="form-control" rows="5" placeholder="Cuéntanos por qué estás interesado en esta posición y por qué serías un buen candidato"><?php echo htmlspecialchars($formData['carta_presentacion'] ?? ''); ?></textarea>
                                </div>
                            </div>
                            
                            <!-- Experiencia Laboral -->
                            <div class="form-section">
                                <h3>Experiencia Laboral</h3>
                                
                                <div class="form-group">
                                    <label for="experiencia" class="form-label">Años de experiencia relacionada</label>
                                    <select id="experiencia" name="experiencia" class="form-control">
                                        <option value="" <?php echo !isset($formData['experiencia']) || $formData['experiencia'] === '' ? 'selected' : ''; ?>>Selecciona una opción</option>
                                        <option value="menos-1" <?php echo isset($formData['experiencia']) && $formData['experiencia'] === 'menos-1' ? 'selected' : ''; ?>>Menos de 1 año</option>
                                        <option value="1-3" <?php echo isset($formData['experiencia']) && $formData['experiencia'] === '1-3' ? 'selected' : ''; ?>>1-3 años</option>
                                        <option value="3-5" <?php echo isset($formData['experiencia']) && $formData['experiencia'] === '3-5' ? 'selected' : ''; ?>>3-5 años</option>
                                        <option value="5-10" <?php echo isset($formData['experiencia']) && $formData['experiencia'] === '5-10' ? 'selected' : ''; ?>>5-10 años</option>
                                        <option value="mas-10" <?php echo isset($formData['experiencia']) && $formData['experiencia'] === 'mas-10' ? 'selected' : ''; ?>>Más de 10 años</option>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label for="empresa_actual" class="form-label">Empresa actual o más reciente</label>
                                    <input type="text" id="empresa_actual" name="empresa_actual" class="form-control" value="<?php echo htmlspecialchars($formData['empresa_actual'] ?? ''); ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label for="cargo_actual" class="form-label">Cargo actual o más reciente</label>
                                    <input type="text" id="cargo_actual" name="cargo_actual" class="form-control" value="<?php echo htmlspecialchars($formData['cargo_actual'] ?? ''); ?>">
                                </div>
                            </div>
                            
                            <!-- Información Adicional -->
                            <div class="form-section">
                                <h3>Información Adicional</h3>
                                
                                <div class="form-group">
                                    <label for="salario_esperado" class="form-label">Expectativa salarial (RD$)</label>
                                    <input type="text" id="salario_esperado" name="salario_esperado" class="form-control" value="<?php echo htmlspecialchars($formData['salario_esperado'] ?? ''); ?>" placeholder="Ej: RD$ 60,000 mensuales">
                                </div>
                                
                                <div class="form-group">
                                    <label for="disponibilidad" class="form-label">Disponibilidad para comenzar</label>
                                    <select id="disponibilidad" name="disponibilidad" class="form-control">
                                        <option value="" <?php echo !isset($formData['disponibilidad']) || $formData['disponibilidad'] === '' ? 'selected' : ''; ?>>Selecciona una opción</option>
                                        <option value="inmediata" <?php echo isset($formData['disponibilidad']) && $formData['disponibilidad'] === 'inmediata' ? 'selected' : ''; ?>>Inmediata</option>
                                        <option value="2-semanas" <?php echo isset($formData['disponibilidad']) && $formData['disponibilidad'] === '2-semanas' ? 'selected' : ''; ?>>2 semanas</option>
                                        <option value="1-mes" <?php echo isset($formData['disponibilidad']) && $formData['disponibilidad'] === '1-mes' ? 'selected' : ''; ?>>1 mes</option>
                                        <option value="mas-1-mes" <?php echo isset($formData['disponibilidad']) && $formData['disponibilidad'] === 'mas-1-mes' ? 'selected' : ''; ?>>Más de 1 mes</option>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">¿Cómo te enteraste de esta vacante?</label>
                                    <div class="checkbox-group">
                                        <input type="radio" id="fuente_web" name="fuente" value="web" <?php echo isset($formData['fuente']) && $formData['fuente'] === 'web' ? 'checked' : ''; ?>>
                                        <label for="fuente_web">Sitio web de SolFis</label>
                                    </div>
                                    <div class="checkbox-group">
                                        <input type="radio" id="fuente_linkedin" name="fuente" value="linkedin" <?php echo isset($formData['fuente']) && $formData['fuente'] === 'linkedin' ? 'checked' : ''; ?>>
                                        <label for="fuente_linkedin">LinkedIn</label>
                                    </div>
                                    <div class="checkbox-group">
                                        <input type="radio" id="fuente_referencia" name="fuente" value="referencia" <?php echo isset($formData['fuente']) && $formData['fuente'] === 'referencia' ? 'checked' : ''; ?>>
                                        <label for="fuente_referencia">Referencia de un empleado</label>
                                    </div>
                                    <div class="checkbox-group">
                                        <input type="radio" id="fuente_otro" name="fuente" value="otro" <?php echo isset($formData['fuente']) && $formData['fuente'] === 'otro' ? 'checked' : ''; ?>>
                                        <label for="fuente_otro">Otro</label>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Términos y Condiciones -->
                            <div class="form-section">
                                <div class="form-group">
                                    <div class="checkbox-group">
                                        <input type="checkbox" id="terminos" name="terminos" value="1" <?php echo isset($formData['terminos']) ? 'checked' : ''; ?> required>
                                        <label for="terminos">Acepto los <a href="../terminos.php" target="_blank">términos y condiciones</a> y la <a href="../privacidad.php" target="_blank">política de privacidad</a> <span class="required-mark">*</span></label>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <div class="checkbox-group">
                                        <input type="checkbox" id="subscribe" name="subscribe" value="1" <?php echo isset($formData['subscribe']) ? 'checked' : ''; ?>>
                                        <label for="subscribe">Me gustaría recibir notificaciones de nuevas vacantes y oportunidades profesionales en SolFis</label>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-buttons">
                                <button type="submit" class="btn-primary">
                                    <i class="fas fa-paper-plane"></i> Enviar Aplicación
                                </button>
                                <a href="detalle.php?id=<?php echo $id; ?>" class="btn-secondary">Cancelar</a>
                            </div>
                        </form>
                    </div>
                    
                    <div class="application-sidebar">
                        <div class="job-sidebar-card">
                            <h3>Resumen de la Vacante</h3>
                            <div class="job-summary">
                                <div class="job-summary-item">
                                    <span class="job-summary-label">Posición</span>
                                    <span class="job-summary-value"><?php echo htmlspecialchars($vacante['titulo']); ?></span>
                                </div>
                                <div class="job-summary-item">
                                    <span class="job-summary-label">Categoría</span>
                                    <span class="job-summary-value"><?php echo htmlspecialchars($vacante['categoria_nombre']); ?></span>
                                </div>
                                <div class="job-summary-item">
                                    <span class="job-summary-label">Ubicación</span>
                                    <span class="job-summary-value"><?php echo htmlspecialchars($vacante['ubicacion']); ?></span>
                                </div>
                                <div class="job-summary-item">
                                    <span class="job-summary-label">Modalidad</span>
                                    <span class="job-summary-value"><?php echo ucfirst(htmlspecialchars($vacante['modalidad'])); ?></span>
                                </div>
                                <div class="job-summary-item">
                                    <span class="job-summary-label">Tipo de Contrato</span>
                                    <span class="job-summary-value"><?php echo ucfirst(str_replace('_', ' ', htmlspecialchars($vacante['tipo_contrato']))); ?></span>
                                </div>
                                <?php if (!empty($vacante['experiencia'])): ?>
                                <div class="job-summary-item">
                                    <span class="job-summary-label">Experiencia</span>
                                    <span class="job-summary-value"><?php echo htmlspecialchars($vacante['experiencia']); ?></span>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="job-sidebar-card">
                            <h3>Tips para tu Aplicación</h3>
                            <ul class="tips-list">
                                <li>
                                    <i class="fas fa-check-circle"></i>
                                    <span>Asegúrate de que tu CV esté actualizado y adaptado a la posición.</span>
                                </li>
                                <li>
                                    <i class="fas fa-check-circle"></i>
                                    <span>En tu carta de presentación, destaca experiencias relevantes para esta vacante.</span>
                                </li>
                                <li>
                                    <i class="fas fa-check-circle"></i>
                                    <span>Sé honesto con tu experiencia y habilidades.</span>
                                </li>
                                <li>
                                    <i class="fas fa-check-circle"></i>
                                    <span>Revisa tu CV en busca de errores antes de enviarlo.</span>
                                </li>
                                <li>
                                    <i class="fas fa-check-circle"></i>
                                    <span>Si tienes preguntas, no dudes en contactarnos a <a href="mailto:rrhh@solfis.com.do">rrhh@solfis.com.do</a></span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </section>
    </main>

    <!-- Footer -->
    <?php include $base_path . 'footer.html'; ?>

    <!-- Scripts -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>
    <script src="../js/main.js"></script>
    <script src="<?php echo $assets_path; ?>js/components/nav.js"></script>
    <script src="<?php echo $assets_path; ?>js/components/footer.js"></script>
    <script src="assets/js/vacantes.js"></script>
    <script>
        AOS.init({
            duration: 800,
            easing: 'ease-in-out',
            once: true
        });

        // Manejo del campo de subida de archivo
        document.getElementById('cv')?.addEventListener('change', function(e) {
            const fileName = e.target.files[0]?.name || 'Arrastra y suelta tu CV o haz clic para seleccionar';
            document.getElementById('file-name').textContent = fileName;
        });
        
        // Validación del formulario
        document.getElementById('application-form')?.addEventListener('submit', function(e) {
            let valid = true;
            
            // Validar campos requeridos
            const requiredFields = this.querySelectorAll('[required]');
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    valid = false;
                    field.classList.add('is-invalid');
                } else {
                    field.classList.remove('is-invalid');
                }
            });
            
            // Validar email
            const emailField = this.querySelector('#email');
            if (emailField && emailField.value.trim()) {
                const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailPattern.test(emailField.value.trim())) {
                    valid = false;
                    emailField.classList.add('is-invalid');
                }
            }
            
            // Validar CV (tamaño y formato)
            const cvField = this.querySelector('#cv');
            if (cvField && cvField.files.length > 0) {
                const file = cvField.files[0];
                const allowedTypes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
                const maxSize = 5 * 1024 * 1024; // 5MB
                
                if (!allowedTypes.includes(file.type)) {
                    valid = false;
                    cvField.classList.add('is-invalid');
                    alert('El formato del CV no es válido. Por favor, sube un archivo PDF, DOC o DOCX.');
                } else if (file.size > maxSize) {
                    valid = false;
                    cvField.classList.add('is-invalid');
                    alert('El tamaño del CV excede el límite de 5MB.');
                }
            }
            
            if (!valid) {
                e.preventDefault();
            }
        });
    </script>
</body>
</html>