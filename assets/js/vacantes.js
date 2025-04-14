/**
 * vacantes.js - Funcionalidades JavaScript para el portal de empleos de SolFis
 */

document.addEventListener('DOMContentLoaded', function() {
    // Inicialización de componentes
    initializeFilters();
    initializeSearchForm();
    initializeFileInputs();
    initializeFormValidation();
    initializeTestimonialSlider();
});

/**
 * Inicializa los filtros en el listado de vacantes
 */
function initializeFilters() {
    const filterButtons = document.querySelectorAll('.filter-btn');
    
    if (filterButtons.length > 0) {
        filterButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                // Si no es un enlace real con href (para casos de filtros en la misma página)
                if (this.getAttribute('href') === '#' || this.getAttribute('data-filter')) {
                    e.preventDefault();
                    
                    // Remover clase active de todos los botones
                    filterButtons.forEach(btn => btn.classList.remove('active'));
                    
                    // Agregar clase active al botón clickeado
                    this.classList.add('active');
                    
                    const filter = this.getAttribute('data-filter');
                    filterJobCards(filter);
                }
            });
        });
    }
}

/**
 * Filtra las tarjetas de trabajo según el filtro seleccionado
 */
function filterJobCards(filter) {
    const jobCards = document.querySelectorAll('.job-card');
    
    if (jobCards.length > 0) {
        jobCards.forEach(card => {
            if (filter === 'all') {
                card.style.display = 'block';
            } else {
                const cardCategory = card.getAttribute('data-category');
                const cardLocation = card.getAttribute('data-location');
                const cardType = card.getAttribute('data-type');
                
                if (cardCategory === filter || cardLocation === filter || cardType === filter) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            }
        });
    }
}

/**
 * Inicializa el formulario de búsqueda
 */
function initializeSearchForm() {
    const searchForm = document.querySelector('.search-form');
    
    if (searchForm) {
        searchForm.addEventListener('submit', function(e) {
            const searchInput = this.querySelector('input[name="q"]').value.trim();
            const locationSelect = this.querySelector('select[name="ubicacion"]').value;
            const categorySelect = this.querySelector('select[name="categoria"]').value;
            
            // Validación básica - se puede expandir según necesidades
            if (searchInput === '' && locationSelect === '' && categorySelect === '') {
                e.preventDefault();
                alert('Por favor, introduce al menos un criterio de búsqueda.');
            }
        });
    }
}

/**
 * Inicializa los campos de tipo file para mejorar la UX
 */
function initializeFileInputs() {
    const fileInputs = document.querySelectorAll('input[type="file"]');
    
    if (fileInputs.length > 0) {
        fileInputs.forEach(input => {
            const fileNameSpan = document.getElementById('file-name');
            
            if (fileNameSpan) {
                input.addEventListener('change', function(e) {
                    if (this.files.length > 0) {
                        const fileName = this.files[0].name;
                        fileNameSpan.textContent = fileName;
                    } else {
                        fileNameSpan.textContent = 'Arrastra y suelta tu CV o haz clic para seleccionar';
                    }
                });
            }
        });
    }
}

/**
 * Inicializa la validación de formularios
 */
function initializeFormValidation() {
    const applicationForm = document.querySelector('.application-form');
    
    if (applicationForm) {
        applicationForm.addEventListener('submit', function(e) {
            let isValid = true;
            const requiredFields = this.querySelectorAll('input[required], select[required], textarea[required]');
            
            // Limpiar mensajes de error previos
            const errorMessages = this.querySelectorAll('.form-error');
            errorMessages.forEach(message => message.remove());
            
            // Validar campos requeridos
            requiredFields.forEach(field => {
                const formGroup = field.closest('.form-group');
                formGroup.classList.remove('has-error');
                
                if (field.value.trim() === '') {
                    isValid = false;
                    formGroup.classList.add('has-error');
                    
                    const errorMessage = document.createElement('div');
                    errorMessage.className = 'form-error';
                    errorMessage.textContent = 'Este campo es requerido';
                    formGroup.appendChild(errorMessage);
                }
            });
            
            // Validar email si existe
            const emailField = this.querySelector('input[type="email"]');
            if (emailField && emailField.value.trim() !== '') {
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                const formGroup = emailField.closest('.form-group');
                
                if (!emailRegex.test(emailField.value.trim())) {
                    isValid = false;
                    formGroup.classList.add('has-error');
                    
                    const errorMessage = document.createElement('div');
                    errorMessage.className = 'form-error';
                    errorMessage.textContent = 'Introduce un email válido';
                    formGroup.appendChild(errorMessage);
                }
            }
            
            // Validar tamaño y tipo de archivo CV
            const cvField = this.querySelector('input[name="cv"]');
            if (cvField && cvField.files.length > 0) {
                const file = cvField.files[0];
                const formGroup = cvField.closest('.form-group');
                const maxSize = 5 * 1024 * 1024; // 5MB
                const allowedTypes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
                
                if (file.size > maxSize) {
                    isValid = false;
                    formGroup.classList.add('has-error');
                    
                    const errorMessage = document.createElement('div');
                    errorMessage.className = 'form-error';
                    errorMessage.textContent = 'El archivo excede el tamaño máximo permitido (5MB)';
                    formGroup.appendChild(errorMessage);
                } else if (!allowedTypes.includes(file.type)) {
                    isValid = false;
                    formGroup.classList.add('has-error');
                    
                    const errorMessage = document.createElement('div');
                    errorMessage.className = 'form-error';
                    errorMessage.textContent = 'Solo se permiten archivos PDF, DOC o DOCX';
                    formGroup.appendChild(errorMessage);
                }
            }
            
            // Validar checkbox de términos y condiciones
            const termsCheckbox = this.querySelector('input[name="terminos"]');
            if (termsCheckbox && !termsCheckbox.checked) {
                isValid = false;
                const formGroup = termsCheckbox.closest('.form-group');
                formGroup.classList.add('has-error');
                
                const errorMessage = document.createElement('div');
                errorMessage.className = 'form-error';
                errorMessage.textContent = 'Debes aceptar los términos y condiciones';
                formGroup.appendChild(errorMessage);
            }
            
            // Si no es válido, prevenir envío
            if (!isValid) {
                e.preventDefault();
                
                // Scroll al primer error
                const firstError = this.querySelector('.has-error');
                if (firstError) {
                    firstError.scrollIntoView({
                        behavior: 'smooth',
                        block: 'center'
                    });
                }
            }
        });
    }
}

/**
 * Inicializa el slider de testimonios
 */
function initializeTestimonialSlider() {
    const testimonialSlider = document.querySelector('.testimonials-slider');
    
    if (testimonialSlider) {
        // Si hay muchos testimonios, implementar un slider simple
        const testimonials = testimonialSlider.querySelectorAll('.testimonial-card');
        
        if (testimonials.length > 3) {
            let currentIndex = 0;
            const itemsToShow = window.innerWidth < 768 ? 1 : (window.innerWidth < 992 ? 2 : 3);
            
            // Ocultar todos excepto los primeros itemsToShow
            testimonials.forEach((testimonial, index) => {
                if (index >= itemsToShow) {
                    testimonial.style.display = 'none';
                }
            });
            
            // Crear controles de navegación
            const controls = document.createElement('div');
            controls.className = 'slider-controls';
            
            const prevButton = document.createElement('button');
            prevButton.className = 'slider-prev';
            prevButton.innerHTML = '<i class="fas fa-chevron-left"></i>';
            
            const nextButton = document.createElement('button');
            nextButton.className = 'slider-next';
            nextButton.innerHTML = '<i class="fas fa-chevron-right"></i>';
            
            controls.appendChild(prevButton);
            controls.appendChild(nextButton);
            
            testimonialSlider.parentNode.appendChild(controls);
            
            // Funcionalidad de los botones
            prevButton.addEventListener('click', function() {
                if (currentIndex > 0) {
                    currentIndex--;
                    updateTestimonialSlider(testimonials, currentIndex, itemsToShow);
                }
            });
            
            nextButton.addEventListener('click', function() {
                if (currentIndex < testimonials.length - itemsToShow) {
                    currentIndex++;
                    updateTestimonialSlider(testimonials, currentIndex, itemsToShow);
                }
            });
            
            // Actualizar estado inicial
            updateButtonState(prevButton, nextButton, currentIndex, testimonials.length, itemsToShow);
        }
    }
}

/**
 * Actualiza el slider de testimonios
 */
function updateTestimonialSlider(testimonials, currentIndex, itemsToShow) {
    testimonials.forEach((testimonial, index) => {
        if (index >= currentIndex && index < currentIndex + itemsToShow) {
            testimonial.style.display = 'block';
        } else {
            testimonial.style.display = 'none';
        }
    });
    
    // Actualizar estado de los botones
    const prevButton = document.querySelector('.slider-prev');
    const nextButton = document.querySelector('.slider-next');
    
    updateButtonState(prevButton, nextButton, currentIndex, testimonials.length, itemsToShow);
}

/**
 * Actualiza el estado de los botones de navegación
 */
function updateButtonState(prevButton, nextButton, currentIndex, totalItems, itemsToShow) {
    prevButton.disabled = currentIndex === 0;
    nextButton.disabled = currentIndex >= totalItems - itemsToShow;
    
    prevButton.classList.toggle('disabled', prevButton.disabled);
    nextButton.classList.toggle('disabled', nextButton.disabled);
}

/**
 * Función para manejar la funcionalidad de guardar/favoritos
 */
document.addEventListener('click', function(e) {
    if (e.target.matches('.btn-save-job') || e.target.closest('.btn-save-job')) {
        const button = e.target.matches('.btn-save-job') ? e.target : e.target.closest('.btn-save-job');
        
        // Toggle clase para cambiar estilo
        button.classList.toggle('saved');
        
        // Cambiar ícono
        const icon = button.querySelector('i');
        if (icon) {
            if (button.classList.contains('saved')) {
                icon.classList.remove('far', 'fa-bookmark');
                icon.classList.add('fas', 'fa-bookmark');
            } else {
                icon.classList.remove('fas', 'fa-bookmark');
                icon.classList.add('far', 'fa-bookmark');
            }
        }
        
        // Aquí podríamos enviar la información al servidor mediante AJAX
        // Si el usuario está autenticado
        const jobId = button.getAttribute('data-job-id');
        console.log('Toggle saved state for job: ' + jobId);
    }
});

/**
 * Toggle de móvil para filtros
 */
document.addEventListener('click', function(e) {
    if (e.target.matches('.filter-toggle') || e.target.closest('.filter-toggle')) {
        const filterSidebar = document.querySelector('.filter-sidebar');
        
        if (filterSidebar) {
            filterSidebar.classList.toggle('active');
        }
    }
});

/**
 * Cerrar modales al hacer clic fuera
 */
document.addEventListener('click', function(e) {
    const modal = document.querySelector('.modal.active');
    
    if (modal && !e.target.closest('.modal-content') && !e.target.closest('.modal-trigger')) {
        modal.classList.remove('active');
    }
});

/**
 * Manejo de ordenamiento en listado
 */
const sortingSelect = document.getElementById('sorting');
if (sortingSelect) {
    sortingSelect.addEventListener('change', function() {
        const url = new URL(window.location);
        url.searchParams.set('sort', this.value);
        window.location.href = url.toString();
    });
}

/**
 * Inicialización de tooltip
 */
function initTooltips() {
    const tooltips = document.querySelectorAll('[data-tooltip]');
    
    tooltips.forEach(tooltip => {
        tooltip.addEventListener('mouseenter', function() {
            const text = this.getAttribute('data-tooltip');
            
            const tooltipElement = document.createElement('div');
            tooltipElement.className = 'tooltip';
            tooltipElement.textContent = text;
            
            document.body.appendChild(tooltipElement);
            
            const rect = this.getBoundingClientRect();
            tooltipElement.style.top = (rect.top - tooltipElement.offsetHeight - 10) + 'px';
            tooltipElement.style.left = (rect.left + (rect.width / 2) - (tooltipElement.offsetWidth / 2)) + 'px';
            tooltipElement.classList.add('active');
        });
        
        tooltip.addEventListener('mouseleave', function() {
            const tooltipElement = document.querySelector('.tooltip.active');
            if (tooltipElement) {
                tooltipElement.remove();
            }
        });
    });
}

// Inicializar tooltips cuando el documento está listo
document.addEventListener('DOMContentLoaded', initTooltips);

/**
 * Manejo de paginación con fetch para actualización dinámica
 */
document.addEventListener('click', function(e) {
    if (e.target.matches('.pagination-link') || e.target.closest('.pagination-link')) {
        // Solo si estamos en el listado de vacantes
        if (document.querySelector('.jobs-listing')) {
            e.preventDefault();
            
            const link = e.target.matches('.pagination-link') ? e.target : e.target.closest('.pagination-link');
            const url = link.getAttribute('href');
            
            if (url) {
                // Extraer el número de página para actualizar la URL sin recargar
                const pageMatch = url.match(/[?&]page=(\d+)/);
                const page = pageMatch ? pageMatch[1] : '1';
                
                // Actualizar la URL sin recargar la página
                const currentUrl = new URL(window.location);
                currentUrl.searchParams.set('page', page);
                window.history.pushState({}, '', currentUrl);
                
                // Mostrar indicador de carga
                const jobsGrid = document.querySelector('.jobs-grid');
                jobsGrid.innerHTML = '<div class="loading-spinner"></div>';
                
                // Realizar la petición AJAX para actualizar solo los resultados
                fetch(url)
                    .then(response => response.text())
                    .then(html => {
                        const parser = new DOMParser();
                        const doc = parser.parseFromString(html, 'text/html');
                        
                        // Actualizar la grilla de trabajos
                        const newJobsGrid = doc.querySelector('.jobs-grid');
                        jobsGrid.innerHTML = newJobsGrid.innerHTML;
                        
                        // Actualizar la paginación
                        const pagination = document.querySelector('.pagination');
                        const newPagination = doc.querySelector('.pagination');
                        if (pagination && newPagination) {
                            pagination.innerHTML = newPagination.innerHTML;
                        }
                        
                        // Actualizar contador de resultados
                        const resultsCount = document.querySelector('.results-count');
                        const newResultsCount = doc.querySelector('.results-count');
                        if (resultsCount && newResultsCount) {
                            resultsCount.innerHTML = newResultsCount.innerHTML;
                        }
                        
                        // Desplazarse al inicio de los resultados
                        document.querySelector('.jobs-listing').scrollIntoView({
                            behavior: 'smooth',
                            block: 'start'
                        });
                    })
                    .catch(error => {
                        console.error('Error al cargar la página:', error);
                        // Recargar la página en caso de error
                        window.location.href = url;
                    });
            }
        }
    }
<<<<<<< HEAD
});

/**
 * Toggle de filtros en dispositivos móviles
 */
document.addEventListener('DOMContentLoaded', function() {
    // Agregar overlay para filtros en móvil
    if (document.querySelector('.filter-sidebar')) {
        const overlay = document.createElement('div');
        overlay.className = 'filter-overlay';
        document.body.appendChild(overlay);
        
        // Botón de cerrar filtros en móvil
        const filterSidebar = document.querySelector('.filter-sidebar');
        const filterHeader = filterSidebar.querySelector('.filter-header');
        
        if (filterHeader) {
            const closeBtn = document.createElement('button');
            closeBtn.className = 'filter-close-btn';
            closeBtn.innerHTML = '<i class="fas fa-times"></i>';
            closeBtn.setAttribute('aria-label', 'Cerrar filtros');
            filterHeader.appendChild(closeBtn);
            
            closeBtn.addEventListener('click', function() {
                filterSidebar.classList.remove('active');
                overlay.classList.remove('active');
                document.body.style.overflow = '';
            });
        }
        
        // Toggle botón de filtros
        const filterToggle = document.getElementById('mobileFilterToggle');
        if (filterToggle) {
            filterToggle.addEventListener('click', function() {
                filterSidebar.classList.add('active');
                overlay.classList.add('active');
                document.body.style.overflow = 'hidden'; // Evitar scroll del body
            });
        }
        
        // Cerrar filtros al hacer clic en overlay
        overlay.addEventListener('click', function() {
            filterSidebar.classList.remove('active');
            overlay.classList.remove('active');
            document.body.style.overflow = '';
        });
    }
    
    // Mejorar la funcionalidad de autosubmit
    const enhanceAutosubmit = function() {
        const form = document.getElementById('filterForm');
        if (!form) return;
        
        // Agregar mensaje de carga
        const createLoadingIndicator = function() {
            const loadingDiv = document.createElement('div');
            loadingDiv.className = 'loading-indicator';
            loadingDiv.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Cargando...';
            return loadingDiv;
        };
        
        // Manejar cambios en checkboxes y radios
        const radioFilters = form.querySelectorAll('input[type="radio"]');
        radioFilters.forEach(filter => {
            filter.addEventListener('change', function() {
                const loadingIndicator = createLoadingIndicator();
                document.querySelector('.jobs-list').appendChild(loadingIndicator);
                
                // Pequeño retraso para mostrar la animación
                setTimeout(() => {
                    form.submit();
                }, 300);
            });
        });
        
        // Manejar cambios en selects
        const selectFilters = form.querySelectorAll('select');
        selectFilters.forEach(filter => {
            filter.addEventListener('change', function() {
                const loadingIndicator = createLoadingIndicator();
                document.querySelector('.jobs-list').appendChild(loadingIndicator);
                
                // Pequeño retraso para mostrar la animación
                setTimeout(() => {
                    form.submit();
                }, 300);
            });
        });
    };
    
    enhanceAutosubmit();
    
    // Mejorar la subida de archivos
    const enhanceFileUpload = function() {
        const fileInput = document.getElementById('cv');
        const fileContainer = document.querySelector('.file-upload-container');
        
        if (!fileInput || !fileContainer) return;
        
        // Manejar drag and drop
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            fileContainer.addEventListener(eventName, preventDefaults, false);
        });
        
        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }
        
        // Estilo activo
        ['dragenter', 'dragover'].forEach(eventName => {
            fileContainer.addEventListener(eventName, highlight, false);
        });
        
        ['dragleave', 'drop'].forEach(eventName => {
            fileContainer.addEventListener(eventName, unhighlight, false);
        });
        
        function highlight() {
            fileContainer.classList.add('active');
        }
        
        function unhighlight() {
            fileContainer.classList.remove('active');
        }
        
        // Manejar soltar archivos
        fileContainer.addEventListener('drop', handleDrop, false);
        
function handleDrop(e) {
                const dt = e.dataTransfer;
                const files = dt.files;
                
                if (files.length) {
                    fileInput.files = files;
                    updateFileNameDisplay(files[0]);
                }
            }
            
            // Actualizar la visualización del nombre del archivo
            fileInput.addEventListener('change', function() {
                if (this.files.length) {
                    updateFileNameDisplay(this.files[0]);
                }
            });
            
            function updateFileNameDisplay(file) {
                const fileNameDisplay = document.getElementById('file-name');
                if (!fileNameDisplay) return;
                
                // Validar tipo de archivo
                const allowedTypes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
                const maxSize = 5 * 1024 * 1024; // 5MB
                
                if (!allowedTypes.includes(file.type)) {
                    fileNameDisplay.textContent = '⚠️ Tipo de archivo no válido. Use PDF, DOC o DOCX';
                    fileNameDisplay.classList.add('error');
                    fileContainer.classList.add('error');
                    return;
                }
                
                if (file.size > maxSize) {
                    fileNameDisplay.textContent = '⚠️ Archivo demasiado grande (máx. 5MB)';
                    fileNameDisplay.classList.add('error');
                    fileContainer.classList.add('error');
                    return;
                }
                
                // Mostrar nombre y tamaño del archivo
                fileNameDisplay.textContent = file.name;
                fileNameDisplay.classList.remove('error');
                fileContainer.classList.remove('error');
                
                // Mostrar tamaño del archivo
                const fileFormatText = document.querySelector('.file-format-text');
                if (fileFormatText) {
                    const fileSize = formatFileSize(file.size);
                    fileFormatText.innerHTML = `<strong>${fileSize}</strong> - Formatos aceptados: PDF, DOC, DOCX`;
                }
            }
            
            function formatFileSize(bytes) {
                if (bytes === 0) return '0 Bytes';
                
                const k = 1024;
                const sizes = ['Bytes', 'KB', 'MB'];
                const i = Math.floor(Math.log(bytes) / Math.log(k));
                
                return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
            }
        };
        
        enhanceFileUpload();
    });

/**
 * Mejora de validación de formulario
 */
document.addEventListener('DOMContentLoaded', function() {
    const applicationForm = document.getElementById('application-form');
    
    if (applicationForm) {
        applicationForm.addEventListener('submit', function(e) {
            let isValid = true;
            
            // Limpiar errores previos
            const errorMessages = document.querySelectorAll('.form-error');
            errorMessages.forEach(msg => msg.remove());
            
            // Validar campos requeridos
            const requiredFields = this.querySelectorAll('[required]');
            requiredFields.forEach(field => {
                const formGroup = field.closest('.form-group');
                formGroup.classList.remove('error');
                
                if (!field.value.trim()) {
                    isValid = false;
                    markFieldAsInvalid(field, 'Este campo es obligatorio');
                }
            });
            
            // Validar email
            const emailField = this.querySelector('#email');
            if (emailField && emailField.value.trim()) {
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(emailField.value.trim())) {
                    isValid = false;
                    markFieldAsInvalid(emailField, 'Por favor, ingrese un email válido');
                }
            }
            
            // Validar teléfono (formato básico)
            const phoneField = this.querySelector('#telefono');
            if (phoneField && phoneField.value.trim()) {
                const phoneValue = phoneField.value.replace(/\D/g, '');
                if (phoneValue.length < 8) {
                    isValid = false;
                    markFieldAsInvalid(phoneField, 'Por favor, ingrese un número de teléfono válido');
                }
            }
            
            // Validar CV
            const cvField = this.querySelector('#cv');
            if (cvField && cvField.files.length > 0) {
                const file = cvField.files[0];
                const allowedTypes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
                const maxSize = 5 * 1024 * 1024; // 5MB
                
                if (!allowedTypes.includes(file.type)) {
                    isValid = false;
                    markFieldAsInvalid(cvField, 'Solo se permiten archivos PDF, DOC o DOCX');
                } else if (file.size > maxSize) {
                    isValid = false;
                    markFieldAsInvalid(cvField, 'El archivo excede el tamaño máximo permitido (5MB)');
                }
            }
            
            // Validar términos y condiciones
            const termsCheckbox = this.querySelector('#terminos');
            if (termsCheckbox && !termsCheckbox.checked) {
                isValid = false;
                markFieldAsInvalid(termsCheckbox, 'Debe aceptar los términos y condiciones');
            }
            
            // Si no es válido, prevenir envío
            if (!isValid) {
                e.preventDefault();
                
                // Mostrar mensaje general de error
                const formTop = this.querySelector('.form-section');
                if (formTop) {
                    const generalError = document.createElement('div');
                    generalError.className = 'alert alert-danger';
                    generalError.innerHTML = '<i class="fas fa-exclamation-circle"></i> Por favor, corrija los errores en el formulario antes de continuar.';
                    formTop.insertBefore(generalError, formTop.firstChild);
                    
                    // Scroll al principio del formulario
                    generalError.scrollIntoView({
                        behavior: 'smooth',
                        block: 'center'
                    });
                }
            }
            
            function markFieldAsInvalid(field, message) {
                const formGroup = field.closest('.form-group');
                formGroup.classList.add('error');
                
                const errorMessage = document.createElement('div');
                errorMessage.className = 'form-error';
                errorMessage.textContent = message;
                formGroup.appendChild(errorMessage);
            }
        });
    }
});

/**
 * Mejora para las tarjetas de trabajo: efecto hover refinado
 */
document.addEventListener('DOMContentLoaded', function() {
    const jobCards = document.querySelectorAll('.job-card, .job-list-card');
    
    jobCards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-5px)';
            this.style.boxShadow = '0 15px 30px rgba(0, 0, 0, 0.15)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = '';
            this.style.boxShadow = '';
        });
    });
});

/**
 * Mejoras de desempeño
 */
// Delegar eventos comunes en lugar de asignar individualmente
document.addEventListener('click', function(e) {
    // Delegación para botones de guardar vacante
    if (e.target.matches('.btn-save-job') || e.target.closest('.btn-save-job')) {
        const button = e.target.matches('.btn-save-job') ? e.target : e.target.closest('.btn-save-job');
        toggleSaveJob(button);
    }
    
    // Delegación para modales
    if (e.target.matches('.modal-trigger') || e.target.closest('.modal-trigger')) {
        const trigger = e.target.matches('.modal-trigger') ? e.target : e.target.closest('.modal-trigger');
        const modalId = trigger.getAttribute('data-modal');
        
        if (modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.classList.add('active');
                document.body.style.overflow = 'hidden';
            }
        }
    }
    
    // Cerrar modales
    if (e.target.matches('.modal-close') || e.target.matches('.modal-overlay')) {
        const modal = e.target.closest('.modal');
        if (modal) {
            modal.classList.remove('active');
            document.body.style.overflow = '';
        }
    }
});

// Función para marcar/desmarcar favoritos
function toggleSaveJob(button) {
    button.classList.toggle('saved');
    
    // Cambiar ícono
    const icon = button.querySelector('i');
    if (icon) {
        if (button.classList.contains('saved')) {
            icon.classList.remove('far');
            icon.classList.add('fas');
            // Mostrar notificación
            showNotification('Vacante guardada en favoritos', 'success');
        } else {
            icon.classList.remove('fas');
            icon.classList.add('far');
            // Mostrar notificación
            showNotification('Vacante eliminada de favoritos', 'info');
        }
    }
    
    // Enviar información al servidor mediante fetch si está logueado
    const jobId = button.getAttribute('data-job-id');
    const action = button.classList.contains('saved') ? 'save' : 'unsave';
    
    // Verificar si hay un usuario logueado
    const isLoggedIn = document.body.classList.contains('user-logged-in');
    
    if (isLoggedIn && jobId) {
        fetch('actions/toggle-favorite.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `job_id=${jobId}&action=${action}`
        })
        .then(response => response.json())
        .then(data => {
            if (!data.success) {
                console.error('Error al guardar favorito:', data.message);
            }
        })
        .catch(error => {
            console.error('Error en solicitud:', error);
        });
    }
}

// Función para mostrar notificaciones
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <div class="notification-content">
            <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'info' ? 'info-circle' : 'exclamation-circle'}"></i>
            <span>${message}</span>
        </div>
        <button type="button" class="notification-close">
            <i class="fas fa-times"></i>
        </button>
    `;
    
    document.body.appendChild(notification);
    
    // Mostrar notificación con animación
    setTimeout(() => {
        notification.classList.add('show');
    }, 10);
    
    // Asignar evento para cerrar
    const closeButton = notification.querySelector('.notification-close');
    if (closeButton) {
        closeButton.addEventListener('click', () => {
            closeNotification(notification);
        });
    }
    
    // Auto cerrar después de 4 segundos
    setTimeout(() => {
        closeNotification(notification);
    }, 4000);
}

function closeNotification(notification) {
    notification.classList.remove('show');
    setTimeout(() => {
        if (notification && document.body.contains(notification)) {
            notification.remove();
        }
    }, 300);
}
=======
});
>>>>>>> bfdd4b60a420df76ff03f2ca490715c5b78545c5
