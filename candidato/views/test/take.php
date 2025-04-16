<!-- views/test/take.php -->
<?php 
$title = "Realizar prueba: " . htmlspecialchars($testInfo['titulo']);
include '../includes/candidate_header.php';
?>

<div class="container py-4">
    <div class="row">
        <div class="col-lg-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1><?php echo htmlspecialchars($testInfo['titulo']); ?></h1>
                <div class="d-flex align-items-center">
                    <div class="me-3">
                        <span class="badge bg-info p-2">
                            <i class="fas fa-clock"></i> Tiempo estimado: <?php echo $testInfo['tiempo_estimado']; ?> min
                        </span>
                    </div>
                    <a href="test.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> Volver
                    </a>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-body">
                    <p><?php echo nl2br(htmlspecialchars($testInfo['descripcion'])); ?></p>
                    
                    <?php if ($testInfo['instrucciones']): ?>
                    <div class="alert alert-info">
                        <h5><i class="fas fa-info-circle"></i> Instrucciones:</h5>
                        <?php echo nl2br(htmlspecialchars($testInfo['instrucciones'])); ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-tasks"></i> Progreso</h5>
                        <span><?php echo $answeredCount; ?> de <?php echo count($questions); ?> preguntas respondidas</span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="progress" style="height: 25px;">
                        <div class="progress-bar progress-bar-striped progress-bar-animated" 
                             role="progressbar" 
                             style="width: <?php echo $progressPercentage; ?>%;" 
                             aria-valuenow="<?php echo $progressPercentage; ?>" 
                             aria-valuemin="0" 
                             aria-valuemax="100">
                            <?php echo $progressPercentage; ?>%
                        </div>
                    </div>
                </div>
            </div>
            
            <form id="test-form" action="test.php?action=complete" method="post">
                <input type="hidden" name="session_id" value="<?php echo $sessionId; ?>">
                
                <?php foreach ($questions as $index => $question): ?>
                <div class="card mb-4 question-card" id="question-<?php echo $question['id']; ?>">
                    <div class="card-header <?php echo !empty($question['previous_answer']) ? 'bg-success text-white' : 'bg-light'; ?>">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Pregunta <?php echo $index + 1; ?></h5>
                            <?php if (!empty($question['previous_answer'])): ?>
                            <span><i class="fas fa-check-circle"></i> Respondida</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="card-body">
                        <h5 class="card-title"><?php echo htmlspecialchars($question['texto']); ?></h5>
                        
                        <div class="mt-4">
                            <?php if ($question['tipo_pregunta'] == 'likert'): ?>
                            <!-- Escala Likert -->
                            <div class="likert-scale">
                                <div class="row text-center gx-0">
                                    <?php foreach ($question['options'] as $option): ?>
                                    <div class="col">
                                        <div class="form-check">
                                            <input class="form-check-input" 
                                                   type="radio" 
                                                   name="answer_<?php echo $question['id']; ?>" 
                                                   value="<?php echo $option['id']; ?>"
                                                   id="option_<?php echo $question['id']; ?>_<?php echo $option['id']; ?>"
                                                   <?php echo (!empty($question['previous_answer']) && $question['previous_answer']['opcion_id'] == $option['id']) ? 'checked' : ''; ?>>
                                            <label class="form-check-label d-block" for="option_<?php echo $question['id']; ?>_<?php echo $option['id']; ?>">
                                                <?php echo htmlspecialchars($option['texto']); ?>
                                            </label>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            
                            <?php elseif ($question['tipo_pregunta'] == 'multiple'): ?>
                            <!-- Opción múltiple -->
                            <div class="multiple-choice">
                                <?php foreach ($question['options'] as $option): ?>
                                <div class="form-check mb-3">
                                    <input class="form-check-input" 
                                           type="radio" 
                                           name="answer_<?php echo $question['id']; ?>" 
                                           value="<?php echo $option['id']; ?>"
                                           id="option_<?php echo $question['id']; ?>_<?php echo $option['id']; ?>"
                                           <?php echo (!empty($question['previous_answer']) && $question['previous_answer']['opcion_id'] == $option['id']) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="option_<?php echo $question['id']; ?>_<?php echo $option['id']; ?>">
                                        <?php echo htmlspecialchars($option['texto']); ?>
                                    </label>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <?php elseif ($question['tipo_pregunta'] == 'pares'): ?>
                            <!-- Elección forzada (pares) -->
                            <div class="forced-choice">
                                <div class="row">
                                    <?php 
                                    // Agrupar opciones en pares (A y B)
                                    $optionA = $question['options'][0] ?? null;
                                    $optionB = $question['options'][1] ?? null;
                                    
                                    if ($optionA && $optionB):
                                    ?>
                                    <div class="col-md-6">
                                        <div class="card h-100">
                                            <div class="card-body">
                                                <div class="form-check">
                                                    <input class="form-check-input" 
                                                           type="radio" 
                                                           name="answer_<?php echo $question['id']; ?>" 
                                                           value="<?php echo $optionA['id']; ?>"
                                                           id="option_<?php echo $question['id']; ?>_<?php echo $optionA['id']; ?>"
                                                           <?php echo (!empty($question['previous_answer']) && $question['previous_answer']['opcion_id'] == $optionA['id']) ? 'checked' : ''; ?>>
                                                    <label class="form-check-label" for="option_<?php echo $question['id']; ?>_<?php echo $optionA['id']; ?>">
                                                        <strong>Opción A:</strong><br>
                                                        <?php echo htmlspecialchars($optionA['texto']); ?>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="card h-100">
                                            <div class="card-body">
                                                <div class="form-check">
                                                    <input class="form-check-input" 
                                                           type="radio" 
                                                           name="answer_<?php echo $question['id']; ?>" 
                                                           value="<?php echo $optionB['id']; ?>"
                                                           id="option_<?php echo $question['id']; ?>_<?php echo $optionB['id']; ?>"
                                                           <?php echo (!empty($question['previous_answer']) && $question['previous_answer']['opcion_id'] == $optionB['id']) ? 'checked' : ''; ?>>
                                                    <label class="form-check-label" for="option_<?php echo $question['id']; ?>_<?php echo $optionB['id']; ?>">
                                                        <strong>Opción B:</strong><br>
                                                        <?php echo htmlspecialchars($optionB['texto']); ?>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <?php elseif ($question['tipo_pregunta'] == 'situacional'): ?>
                            <!-- Situacional -->
                            <div class="situational">
                                <?php foreach ($question['options'] as $option): ?>
                                <div class="form-check mb-3">
                                    <input class="form-check-input" 
                                           type="radio" 
                                           name="answer_<?php echo $question['id']; ?>" 
                                           value="<?php echo $option['id']; ?>"
                                           id="option_<?php echo $question['id']; ?>_<?php echo $option['id']; ?>"
                                           <?php echo (!empty($question['previous_answer']) && $question['previous_answer']['opcion_id'] == $option['id']) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="option_<?php echo $question['id']; ?>_<?php echo $option['id']; ?>">
                                        <?php echo htmlspecialchars($option['texto']); ?>
                                    </label>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="mt-3 text-end">
                            <button type="button" class="btn btn-primary save-answer" data-question-id="<?php echo $question['id']; ?>">
                                <i class="fas fa-save"></i> Guardar Respuesta
                            </button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <button type="button" class="btn btn-outline-secondary" id="save-and-exit">
                                <i class="fas fa-save"></i> Guardar y Continuar Después
                            </button>
                            
                            <button type="submit" class="btn btn-success" id="complete-test" <?php echo $allAnswered ? '' : 'disabled'; ?>>
                                <i class="fas fa-check-circle"></i> Finalizar Prueba
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Variables
    const saveButtons = document.querySelectorAll('.save-answer');
    const completeButton = document.getElementById('complete-test');
    const sessionId = <?php echo $sessionId; ?>;
    let answeredCount = <?php echo $answeredCount; ?>;
    const totalQuestions = <?php echo count($questions); ?>;
    
    // Función para guardar respuesta
    async function saveAnswer(questionId) {
        const radioName = `answer_${questionId}`;
        const selectedOption = document.querySelector(`input[name="${radioName}"]:checked`);
        
        if (!selectedOption) {
            alert('Por favor seleccione una respuesta.');
            return false;
        }
        
        const optionId = selectedOption.value;
        
        try {
            const response = await fetch('test.php?action=save_answer', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `session_id=${sessionId}&question_id=${questionId}&opcion_id=${optionId}&tipo_respuesta=multiple`
            });
            
            const data = await response.json();
            
            if (data.success) {
                // Actualizar UI
                const questionCard = document.getElementById(`question-${questionId}`);
                questionCard.querySelector('.card-header').classList.add('bg-success', 'text-white');
                questionCard.querySelector('.card-header').classList.remove('bg-light');
                
                // Añadir ícono de completado
                const headerContent = questionCard.querySelector('.card-header div');
                if (!headerContent.querySelector('span')) {
                    const completedSpan = document.createElement('span');
                    completedSpan.innerHTML = '<i class="fas fa-check-circle"></i> Respondida';
                    headerContent.appendChild(completedSpan);
                }
                
                // Actualizar contador
                if (data.progress) {
                    const progressBar = document.querySelector('.progress-bar');
                    progressBar.style.width = `${data.progress.percentage}%`;
                    progressBar.setAttribute('aria-valuenow', data.progress.percentage);
                    progressBar.textContent = `${data.progress.percentage}%`;
                    
                    answeredCount = data.progress.answered;
                    
                    // Actualizar texto de progreso
                    document.querySelector('.card-header span').textContent = `${answeredCount} de ${totalQuestions} preguntas respondidas`;
                    
                    // Habilitar botón de finalizar si todas están respondidas
                    if (answeredCount === totalQuestions) {
                        completeButton.removeAttribute('disabled');
                    }
                }
                
                return true;
            } else {
                alert('Error al guardar la respuesta: ' + data.message);
                return false;
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Error de conexión al guardar la respuesta');
            return false;
        }
    }
    
    // Configurar eventos para botones de guardar
    saveButtons.forEach(button => {
        button.addEventListener('click', function() {
            const questionId = this.getAttribute('data-question-id');
            saveAnswer(questionId);
        });
    });
    
    // Configurar evento para guardar y salir
    document.getElementById('save-and-exit').addEventListener('click', function() {
        alert('Tus respuestas han sido guardadas. Puedes continuar la prueba más tarde.');
        window.location.href = 'test.php';
    });
});
</script>

<?php include '../includes/candidate_footer.php'; ?>