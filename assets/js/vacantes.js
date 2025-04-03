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
});