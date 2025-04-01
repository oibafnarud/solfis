<?php
/**
 * Portal de Vacantes SolFis
 * Página para aplicar a una vacante específica
 */

// Incluir archivos necesarios
require_once '../config.php';
require_once '../includes/blog-system.php';
require_once '../includes/jobs-system.php';

// Verificar autenticación
$auth = Auth::getInstance();
if (!$auth->isLoggedIn()) {
    header('Location: ../login.php?redirect=aplicar&vacancy_id=' . (isset($_GET['id']) ? $_GET['id'] : ''));
    exit;
}

// Inicializar las clases necesarias
$jobVacancy = new JobVacancy();
$jobQuestion = new JobVacancyQuestion();
$candidate = new Candidate();
$application = new JobApplication();

// Verificar que se haya proporcionado un ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$id = (int)$_GET['id'];

// Obtener datos de la vacante
$vacancy = $jobVacancy->getVacancyById($id);

// Si la vacante no existe o no está publicada, redirigir
if (!$vacancy || $vacancy['status'] !== 'published') {
    header('Location: index.php?error=vacancy-not-found');
    exit;
}

// Verificar si el usuario es un candidato
$candidateProfile = $candidate->getCandidateByUserId($auth->getUserId());

if (!$candidateProfile) {
    header('Location: ../perfil/cv.php?redirect=aplicar&vacancy_id=' . $id);
    exit;
}

// Verificar si ya ha aplicado a esta vacante
if ($application->hasApplied($candidateProfile['id'], $id)) {
    header('Location: detalle.php?id=' . $id . '&message=already-applied');
    exit;
}

// Obtener preguntas personalizadas para esta vacante
$questions = $jobQuestion->getQuestionsByVacancy($id);

// Procesar formulario de aplicación
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validar la carta de presentación
    $coverLetter = isset($_POST['cover_letter']) ? trim($_POST['cover_letter']) : '';
    
    if (empty($coverLetter)) {
        $errors[] = 'La carta de presentación es obligatoria.';
    } elseif (strlen($coverLetter) < 100) {
        $errors[] = 'La carta de presentación debe tener al menos 100 caracteres.';
    }
    
    // Validar respuestas a preguntas personalizadas
    $answers = [];
    
    foreach ($questions as $question) {
        $questionId = $question['id'];
        $answer = isset($_POST['question_' . $questionId]) ? trim($_POST['question_' . $questionId]) : '';
        
        if ($question['required'] && empty($answer)) {
            $errors[] = 'La pregunta "' . $question['question'] . '" es obligatoria.';
        }
        
        $answers[$questionId] = $answer;
    }
    
    // Procesar CV (opcional, usar el del perfil si no se proporciona uno nuevo)
    $resumePath = $candidateProfile['cv_path'];
    
    if (isset($_FILES['resume']) && $_FILES['resume']['error'] !== UPLOAD_ERR_NO_FILE) {
        $upload = $candidate->uploadResume($_FILES['resume'], $candidateProfile['id']);
        
        if ($upload['success']) {
            $resumePath = $upload['path'];
            
            // Actualizar CV en el perfil del candidato
            $candidate->updateCvPath($candidateProfile['id'], $resumePath);
        } else {
            $errors[] = 'Error al subir el CV: ' . $upload['message'];
        }
    }
    
    // Si no hay errores, procesar la aplicación
    if (empty($errors)) {
        $applicationData = [
            'vacancy_id' => $id,
            'candidate_id' => $candidateProfile['id'],
            'cover_letter' => $coverLetter,
            'resume_path' => $resumePath
        ];
        
        $applicationId = $application->createApplication($applicationData);
        
        if ($applicationId) {
            // Guardar respuestas a preguntas
            foreach ($answers as $questionId => $answer) {
                $application->saveAnswer($applicationId, $questionId, $answer);
            }
            
            // Incrementar contador de aplicaciones
            $jobVacancy->incrementApplications($id);
            
            // Marcar como éxito
            $success = true;
        } else {
            $errors[] = 'Ha ocurrido un error al procesar su aplicación. Por favor, intente de nuevo.';
        }
    }
}

// Definir título de la página
$pageTitle = 'Aplicar a: ' . $vacancy['title'] . ' - Vacantes SolFis';
?>

<?php include '../includes/header.php'; ?>

<!-- Cabecera -->
<section class="bg-primary text-white py-4">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-2">
                <li class="breadcrumb-item"><a href="../index.php" class="text-white">Inicio</a></li>
                <li class="breadcrumb-item"><a href="index.php" class="text-white">Vacantes</a></li>
                <li class="breadcrumb-item"><a href="detalle.php?id=<?php echo $id; ?>" class="text-white"><?php echo htmlspecialchars($vacancy['title']); ?></a></li>
                <li class="breadcrumb-item active text-white" aria-current="page">Aplicar</li>
            </ol>
        </nav>
        <h1 class="display-5 fw-bold">Aplicar a: <?php echo htmlspecialchars($vacancy['title']); ?></h1>
        <p class="lead">Complete el formulario a continuación para enviar su candidatura.</p>
    </div>
</section>

<!-- Contenido principal -->
<section class="py-5">
    <div class="container">
        <?php if ($success): ?>
            <div class="card mb-4 border-success">
                <div class="card-body text-center py-5">
                    <i class="fas fa-check-circle text-success fa-5x mb-3"></i>
                    <h2 class="card-title">¡Aplicación enviada con éxito!</h2>
                    <p class="card-text">Su aplicación para la vacante <strong><?php echo htmlspecialchars($vacancy['title']); ?></strong> ha sido recibida correctamente.</p>
                    <p class="card-text">Revisaremos su candidatura y nos pondremos en contacto con usted en caso de avanzar en el proceso.</p>
                    <div class="mt-4">
                        <a href="index.php" class="btn btn-primary me-2">Ver más vacantes</a>
                        <a href="../perfil/mis-aplicaciones.php" class="btn btn-outline-primary">Ver mis aplicaciones</a>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger mb-4">
                    <h5 class="alert-heading">Se encontraron errores en el formulario:</h5>
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo $error; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <!-- Información de la vacante -->
            <div class="row mb-4">
                <div class="col-md-8">
                    <div class="card mb-4">
                        <div class="card-header bg-light">
                            <h5 class="mb-0">Información de la vacante</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-8">
                                    <h4><?php echo htmlspecialchars($vacancy['title']); ?></h4>
                                    <p><?php echo htmlspecialchars($vacancy['department']); ?></p>
                                    
                                    <div class="vacancy-meta d-flex flex-wrap mb-3">
                                        <span class="badge bg-light text-dark me-2 mb-1">
                                            <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($vacancy['location']); ?>
                                        </span>
                                        <span class="badge bg-light text-dark me-2 mb-1">
                                            <i class="fas fa-laptop-house"></i> <?php echo ucfirst(htmlspecialchars($vacancy['work_mode'])); ?>
                                        </span>
                                        <span class="badge bg-light text-dark mb-1">
                                            <i class="fas fa-folder"></i> <?php echo htmlspecialchars($vacancy['category_name']); ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="col-md-4 text-md-end">
                                    <a href="detalle.php?id=<?php echo $id; ?>" class="btn btn-outline-primary">
                                        <i class="fas fa-eye"></i> Ver detalle completo
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card mb-4">
                        <div class="card-header bg-light">
                            <h5 class="mb-0">Perfil del candidato</h5>
                        </div>
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-3">
                                <div class="flex-shrink-0">
                                    <?php if (!empty($candidateProfile['image'])): ?>
                                        <img src="<?php echo $candidateProfile['image']; ?>" alt="<?php echo htmlspecialchars($_SESSION['user']['name']); ?>" class="rounded-circle" width="60" height="60">
                                    <?php else: ?>
                                        <div class="bg-secondary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                                            <i class="fas fa-user fa-lg"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h5 class="mb-1"><?php echo htmlspecialchars($_SESSION['user']['name']); ?></h5>
                                    <p class="mb-0 text-muted"><?php echo !empty($candidateProfile['headline']) ? htmlspecialchars($candidateProfile['headline']) : 'Candidato'; ?></p>
                                </div>
                            </div>
                            
                            <div class="profile-info">
                                <p class="mb-1"><i class="fas fa-envelope text-muted me-2"></i> <?php echo htmlspecialchars($_SESSION['user']['email']); ?></p>
                                <?php if (!empty($candidateProfile['phone'])): ?>
                                    <p class="mb-1"><i class="fas fa-phone text-muted me-2"></i> <?php echo htmlspecialchars($candidateProfile['phone']); ?></p>
                                <?php endif; ?>
                                <?php if (!empty($candidateProfile['location'])): ?>
                                    <p class="mb-0"><i class="fas fa-map-marker-alt text-muted me-2"></i> <?php echo htmlspecialchars($candidateProfile['city'] . ', ' . $candidateProfile['country']); ?></p>
                                <?php endif; ?>
                            </div>
                            
                            <hr>
                            
                            <div class="text-center">
                                <a href="../perfil/index.php" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-edit"></i> Editar perfil
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Formulario de aplicación -->
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Formulario de aplicación</h5>
                </div>
                <div class="card-body">
                    <form action="aplicar.php?id=<?php echo $id; ?>" method="post" enctype="multipart/form-data">
                        <!-- Carta de presentación -->
                        <div class="mb-4">
                            <label for="cover_letter" class="form-label">Carta de presentación <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="cover_letter" name="cover_letter" rows="6" required><?php echo isset($_POST['cover_letter']) ? htmlspecialchars($_POST['cover_letter']) : ''; ?></textarea>
                            <div class="form-text">
                                Explique brevemente por qué está interesado en esta posición y por qué considera que es un buen candidato. 
                                Incluya información relevante sobre su experiencia y habilidades que se relacionen con esta vacante.
                            </div>
                        </div>
                        
                        <!-- CV -->
                        <div class="mb-4">
                            <label for="resume" class="form-label">Currículum Vitae</label>
                            
                            <?php if (!empty($candidateProfile['cv_path'])): ?>
                                <div class="alert alert-info mb-2">
                                    <div class="d-flex align-items-center">
                                        <div>
                                            <i class="fas fa-file-pdf text-danger me-2"></i>
                                            <strong>CV actual:</strong> 
                                            <?php echo basename($candidateProfile['cv_path']); ?>
                                            (Última actualización: <?php echo date('d/m/Y H:i', strtotime($candidateProfile['cv_updated_at'])); ?>)
                                        </div>
                                        <a href="../<?php echo $candidateProfile['cv_path']; ?>" target="_blank" class="btn btn-sm btn-outline-primary ms-auto">
                                            <i class="fas fa-eye"></i> Ver
                                        </a>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <input type="file" class="form-control" id="resume" name="resume" accept=".pdf,.doc,.docx">
                            <div class="form-text">
                                <?php if (!empty($candidateProfile['cv_path'])): ?>
                                    Opcional. Suba un nuevo CV sólo si desea actualizar el existente.
                                <?php else: ?>
                                    Suba su CV en formato PDF, DOC o DOCX (máximo 5MB).
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Preguntas específicas de la vacante -->
                        <?php if (!empty($questions)): ?>
                            <h5 class="mb-3">Preguntas específicas</h5>
                            
                            <?php foreach ($questions as $question): ?>
                                <div class="mb-3">
                                    <label for="question_<?php echo $question['id']; ?>" class="form-label">
                                        <?php echo htmlspecialchars($question['question']); ?>
                                        <?php if ($question['required']): ?>
                                            <span class="text-danger">*</span>
                                        <?php endif; ?>
                                    </label>
                                    
                                    <?php if ($question['type'] === 'text'): ?>
                                        <input type="text" class="form-control" id="question_<?php echo $question['id']; ?>" name="question_<?php echo $question['id']; ?>" value="<?php echo isset($_POST['question_' . $question['id']]) ? htmlspecialchars($_POST['question_' . $question['id']]) : ''; ?>" <?php echo $question['required'] ? 'required' : ''; ?>>
                                    
                                    <?php elseif ($question['type'] === 'textarea'): ?>
                                        <textarea class="form-control" id="question_<?php echo $question['id']; ?>" name="question_<?php echo $question['id']; ?>" rows="3" <?php echo $question['required'] ? 'required' : ''; ?>><?php echo isset($_POST['question_' . $question['id']]) ? htmlspecialchars($_POST['question_' . $question['id']]) : ''; ?></textarea>
                                    
                                    <?php elseif ($question['type'] === 'select'): ?>
                                        <?php $options = json_decode($question['options'], true); ?>
                                        <select class="form-select" id="question_<?php echo $question['id']; ?>" name="question_<?php echo $question['id']; ?>" <?php echo $question['required'] ? 'required' : ''; ?>>
                                            <option value="">Seleccione una opción</option>
                                            <?php foreach ($options as $option): ?>
                                                <option value="<?php echo htmlspecialchars($option); ?>" <?php echo isset($_POST['question_' . $question['id']]) && $_POST['question_' . $question['id']] === $option ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($option); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    
                                    <?php elseif ($question['type'] === 'radio'): ?>
                                        <?php $options = json_decode($question['options'], true); ?>
                                        <?php foreach ($options as $index => $option): ?>
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="question_<?php echo $question['id']; ?>" id="question_<?php echo $question['id']; ?>_<?php echo $index; ?>" value="<?php echo htmlspecialchars($option); ?>" <?php echo isset($_POST['question_' . $question['id']]) && $_POST['question_' . $question['id']] === $option ? 'checked' : ''; ?> <?php echo $question['required'] ? 'required' : ''; ?>>
                                                <label class="form-check-label" for="question_<?php echo $question['id']; ?>_<?php echo $index; ?>">
                                                    <?php echo htmlspecialchars($option); ?>
                                                </label>
                                            </div>
                                        <?php endforeach; ?>
                                    
                                    <?php elseif ($question['type'] === 'checkbox'): ?>
                                        <?php $options = json_decode($question['options'], true); ?>
                                        <?php 
                                        $selectedOptions = [];
                                        if (isset($_POST['question_' . $question['id']])) {
                                            $selectedOptions = is_array($_POST['question_' . $question['id']]) ? $_POST['question_' . $question['id']] : [$_POST['question_' . $question['id']]];
                                        }
                                        ?>
                                        <?php foreach ($options as $index => $option): ?>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="question_<?php echo $question['id']; ?>[]" id="question_<?php echo $question['id']; ?>_<?php echo $index; ?>" value="<?php echo htmlspecialchars($option); ?>" <?php echo in_array($option, $selectedOptions) ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="question_<?php echo $question['id']; ?>_<?php echo $index; ?>">
                                                    <?php echo htmlspecialchars($option); ?>
                                                </label>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        
                        <!-- Términos y consentimiento -->
                        <div class="mb-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="consent" name="consent" required checked>
                                <label class="form-check-label" for="consent">
                                    Autorizo a SolFis a procesar mis datos personales para gestionar mi candidatura para esta vacante y posibles futuras oportunidades. <span class="text-danger">*</span>
                                </label>
                            </div>
                            <div class="form-text">
                                Puede consultar nuestra <a href="../politica-privacidad.php" target="_blank">Política de Privacidad</a> para más información sobre cómo tratamos sus datos personales.
                            </div>
                        </div>
                        
                        <!-- Botones de acción -->
                        <div class="text-end">
                            <a href="detalle.php?id=<?php echo $id; ?>" class="btn btn-outline-secondary me-2">Cancelar</a>
                            <button type="submit" class="btn btn-primary">Enviar aplicación</button>
                        </div>
                    </form>
                </div>