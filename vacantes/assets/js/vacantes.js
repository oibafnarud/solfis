/**
 * vacantes.js - Script para el portal de empleos de SolFis
 */

document.addEventListener('DOMContentLoaded', function() {
    // Inicializar componentes
    initSearchFilters();
    initFaqToggle();
    initFormTabs();
    initFileUpload();
    initCVUpload();
    initCandidateProfile();
    initPipelineBoard();
    initNotifications();
    
    // Easter egg :)
    console.log("%c¡Bienvenido al portal de empleos de SolFis!", "color:#00B1EB; font-size:20px; font-weight:bold");
    console.log("%c¿Eres desarrollador? ¡Estamos contratando! Revisa nuestras vacantes de tecnología.", "color:#002C6B; font-size:14px");
});

/**
 * Inicializar filtros de búsqueda avanzada
 */
function initSearchFilters() {
    const toggleAdvanced = document.getElementById('toggleAdvanced');
    const advancedFields = document.getElementById('advancedFields');
    
    if (toggleAdvanced && advancedFields) {
        toggleAdvanced.addEventListener('click', function(e) {
            e.preventDefault();
            advancedFields.classList.toggle('show');
            this.querySelector('i').classList.toggle('fa-chevron-down');
            this.querySelector('i').classList.toggle('fa-chevron-up');
        });
    }
    
    // Filtros en la barra lateral
    const filterForm = document.getElementById('filterForm');
    if (filterForm) {
        const filterInputs = filterForm.querySelectorAll('input, select');
        
        filterInputs.forEach(input => {
            input.addEventListener('change', function() {
                filterForm.submit();
            });
        });
    }
}

/**
 * Inicializar acordeón de preguntas frecuentes
 */
function initFaqToggle() {
    const faqItems = document.querySelectorAll('.faq-question');
    
    faqItems.forEach(item => {
        item.addEventListener('click', () => {
            // Cerrar otros items
            faqItems.forEach(otherItem => {
                if (otherItem !== item) {
                    otherItem.parentElement.classList.remove('active');
                    const icon = otherItem.querySelector('.faq-toggle i');
                    icon.classList.remove('fa-minus');
                    icon.classList.add('fa-plus');
                }
            });
            
            // Abrir/cerrar item actual
            item.parentElement.classList.toggle('active');
            const icon = item.querySelector('.faq-toggle i');
            icon.classList.toggle('fa-plus');
            icon.classList.toggle('fa-minus');
        });
    });
}

/**
 * Inicializar tabs en formularios
 */
function initFormTabs() {
    const tabs = document.querySelectorAll('.apply-tab');
    const contents = document.querySelectorAll('.apply-content');
    
    if (tabs.length && contents.length) {
        tabs.forEach(tab => {
            tab.addEventListener('click', () => {
                // Desactivar todos los tabs y contenidos
                tabs.forEach(t => t.classList.remove('active'));
                contents.forEach(c => c.classList.remove('active'));
                
                // Activar el tab y contenido actual
                tab.classList.add('active');
                const target = tab.getAttribute('data-target');
                document.getElementById(target).classList.add('active');
                
                // Actualizar progreso si existe
                updateProgress();
            });
        });
        
        // Activar el primer tab por defecto
        tabs[0].click();
    }
}

/**
 * Actualizar barra de progreso
 */
function updateProgress() {
    const progressBar = document.querySelector('.progress-fill');
    const activeTab = document.querySelector('.apply-tab.active');
    
    if (progressBar && activeTab) {
        const totalTabs = document.querySelectorAll('.apply-tab').length;
        const currentIndex = Array.from(document.querySelectorAll('.apply-tab')).indexOf(activeTab) + 1;
        const progressPercent = (currentIndex / totalTabs) * 100;
        
        progressBar.style.width = `${progressPercent}%`;
        
        // Actualizar estado de los pasos
        const steps = document.querySelectorAll('.progress-step');
        steps.forEach((step, index) => {
            if (index + 1 < currentIndex) {
                step.classList.add('completed');
                step.classList.remove('active');
            } else if (index + 1 === currentIndex) {
                step.classList.add('active');
                step.classList.remove('completed');
            } else {
                step.classList.remove('active');
                step.classList.remove('completed');
            }
        });
    }
}

/**
 * Inicializar carga de archivos
 */
function initFileUpload() {
    const fileInputs = document.querySelectorAll('.form-file-input');
    
    fileInputs.forEach(input => {
        input.addEventListener('change', function(e) {
            const fileName = e.target.files[0]?.name || 'Ningún archivo seleccionado';
            const fileSize = e.target.files[0]?.size || 0;
            const fileLabel = this.parentElement.querySelector('.form-file-label');
            
            if (fileLabel) {
                // Mostrar nombre y tamaño del archivo
                const formattedSize = formatFileSize(fileSize);
                fileLabel.textContent = `${fileName} (${formattedSize})`;
            }
            
            // Si hay una lista de archivos, agregar el nuevo archivo
            const fileList = document.querySelector('.file-list');
            if (fileList && e.target.files[0]) {
                addFileToList(fileList, e.target.files[0]);
            }
        });
    });
    
    // Botones para eliminar archivos
    document.addEventListener('click', function(e) {
        if (e.target.closest('.file-action.delete')) {
            const fileItem = e.target.closest('.file-item');
            if (fileItem) {
                fileItem.remove();
            }
        }
    });
}

/**
 * Agregar archivo a la lista
 */
function addFileToList(fileList, file) {
    const fileId = 'file-' + Date.now();
    const fileItem = document.createElement('div');
    fileItem.className = 'file-item';
    fileItem.dataset.id = fileId;
    
    // Determinar el tipo de icono según la extensión
    const fileExtension = file.name.split('.').pop().toLowerCase();
    let fileIcon = 'fas fa-file';
    
    if (['pdf'].includes(fileExtension)) {
        fileIcon = 'fas fa-file-pdf';
    } else if (['doc', 'docx'].includes(fileExtension)) {
        fileIcon = 'fas fa-file-word';
    } else if (['xls', 'xlsx', 'csv'].includes(fileExtension)) {
        fileIcon = 'fas fa-file-excel';
    } else if (['jpg', 'jpeg', 'png', 'gif'].includes(fileExtension)) {
        fileIcon = 'fas fa-file-image';
    }
    
    // HTML para el elemento de archivo
    fileItem.innerHTML = `
        <div class="file-icon">
            <i class="${fileIcon}"></i>
        </div>
        <div class="file-info">
            <div class="file-name">${file.name}</div>
            <div class="file-size">${formatFileSize(file.size)}</div>
        </div>
        <div class="file-actions">
            <button type="button" class="file-action delete" title="Eliminar">
                <i class="fas fa-trash"></i>
            </button>
        </div>
    `;
    
    fileList.appendChild(fileItem);
}

/**
 * Formatear tamaño de archivo
 */
function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

/**
 * Inicializar carga de CV con drag & drop
 */
function initCVUpload() {
    const dropzone = document.querySelector('.cv-dropzone');
    const fileInput = document.querySelector('#cv-file');
    
    if (dropzone && fileInput) {
        // Prevenir comportamiento por defecto
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            dropzone.addEventListener(eventName, preventDefaults, false);
        });
        
        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }
        
        // Resaltar la zona al arrastrar
        ['dragenter', 'dragover'].forEach(eventName => {
            dropzone.addEventListener(eventName, highlight, false);
        });
        
        ['dragleave', 'drop'].forEach(eventName => {
            dropzone.addEventListener(eventName, unhighlight, false);
        });
        
        function highlight() {
            dropzone.classList.add('highlight');
        }
        
        function unhighlight() {
            dropzone.classList.remove('highlight');
        }
        
        // Manejar archivos soltados
        dropzone.addEventListener('drop', handleDrop, false);
        
        function handleDrop(e) {
            const dt = e.dataTransfer;
            const files = dt.files;
            
            fileInput.files = files;
            
            // Disparar el evento change manualmente
            const event = new Event('change', { bubbles: true });
            fileInput.dispatchEvent(event);
        }
        
        // Al hacer click en la zona, activar el input
        dropzone.addEventListener('click', () => {
            fileInput.click();
        });
    }
}

/**
 * Inicializar perfil de candidato
 */
function initCandidateProfile() {
    // Mostrar/ocultar el formulario de edición
    const editButtons = document.querySelectorAll('.btn-edit-section');
    
    editButtons.forEach(button => {
        button.addEventListener('click', function() {
            const sectionId = this.getAttribute('data-section');
            const viewSection = document.getElementById(`${sectionId}-view`);
            const editSection = document.getElementById(`${sectionId}-edit`);
            
            if (viewSection && editSection) {
                viewSection.classList.toggle('d-none');
                editSection.classList.toggle('d-none');
            }
        });
    });
    
    // Botones para cancelar edición
    const cancelButtons = document.querySelectorAll('.btn-cancel-edit');
    
    cancelButtons.forEach(button => {
        button.addEventListener('click', function() {
            const sectionId = this.getAttribute('data-section');
            const viewSection = document.getElementById(`${sectionId}-view`);
            const editSection = document.getElementById(`${sectionId}-edit`);
            
            if (viewSection && editSection) {
                viewSection.classList.toggle('d-none');
                editSection.classList.toggle('d-none');
            }
        });
    });
}

/**
 * Inicializar tablero de pipeline (kanban)
 */
function initPipelineBoard() {
    const pipelineBoard = document.querySelector('.pipeline-board');
    
    if (pipelineBoard) {
        // Hacer que las tarjetas sean arrastrables entre columnas
        // Nota: Esta es una implementación básica, para una completa use una librería como SortableJS
        
        const candidateCards = document.querySelectorAll('.candidate-card');
        const pipelineColumns = document.querySelectorAll('.pipeline-column .pipeline-body');
        
        candidateCards.forEach(card => {
            card.setAttribute('draggable', true);
            
            card.addEventListener('dragstart', function(e) {
                e.dataTransfer.setData('text/plain', card.id);
                setTimeout(() => {
                    card.classList.add('dragging');
                }, 0);
            });
            
            card.addEventListener('dragend', function() {
                card.classList.remove('dragging');
            });
        });
        
        pipelineColumns.forEach(column => {
            column.addEventListener('dragover', function(e) {
                e.preventDefault();
                column.classList.add('dragover');
            });
            
            column.addEventListener('dragleave', function() {
                column.classList.remove('dragover');
            });
            
            column.addEventListener('drop', function(e) {
                e.preventDefault();
                column.classList.remove('dragover');
                
                const cardId = e.dataTransfer.getData('text/plain');
                const card = document.getElementById(cardId);
                
                if (card && column.closest('.pipeline-column') !== card.closest('.pipeline-column')) {
                    column.appendChild(card);
                    
                    // Aquí se podría hacer una petición AJAX para actualizar la etapa en la base de datos
                    const candidateId = card.getAttribute('data-id');
                    const stageId = column.closest('.pipeline-column').getAttribute('data-stage-id');
                    
                    console.log(`Candidato ${candidateId} movido a etapa ${stageId}`);
                    
                    // Actualizar contadores
                    updateColumnCounters();
                }
            });
        });
        
        function updateColumnCounters() {
            document.querySelectorAll('.pipeline-column').forEach(column => {
                const counter = column.querySelector('.pipeline-count');
                const cards = column.querySelectorAll('.candidate-card').length;
                
                if (counter) {
                    counter.textContent = cards;
                }
            });
        }
    }
}

/**
 * Inicializar sistema de notificaciones
 */
function initNotifications() {
    // Mostrar notificaciones automáticas si hay mensajes en la URL
    const urlParams = new URLSearchParams(window.location.search);
    const successMsg = urlParams.get('success');
    const errorMsg = urlParams.get('error');
    const infoMsg = urlParams.get('info');
    
    if (successMsg) {
        showNotification('success', 'Éxito', decodeURIComponent(successMsg));
    }
    
    if (errorMsg) {
        showNotification('error', 'Error', decodeURIComponent(errorMsg));
    }
    
    if (infoMsg) {
        showNotification('info', 'Información', decodeURIComponent(infoMsg));
    }
    
    // Cerrar notificaciones al hacer clic en el botón X
    document.addEventListener('click', function(e) {
        if (e.target.closest('.notification-close')) {
            const notification = e.target.closest('.notification');
            if (notification) {
                notification.remove();
            }
        }
    });
}

/**
 * Mostrar una notificación
 */
function showNotification(type, title, message) {
    // Crear el elemento de notificación
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    
    // Determinar icono según el tipo
    let icon = 'fas fa-info-circle';
    if (type === 'success') {
        icon = 'fas fa-check-circle';
    } else if (type === 'error') {
        icon = 'fas fa-exclamation-circle';
    }
    
    // HTML para la notificación
    notification.innerHTML = `
        <div class="notification-icon">
            <i class="${icon}"></i>
        </div>
        <div class="notification-content">
            <h4 class="notification-title">${title}</h4>
            <p class="notification-message">${message}</p>
        </div>
        <button class="notification-close">
            <i class="fas fa-times"></i>
        </button>
    `;
    
    // Agregar al DOM
    document.body.appendChild(notification);
    
    // Eliminar después de 5 segundos
    setTimeout(() => {
        if (document.body.contains(notification)) {
            notification.remove();
        }
    }, 5000);
}