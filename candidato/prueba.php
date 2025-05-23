<?php
session_start();

// Verificar que el usuario esté autenticado como candidato
if (!isset($_SESSION['candidato_id'])) {
    header('Location: login.php');
    exit;
}

// Incluir archivos necesarios
require_once '../includes/jobs-system.php';

// Obtener ID de la prueba a realizar
$prueba_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Si no se especificó una prueba, redirigir al panel
if ($prueba_id === 0) {
    header('Location: panel.php');
    exit;
}

// Instanciar gestores necesarios
$candidateManager = new CandidateManager();
$testManager = new TestManager(); // Esta clase deberá ser implementada

// Obtener datos del candidato
$candidato_id = $_SESSION['candidato_id'];
$candidato = $candidateManager->getCandidateById($candidato_id);

// Obtener información de la prueba
$prueba = $testManager->getTestById($prueba_id);

// Verificar si la prueba existe
if (!$prueba) {
    header('Location: panel.php?error=prueba_no_encontrada');
    exit;
}

// Verificar si el candidato ya completó esta prueba
$sesion = $testManager->checkExistingSession($candidato_id, $prueba_id);

// Si ya hay una sesión completada, mostrar mensaje o redirigir
if ($sesion && $sesion['estado'] === 'completada') {
    header('Location: resultado-prueba.php?sesion_id=' . $sesion['id']);
    exit;
}

// Si hay una sesión en progreso, continuarla
$sesion_id = $sesion ? $sesion['id'] : null;

// Obtener número de pregunta actual
$pregunta_actual = isset($_GET['p']) ? (int)$_GET['p'] : 0;

// Si no hay sesión, crear una nueva
if (!$sesion_id) {
    $sesion_id = $testManager->createSession($candidato_id, $prueba_id);
}

// Obtener todas las preguntas de la prueba
$preguntas = $testManager->getTestQuestions($prueba_id);
$total_preguntas = count($preguntas);

// Determinar si hay una siguiente pregunta
$hay_siguiente = ($pregunta_actual < $total_preguntas - 1);
$hay_anterior = ($pregunta_actual > 0);

// Obtener la pregunta actual
$pregunta = isset($preguntas[$pregunta_actual]) ? $preguntas[$pregunta_actual] : null;

// Si no hay más preguntas, finalizar la prueba
if (!$pregunta) {
    $testManager->completeSession($sesion_id);
    header('Location: resultado-prueba.php?sesion_id=' . $sesion_id);
    exit;
}

// Obtener opciones de respuesta para la pregunta actual
$opciones = $testManager->getQuestionOptions($pregunta['id']);

// Verificar si ya hay una respuesta para esta pregunta en esta sesión
$respuesta_actual = $testManager->getAnswer($sesion_id, $pregunta['id']);

// Procesar respuesta si se envió el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['responder'])) {
    // Capturar la respuesta según el tipo de pregunta
    $respuesta = null;
    
    switch ($pregunta['tipo']) {
        case 'opcion_multiple':
            $respuesta = isset($_POST['opcion_id']) ? (int)$_POST['opcion_id'] : null;
            break;
        case 'verdadero_falso':
            $respuesta = isset($_POST['valor']) ? (int)$_POST['valor'] : null;
            break;
        case 'escala_likert':
            $respuesta = isset($_POST['valor_escala']) ? (int)$_POST['valor_escala'] : null;
            break;
        case 'respuesta_abierta':
            $respuesta = isset($_POST['texto_respuesta']) ? $_POST['texto_respuesta'] : null;
            break;
    }
    
    // Guardar la respuesta
    if ($respuesta !== null) {
        $testManager->saveAnswer($sesion_id, $pregunta['id'], $respuesta);
        
        // Si se seleccionó "Siguiente", avanzar a la siguiente pregunta
        if (isset($_POST['accion']) && $_POST['accion'] === 'siguiente' && $hay_siguiente) {
            header('Location: prueba.php?id=' . $prueba_id . '&p=' . ($pregunta_actual + 1));
            exit;
        } 
        // Si se seleccionó "Anterior", retroceder a la pregunta anterior
        else if (isset($_POST['accion']) && $_POST['accion'] === 'anterior' && $hay_anterior) {
            header('Location: prueba.php?id=' . $prueba_id . '&p=' . ($pregunta_actual - 1));
            exit;
        }
        // Si se seleccionó "Finalizar", completar la prueba
        else if (isset($_POST['accion']) && $_POST['accion'] === 'finalizar') {
            try {
                // Registrar en un log
                error_log("Completando sesión de prueba: $sesion_id");
                
                // Completar la sesión
                $success = $testManager->completeSession($sesion_id);
                
                if ($success) {
                    header('Location: resultado-prueba.php?sesion_id=' . $sesion_id);
                    exit;
                } else {
                    // Si falló, mostrar un mensaje de error
                    $error_message = "Hubo un problema al completar la prueba. Por favor, contacta a soporte.";
                    
                    // Log del error
                    error_log("Error al completar sesión $sesion_id: " . print_r(error_get_last(), true));
                }
            } catch (Exception $e) {
                // Capturar cualquier excepción
                $error_message = "Error inesperado: " . $e->getMessage();
                error_log("Excepción al completar sesión $sesion_id: " . $e->getMessage());
            }
        }
    }
}

// Calcular progreso
$progreso = round(($pregunta_actual / $total_preguntas) * 100);

// Título de la página
$pageTitle = $prueba['titulo'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - SolFis Talentos</title>
    
    <!-- CSS -->
    <link rel="stylesheet" href="../assets/css/normalize.css">
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="css/candidato.css">
    <link rel="stylesheet" href="css/pruebas.css">
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Estilos adicionales para imágenes fallidas -->
    <style>
    .image-error {
        background-color: #f8f9fa;
        border: 1px dashed #ced4da;
        border-radius: 8px;
        padding: 20px;
        text-align: center;
        color: #6c757d;
    }
    
    .image-error i {
        font-size: 2rem;
        margin-bottom: 10px;
    }
    
    /* Mejorar visibilidad del temporizador */
    .question-timer {
        font-weight: bold;
        font-size: 1.1rem;
    }
    
    .question-timer.warning {
        background-color: rgba(255, 193, 7, 0.3);
        color: #FF6600;
        animation: pulse 1s infinite;
    }
    
    @keyframes pulse {
        0% { opacity: 1; }
        50% { opacity: 0.7; }
        100% { opacity: 1; }
    }
    </style>
</head>
<body>
    <div class="test-container">
        <header class="test-header">
            <div class="test-info">
                <h1><?php echo htmlspecialchars($prueba['titulo']); ?></h1>
                <div class="test-progress">
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: <?php echo $progreso; ?>%"></div>
                    </div>
                    <div class="progress-text">
                        <span>Pregunta <?php echo $pregunta_actual + 1; ?> de <?php echo $total_preguntas; ?></span>
                        <span><?php echo $progreso; ?>% completado</span>
                    </div>
                </div>
            </div>
            <div class="test-actions">
                <a href="#" class="btn-outline pause-test" id="pauseBtn">
                    <i class="fas fa-pause"></i> Pausar
                </a>
            </div>
        </header>
        
        <main class="test-main">
            <div class="question-container">
                <div class="question-header">
                    <span class="question-number">Pregunta <?php echo $pregunta_actual + 1; ?></span>
                    <?php if (!empty($pregunta['tiempo_sugerido'])): ?>
                    <div class="question-timer" data-seconds="<?php echo $pregunta['tiempo_sugerido']; ?>">
                        <i class="fas fa-clock"></i> <span id="timer"><?php echo $pregunta['tiempo_sugerido']; ?></span> seg
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="question-content">
                    <h2 class="question-text"><?php echo htmlspecialchars($pregunta['texto']); ?></h2>
                    <?php if (!empty($pregunta['instrucciones'])): ?>
                    <p class="question-instructions"><?php echo htmlspecialchars($pregunta['instrucciones']); ?></p>
                    <?php endif; ?>
                    
                    <?php if (!empty($pregunta['imagen_url'])): ?>
                    <div class="question-image">
                        <img src="<?php echo htmlspecialchars($pregunta['imagen_url']); ?>" alt="Imagen de la pregunta">
                    </div>
                    <?php endif; ?>
                </div>
                
                <form method="POST" action="" class="answer-form" id="answerForm">
                    <div class="answer-options">
                        <?php if ($pregunta['tipo'] === 'opcion_multiple'): ?>
                            <?php foreach ($opciones as $opcion): ?>
                            <div class="answer-option">
                                <input type="radio" 
                                       id="option_<?php echo $opcion['id']; ?>" 
                                       name="opcion_id" 
                                       value="<?php echo $opcion['id']; ?>"
                                       <?php echo ($respuesta_actual && isset($respuesta_actual['opcion_id']) && $respuesta_actual['opcion_id'] == $opcion['id']) ? 'checked' : ''; ?>>
                                <label for="option_<?php echo $opcion['id']; ?>" class="option-label">
                                    <?php echo htmlspecialchars($opcion['texto']); ?>
                                </label>
                            </div>
                            <?php endforeach; ?>
                        
                        <?php elseif ($pregunta['tipo'] === 'verdadero_falso'): ?>
                            <div class="answer-option">
                                <input type="radio" 
                                       id="option_true" 
                                       name="valor" 
                                       value="1"
                                       <?php echo ($respuesta_actual && isset($respuesta_actual['valor_escala']) && $respuesta_actual['valor_escala'] == 1) ? 'checked' : ''; ?>>
                                <label for="option_true" class="option-label">Verdadero</label>
                            </div>
                            <div class="answer-option">
                                <input type="radio" 
                                       id="option_false" 
                                       name="valor" 
                                       value="0"
                                       <?php echo ($respuesta_actual && isset($respuesta_actual['valor_escala']) && $respuesta_actual['valor_escala'] == 0) ? 'checked' : ''; ?>>
                                <label for="option_false" class="option-label">Falso</label>
                            </div>
                        
                        <?php elseif ($pregunta['tipo'] === 'escala_likert'): ?>
                            <div class="likert-scale">
                                <?php 
                                // Si no hay opciones definidas, creamos opciones predeterminadas para escalas Likert
                                if (empty($opciones)) {
                                    $opciones = [
                                        ['id' => 'likert_5', 'valor' => 5, 'texto' => 'Totalmente de acuerdo'],
                                        ['id' => 'likert_4', 'valor' => 4, 'texto' => 'De acuerdo'],
                                        ['id' => 'likert_3', 'valor' => 3, 'texto' => 'Neutral'],
                                        ['id' => 'likert_2', 'valor' => 2, 'texto' => 'En desacuerdo'],
                                        ['id' => 'likert_1', 'valor' => 1, 'texto' => 'Totalmente en desacuerdo']
                                    ];
                                }
                                
                                foreach ($opciones as $index => $opcion): 
                                    // Asegurarse de que las opciones tengan todos los campos necesarios
                                    $opcionId = isset($opcion['id']) ? $opcion['id'] : 'likert_' . ($index + 1);
                                    $valor = isset($opcion['valor']) ? $opcion['valor'] : ($index + 1);
                                    $texto = isset($opcion['texto']) ? $opcion['texto'] : 'Opción ' . ($index + 1);
                                ?>
                                <div class="likert-option">
                                    <input type="radio" 
                                           id="option_<?php echo $opcionId; ?>" 
                                           name="valor_escala" 
                                           value="<?php echo $valor; ?>"
                                           <?php echo ($respuesta_actual && isset($respuesta_actual['valor_escala']) && $respuesta_actual['valor_escala'] == $valor) ? 'checked' : ''; ?>>
                                    <label for="option_<?php echo $opcionId; ?>" class="likert-label">
                                        <?php echo htmlspecialchars($texto); ?>
                                    </label>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        
                        <?php elseif ($pregunta['tipo'] === 'respuesta_abierta'): ?>
                            <div class="text-answer">
                                <textarea name="texto_respuesta" 
                                          id="texto_respuesta" 
                                          rows="5" 
                                          placeholder="Escribe tu respuesta aquí..."><?php echo isset($respuesta_actual['texto_respuesta']) ? $respuesta_actual['texto_respuesta'] : ''; ?></textarea>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-navigation">
                        <?php if ($hay_anterior): ?>
                        <button type="submit" name="accion" value="anterior" class="nav-btn btn-outline">
                            <i class="fas fa-chevron-left"></i> Anterior
                        </button>
                        <?php else: ?>
                        <div></div> <!-- Espaciador -->
                        <?php endif; ?>
                        
                        <input type="hidden" name="responder" value="1">
                        
                        <?php if ($hay_siguiente): ?>
                        <button type="submit" name="accion" value="siguiente" class="nav-btn btn-primary" id="nextBtn">
                            Siguiente <i class="fas fa-chevron-right"></i>
                        </button>
                        <?php else: ?>
                        <button type="submit" name="accion" value="finalizar" class="nav-btn btn-success" id="finishBtn">
                            Finalizar Prueba <i class="fas fa-check"></i>
                        </button>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </main>
        
        <div class="test-modal" id="pauseModal">
            <div class="modal-content">
                <h3>¿Deseas pausar la prueba?</h3>
                <p>Tu progreso será guardado y podrás continuar más tarde desde donde lo dejaste.</p>
                <div class="modal-actions">
                    <button class="btn-outline" id="continuarBtn">Continuar Prueba</button>
                    <a href="panel.php" class="btn-primary">Pausar y Salir</a>
                </div>
            </div>
        </div>
        
        <!-- Modal de error para mostrar cuando hay problemas -->
        <?php if (isset($error_message)): ?>
        <div class="test-modal show" id="errorModal">
            <div class="modal-content">
                <h3><i class="fas fa-exclamation-triangle" style="color: #dc3545;"></i> Error</h3>
                <p><?php echo $error_message; ?></p>
                <div class="modal-actions">
                    <a href="pruebas.php" class="btn-outline">
                        Volver a mis evaluaciones
                    </a>
                    <a href="prueba.php?id=<?php echo $prueba_id; ?>" class="btn-primary">
                        Intentar de nuevo
                    </a>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Timer functionality
            const timerElement = document.getElementById('timer');
            if (timerElement) {
                const timerContainer = document.querySelector('.question-timer');
                const seconds = parseInt(timerContainer.dataset.seconds);
                let timeLeft = seconds;
                
                const timer = setInterval(() => {
                    timeLeft--;
                    timerElement.textContent = timeLeft;
                    
                    if (timeLeft <= 10) {
                        timerContainer.classList.add('warning');
                    }
                    
                    if (timeLeft <= 0) {
                        clearInterval(timer);
                        // Auto-submit after time is up
                        setTimeout(() => {
                            document.getElementById('answerForm').submit();
                        }, 500);
                    }
                }, 1000);
            }
            
            // Pause modal functionality
            const pauseBtn = document.getElementById('pauseBtn');
            const pauseModal = document.getElementById('pauseModal');
            const continuarBtn = document.getElementById('continuarBtn');
            
            pauseBtn?.addEventListener('click', function(e) {
                e.preventDefault();
                pauseModal.classList.add('show');
            });
            
            continuarBtn?.addEventListener('click', function() {
                pauseModal.classList.remove('show');
            });
            
            // Auto-save responses for non-radio inputs
            const textareaField = document.getElementById('texto_respuesta');
            if (textareaField) {
                textareaField.addEventListener('blur', function() {
                    // Could implement autosave via AJAX here
                });
            }
            
            // Validación del formulario
            const answerForm = document.getElementById('answerForm');
            if (answerForm) {
                answerForm.addEventListener('submit', function(event) {
                    // Verificar si hay alguna opción seleccionada para opción múltiple
                    if (document.querySelector('input[type="radio"]')) {
                        const radioChecked = document.querySelector('input[type="radio"]:checked');
                        if (!radioChecked) {
                            event.preventDefault();
                            alert('Por favor selecciona una opción antes de continuar.');
                            return false;
                        }
                    }
                    
                    // Verificar si hay texto en la respuesta abierta
                    if (textareaField && textareaField.value.trim() === '') {
                        event.preventDefault();
                        alert('Por favor escribe tu respuesta antes de continuar.');
                        textareaField.focus();
                        return false;
                    }
                });
            }
            
            // Mejorar la interacción con las opciones para escala Likert
            const likertOptions = document.querySelectorAll('.likert-option');
            likertOptions.forEach(option => {
                option.addEventListener('click', function() {
                    // Programáticamente seleccionar el radio button cuando se hace clic en cualquier parte de la opción
                    const radio = this.querySelector('input[type="radio"]');
                    if (radio) {
                        radio.checked = true;
                    }
                });
            });
            
            // Verificar la carga de imágenes
            const questionImages = document.querySelectorAll('.question-image img');
            
            questionImages.forEach(img => {
                img.onerror = function() {
                    // Si la imagen falla al cargar, mostrar un mensaje
                    const imgContainer = this.parentElement;
                    imgContainer.innerHTML = '<div class="image-error">' + 
                                            '<i class="fas fa-image"></i>' + 
                                            '<p>No se pudo cargar la imagen</p>' + 
                                            '</div>';
                };
                
                // Verificar si la URL de la imagen está vacía o es inválida
                if (!img.src || img.src === window.location.href || 
                    img.src === 'http://' || img.src === 'https://') {
                    img.onerror();
                }
            });
        });
    </script>
</body>
</html>