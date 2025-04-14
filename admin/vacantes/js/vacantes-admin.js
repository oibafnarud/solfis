/**
 * vacantes-admin.js - Funcionalidades JavaScript para la administración de vacantes
 */

document.addEventListener('DOMContentLoaded', function() {
    // Inicializar componentes
    initTooltips();
    initDeleteConfirmation();
    initFeatureToggle();
    initFormValidation();
    initRichTextEditors();
    initDatePickers();
    initPipeline();
    initCharts();
});

/**
 * Inicializar tooltips de Bootstrap
 */
function initTooltips() {
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
}

/**
 * Inicializar confirmación para eliminación de vacantes
 */
function initDeleteConfirmation() {
    // Modal para confirmación de eliminación
    const deleteModal = document.getElementById('deleteModal');
    if (deleteModal) {
        const modal = new bootstrap.Modal(deleteModal);
        let idToDelete = null;
        
        // Botones de eliminación
        const deleteButtons = document.querySelectorAll('.delete-vacancy, .delete-category, .delete-application');
        deleteButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                idToDelete = this.getAttribute('data-id');
                modal.show();
            });
        });
        
        // Botón de confirmación
        const confirmButton = document.getElementById('confirmDelete');
        if (confirmButton) {
            confirmButton.addEventListener('click', function() {
                if (idToDelete) {
                    // Determinar tipo de eliminación basado en clases del botón
                    const deleteButton = document.querySelector(`.delete-vacancy[data-id="${idToDelete}"], .delete-category[data-id="${idToDelete}"], .delete-application[data-id="${idToDelete}"]`);
                    let redirectUrl = 'vacante-eliminar.php';
                    
                    if (deleteButton) {
                        if (deleteButton.classList.contains('delete-category')) {
                            redirectUrl = 'categoria-eliminar.php';
                        } else if (deleteButton.classList.contains('delete-application')) {
                            redirectUrl = 'aplicacion-eliminar.php';
                        }
                    }
                    
                    // Redirigir a la URL de eliminación
                    window.location.href = `${redirectUrl}?id=${idToDelete}`;
                }
                modal.hide();
            });
        }
    }
}

/**
 * Inicializar toggle para destacar vacantes
 */
function initFeatureToggle() {
    const featureButtons = document.querySelectorAll('.toggle-featured');
    
    featureButtons.forEach(button => {
        button.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            const icon = this.querySelector('i');
            const featured = icon.classList.contains('fas');
            
            // Realizar solicitud AJAX para cambiar estado
            fetch('vacante-destacar.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `id=${id}&featured=${!featured ? 1 : 0}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Cambiar ícono
                    if (featured) {
                        icon.classList.remove('fas');
                        icon.classList.add('far');
                    } else {
                        icon.classList.remove('far');
                        icon.classList.add('fas');
                    }
                    
                    // Mostrar notificación
                    showNotification(data.message, 'success');
                } else {
                    showNotification(data.message || 'Error al cambiar estado', 'danger');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Error al procesar la solicitud', 'danger');
            });
        });
    });
}

/**
 * Mostrar notificación temporal
 */
function showNotification(message, type) {
    // Verificar si ya existe una notificación
    let notification = document.querySelector('.notification');
    
    if (!notification) {
        // Crear nueva notificación
        notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        document.body.appendChild(notification);
    } else {
        // Actualizar clase de tipo
        notification.className = `notification notification-${type}`;
    }
    
    // Establecer mensaje
    notification.innerHTML = `
        <div class="notification-content">
            <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
            <span>${message}</span>
        </div>
        <button type="button" class="notification-close">
            <i class="fas fa-times"></i>
        </button>
    `;
    
    // Mostrar notificación
    setTimeout(() => {
        notification.classList.add('show');
    }, 10);
    
    // Asignar evento para cerrar
    const closeButton = notification.querySelector('.notification-close');
    if (closeButton) {
        closeButton.addEventListener('click', () => {
            notification.classList.remove('show');
            setTimeout(() => {
                notification.remove();
            }, 300);
        });
    }
    
    // Auto cerrar después de 5 segundos
    setTimeout(() => {
        if (notification && document.body.contains(notification)) {
            notification.classList.remove('show');
            setTimeout(() => {
                if (notification && document.body.contains(notification)) {
                    notification.remove();
                }
            }, 300);
        }
    }, 5000);
}

/**
 * Inicializar validación de formularios
 */
function initFormValidation() {
    const forms = document.querySelectorAll('.needs-validation');
    
    Array.from(forms).forEach(form => {
        form.addEventListener('submit', event => {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            
            form.classList.add('was-validated');
        }, false);
    });
}

/**
 * Inicializar editores de texto enriquecido
 */
function initRichTextEditors() {
    // Verificar si la biblioteca está disponible
    if (typeof ClassicEditor !== 'undefined') {
        const editorElements = document.querySelectorAll('.rich-editor');
        
        editorElements.forEach(element => {
            ClassicEditor
                .create(element)
                .catch(error => {
                    console.error(error);
                });
        });
    }
}

/**
 * Inicializar selectores de fecha
 */
function initDatePickers() {
    // Verificar si la biblioteca está disponible
    if (typeof flatpickr !== 'undefined') {
        const dateInputs = document.querySelectorAll('.date-picker');
        
        dateInputs.forEach(input => {
            flatpickr(input, {
                dateFormat: "Y-m-d",
                altInput: true,
                altFormat: "d/m/Y",
                locale: {
                    firstDayOfWeek: 1
                }
            });
        });
    }
}

/**
 * Inicializar vista de pipeline para aplicaciones
 */
function initPipeline() {
    const pipelineContainer = document.querySelector('.pipeline-container');
    
    if (pipelineContainer) {
        // Permitir arrastrar tarjetas entre columnas
        const draggables = document.querySelectorAll('.pipeline-card');
        const containers = document.querySelectorAll('.pipeline-column');
        
        draggables.forEach(draggable => {
            draggable.addEventListener('dragstart', () => {
                draggable.classList.add('dragging');
            });
            
            draggable.addEventListener('dragend', () => {
                draggable.classList.remove('dragging');
                
                // Obtener información para actualizar BD
                const applicationId = draggable.getAttribute('data-id');
                const newStage = draggable.closest('.pipeline-column').getAttribute('data-stage');
                
                // Enviar actualización mediante AJAX
                updateApplicationStage(applicationId, newStage);
            });
        });
        
        containers.forEach(container => {
            container.addEventListener('dragover', e => {
                e.preventDefault();
                const afterElement = getDragAfterElement(container, e.clientY);
                const draggable = document.querySelector('.dragging');
                
                if (afterElement == null) {
                    container.appendChild(draggable);
                } else {
                    container.insertBefore(draggable, afterElement);
                }
            });
        });
    }
}

/**
 * Obtener elemento después del cual insertar el elemento arrastrado
 */
function getDragAfterElement(container, y) {
    const draggableElements = [...container.querySelectorAll('.pipeline-card:not(.dragging)')];
    
    return draggableElements.reduce((closest, child) => {
        const box = child.getBoundingClientRect();
        const offset = y - box.top - box.height / 2;
        
        if (offset < 0 && offset > closest.offset) {
            return { offset: offset, element: child };
        } else {
            return closest;
        }
    }, { offset: Number.NEGATIVE_INFINITY }).element;
}

/**
 * Actualizar etapa de aplicación
 */
function updateApplicationStage(applicationId, newStage) {
    fetch('aplicacion-actualizar-etapa.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `id=${applicationId}&etapa=${newStage}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Actualizar contadores de etapas
            updateStageCounts();
            showNotification(data.message, 'success');
        } else {
            showNotification(data.message || 'Error al actualizar etapa', 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Error al procesar la solicitud', 'danger');
    });
}

/**
 * Actualizar contadores de etapas en pipeline
 */
function updateStageCounts() {
    const containers = document.querySelectorAll('.pipeline-column');
    
    containers.forEach(container => {
        const count = container.querySelectorAll('.pipeline-card').length;
        const countElement = container.querySelector('.pipeline-column-count');
        
        if (countElement) {
            countElement.textContent = count;
        }
    });
}

/**
 * Inicializar gráficos para estadísticas
 */
function initCharts() {
    // Verificar si Chart.js está disponible
    if (typeof Chart !== 'undefined') {
        // Gráfico de aplicaciones por mes
        const applicationsChart = document.getElementById('applicationsChart');
        if (applicationsChart) {
            const ctx = applicationsChart.getContext('2d');
            
            // Datos de ejemplo (en producción vendrían de la BD)
            const months = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
            const applicationsData = [25, 30, 35, 40, 45, 50, 55, 60, 65, 70, 75, 80];
            
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: months,
                    datasets: [{
                        label: 'Aplicaciones',
                        data: applicationsData,
                        backgroundColor: 'rgba(0, 177, 235, 0.2)',
                        borderColor: 'rgba(0, 177, 235, 1)',
                        borderWidth: 2,
                        tension: 0.3,
                        pointBackgroundColor: 'rgba(0, 177, 235, 1)',
                        pointRadius: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0, 44, 107, 0.8)',
                            callbacks: {
                                label: function(context) {
                                    return `Aplicaciones: ${context.raw}`;
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: {
                                display: false
                            }
                        },
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)'
                            }
                        }
                    }
                }
            });
        }
        
        // Gráfico de vacantes por categoría
        const categoriesChart = document.getElementById('categoriesChart');
        if (categoriesChart) {
            const ctx = categoriesChart.getContext('2d');
            
            // Datos de ejemplo (en producción vendrían de la BD)
            const categories = ['Contabilidad', 'Auditoría', 'Impuestos', 'Finanzas', 'Tecnología', 'Administrativo'];
            const vacanciesData = [12, 8, 7, 6, 6, 4];
            
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: categories,
                    datasets: [{
                        label: 'Vacantes',
                        data: vacanciesData,
                        backgroundColor: 'rgba(0, 44, 107, 0.7)',
                        borderColor: 'rgba(0, 44, 107, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0, 44, 107, 0.8)',
                            callbacks: {
                                label: function(context) {
                                    return `Vacantes: ${context.raw}`;
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: {
                                display: false
                            }
                        },
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)'
                            }
                        }
                    }
                }
            });
        }
    }
}

/**
 * Función para gestionar preguntas personalizadas en el formulario de vacante
 */
document.addEventListener('DOMContentLoaded', function() {
    const addQuestionButton = document.getElementById('addQuestion');
    if (addQuestionButton) {
        addQuestionButton.addEventListener('click', function() {
            addCustomQuestion();
        });
        
        // Inicializar escuchadores para botones existentes
        initQuestionListeners();
    }
});

/**
 * Añadir una nueva pregunta personalizada
 */
function addCustomQuestion() {
    const questionsContainer = document.getElementById('customQuestions');
    if (!questionsContainer) return;
    
    const questionIndex = document.querySelectorAll('.custom-question').length;
    
    const questionHtml = `
        <div class="custom-question card mb-3" id="question${questionIndex}">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Pregunta #${questionIndex + 1}</h5>
                <button type="button" class="btn btn-outline-danger remove-option">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-secondary add-option" data-index="${questionIndex}">
                            <i class="fas fa-plus"></i> Añadir opción
                        </button>
                    </div>
                </div>
                <div class="form-group">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="question_required_${questionIndex}" name="questions[${questionIndex}][required]" checked>
                        <label class="form-check-label" for="question_required_${questionIndex}">Obligatorio</label>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    questionsContainer.insertAdjacentHTML('beforeend', questionHtml);
    
    // Inicializar eventos para la nueva pregunta
    initQuestionListeners();
}

/**
 * Inicializar eventos para preguntas personalizadas
 */
function initQuestionListeners() {
    // Evento para eliminar preguntas
    document.querySelectorAll('.remove-question').forEach(button => {
        button.addEventListener('click', function() {
            const questionCard = this.closest('.custom-question');
            if (questionCard) {
                questionCard.remove();
                // Renumerar las preguntas restantes
                updateQuestionNumbers();
            }
        });
    });
    
    // Evento para cambio de tipo de pregunta
    document.querySelectorAll('.question-type').forEach(select => {
        select.addEventListener('change', function() {
            const index = this.getAttribute('data-index');
            const optionsContainer = document.getElementById(`options_container_${index}`);
            
            if (this.value === 'select' || this.value === 'checkbox') {
                optionsContainer.style.display = 'block';
            } else {
                optionsContainer.style.display = 'none';
            }
        });
    });
    
    // Evento para añadir opciones
    document.querySelectorAll('.add-option').forEach(button => {
        button.addEventListener('click', function() {
            const index = this.getAttribute('data-index');
            const optionsList = document.getElementById(`options_list_${index}`);
            const optionsCount = optionsList.querySelectorAll('.input-group').length;
            
            const optionHtml = `
                <div class="input-group mb-2">
                    <input type="text" class="form-control" name="questions[${index}][options][${optionsCount}]" placeholder="Opción ${optionsCount + 1}">
                    <button type="button" class="btn btn-outline-danger remove-option">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            `;
            
            optionsList.insertAdjacentHTML('beforeend', optionHtml);
            
            // Inicializar evento para eliminar opción
            const newOptionButton = optionsList.querySelector('.input-group:last-child .remove-option');
            newOptionButton.addEventListener('click', function() {
                this.closest('.input-group').remove();
            });
        });
    });
    
    // Evento para eliminar opciones
    document.querySelectorAll('.remove-option').forEach(button => {
        button.addEventListener('click', function() {
            this.closest('.input-group').remove();
        });
    });
}

/**
 * Actualizar números de preguntas
 */
function updateQuestionNumbers() {
    document.querySelectorAll('.custom-question').forEach((card, index) => {
        // Actualizar título
        const header = card.querySelector('.card-header h5');
        if (header) {
            header.textContent = `Pregunta #${index + 1}`;
        }
        
        // Actualizar ID
        card.id = `question${index}`;
        
        // Actualizar índices en los nombres de campos
        const inputs = card.querySelectorAll('input, select');
        inputs.forEach(input => {
            const name = input.getAttribute('name');
            if (name) {
                input.setAttribute('name', name.replace(/questions\[\d+\]/, `questions[${index}]`));
            }
            
            const id = input.getAttribute('id');
            if (id && id.startsWith('question_')) {
                input.setAttribute('id', id.replace(/_\d+$/, `_${index}`));
            }
        });
        
        // Actualizar data-index en select y botón añadir opción
        const typeSelect = card.querySelector('.question-type');
        if (typeSelect) {
            typeSelect.setAttribute('data-index', index);
        }
        
        const addOptionBtn = card.querySelector('.add-option');
        if (addOptionBtn) {
            addOptionBtn.setAttribute('data-index', index);
        }
        
        // Actualizar ID de contenedor de opciones
        const optionsContainer = card.querySelector('.options-container');
        if (optionsContainer) {
            optionsContainer.id = `options_container_${index}`;
        }
        
        // Actualizar ID de lista de opciones
        const optionsList = card.querySelector('.options-list');
        if (optionsList) {
            optionsList.id = `options_list_${index}`;
        }
    });
}

/**
 * Ordenar y reindexar inputs de opción
 */
function reindexOptions(optionsList, questionIndex) {
    const options = optionsList.querySelectorAll('.input-group');
    options.forEach((option, index) => {
        const input = option.querySelector('input');
        if (input) {
            input.setAttribute('name', `questions[${questionIndex}][options][${index}]`);
            input.setAttribute('placeholder', `Opción ${index + 1}`);
        }
    });
}

/**
 * Modo de vista previa para el formulario de aplicación
 */
const previewButton = document.getElementById('previewForm');
if (previewButton) {
    previewButton.addEventListener('click', function() {
        // Recopilar datos del formulario
        const title = document.getElementById('titulo').value || 'Título de la Vacante';
        const questions = [];
        
        document.querySelectorAll('.custom-question').forEach((card, index) => {
            const text = card.querySelector(`input[name="questions[${index}][text]"]`).value;
            const type = card.querySelector(`select[name="questions[${index}][type]"]`).value;
            const required = card.querySelector(`input[name="questions[${index}][required]"]`).checked;
            
            const options = [];
            if (type === 'select' || type === 'checkbox') {
                card.querySelectorAll(`input[name^="questions[${index}][options]"]`).forEach(input => {
                    if (input.value.trim()) {
                        options.push(input.value.trim());
                    }
                });
            }
            
            if (text.trim()) {
                questions.push({
                    text,
                    type,
                    required,
                    options
                });
            }
        });
        
        // Generar HTML de vista previa
        let previewHtml = `
            <div class="modal-header">
                <h5 class="modal-title">Vista Previa: ${title}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form class="application-form preview">
                    <h3>Formulario de Aplicación</h3>
        `;
        
        // Añadir preguntas
        questions.forEach((question, index) => {
            previewHtml += `
                <div class="form-group mb-3">
                    <label for="preview_q${index}">${question.text} ${question.required ? '<span class="text-danger">*</span>' : ''}</label>
            `;
            
            switch (question.type) {
                case 'text':
                    previewHtml += `
                        <input type="text" class="form-control" id="preview_q${index}" ${question.required ? 'required' : ''}>
                    `;
                    break;
                case 'select':
                    previewHtml += `
                        <select class="form-control" id="preview_q${index}" ${question.required ? 'required' : ''}>
                            <option value="">Selecciona una opción</option>
                    `;
                    
                    question.options.forEach(option => {
                        previewHtml += `<option value="${option}">${option}</option>`;
                    });
                    
                    previewHtml += `</select>`;
                    break;
                case 'checkbox':
                    question.options.forEach((option, optIndex) => {
                        previewHtml += `
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="preview_q${index}_opt${optIndex}">
                                <label class="form-check-label" for="preview_q${index}_opt${optIndex}">${option}</label>
                            </div>
                        `;
                    });
                    break;
            }
            
            previewHtml += `</div>`;
        });
        
        // Cerrar formulario
        previewHtml += `
                    <div class="text-center mt-4">
                        <button type="button" class="btn btn-primary" disabled>Enviar Aplicación</button>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        `;
        
        // Actualizar modal y mostrar
        const previewModal = document.getElementById('previewModal');
        if (previewModal) {
            const modalContent = previewModal.querySelector('.modal-content');
            modalContent.innerHTML = previewHtml;
            
            const modal = new bootstrap.Modal(previewModal);
            modal.show();
        }
    });
}

/**
 * Drag and drop para subida de archivos
 */
const dropZones = document.querySelectorAll('.file-drop-zone');

dropZones.forEach(zone => {
    const input = zone.querySelector('input[type="file"]');
    const preview = zone.querySelector('.file-preview');
    
    // Prevenir navegador de abrir el archivo
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        zone.addEventListener(eventName, preventDefaults, false);
    });
    
    // Destacar la zona al arrastrar
    ['dragenter', 'dragover'].forEach(eventName => {
        zone.addEventListener(eventName, highlight, false);
    });
    
    ['dragleave', 'drop'].forEach(eventName => {
        zone.addEventListener(eventName, unhighlight, false);
    });
    
    // Manejar archivos soltados
    zone.addEventListener('drop', handleDrop, false);
    
    // Manejar cambio en input
    if (input) {
        input.addEventListener('change', function() {
            handleFiles(this.files);
        });
    }
    
    function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }
    
    function highlight() {
        zone.classList.add('highlight');
    }
    
    function unhighlight() {
        zone.classList.remove('highlight');
    }
    
    function handleDrop(e) {
        const dt = e.dataTransfer;
        const files = dt.files;
        
        handleFiles(files);
    }
    
    function handleFiles(files) {
        if (!files.length || !preview) return;
        
        // Limpiar vista previa
        preview.innerHTML = '';
        
        // Mostrar información del archivo
        const file = files[0];
        
        // Verificar tipo de archivo
        const allowedTypes = input ? input.accept.split(',') : [];
        const isValidType = allowedTypes.length === 0 || allowedTypes.some(type => {
            if (type.startsWith('.')) {
                // Extensión
                return file.name.toLowerCase().endsWith(type.toLowerCase());
            } else {
                // MIME type
                return file.type === type;
            }
        });
        
        if (!isValidType) {
            preview.innerHTML = `<div class="alert alert-danger">Tipo de archivo no válido</div>`;
            return;
        }
        
        // Crear elemento de vista previa
        const fileInfo = document.createElement('div');
        fileInfo.className = 'file-info';
        
        // Ícono según tipo
        let icon = 'file';
        if (file.type.startsWith('image/')) {
            icon = 'file-image';
        } else if (file.type.includes('pdf')) {
            icon = 'file-pdf';
        } else if (file.type.includes('word') || file.name.endsWith('.doc') || file.name.endsWith('.docx')) {
            icon = 'file-word';
        } else if (file.type.includes('excel') || file.name.endsWith('.xls') || file.name.endsWith('.xlsx')) {
            icon = 'file-excel';
        }
        
        fileInfo.innerHTML = `
            <i class="fas fa-${icon}"></i>
            <div class="file-details">
                <span class="file-name">${file.name}</span>
                <span class="file-size">${formatFileSize(file.size)}</span>
            </div>
            <button type="button" class="btn btn-sm btn-outline-danger remove-file">
                <i class="fas fa-times"></i>
            </button>
        `;
        
        preview.appendChild(fileInfo);
        
        // Evento para eliminar archivo
        const removeButton = preview.querySelector('.remove-file');
        if (removeButton) {
            removeButton.addEventListener('click', function() {
                preview.innerHTML = '';
                if (input) {
                    input.value = '';
                }
            });
        }
    }
    
    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }
});button" class="btn btn-sm btn-outline-danger remove-question">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="card-body">
                <div class="form-group mb-3">
                    <label for="question_text_${questionIndex}">Texto de la pregunta</label>
                    <input type="text" class="form-control" id="question_text_${questionIndex}" name="questions[${questionIndex}][text]" required>
                </div>
                <div class="form-group mb-3">
                    <label for="question_type_${questionIndex}">Tipo de pregunta</label>
                    <select class="form-control question-type" id="question_type_${questionIndex}" name="questions[${questionIndex}][type]" data-index="${questionIndex}">
                        <option value="text">Texto</option>
                        <option value="select">Selección</option>
                        <option value="checkbox">Casilla de verificación</option>
                    </select>
                </div>
                <div class="options-container" id="options_container_${questionIndex}" style="display: none;">
                    <div class="form-group mb-3">
                        <label>Opciones</label>
                        <div class="options-list" id="options_list_${questionIndex}">
                            <div class="input-group mb-2">
                                <input type="text" class="form-control" name="questions[${questionIndex}][options][0]" placeholder="Opción 1">
                                <button type="