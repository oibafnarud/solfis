// contact.js
document.addEventListener('DOMContentLoaded', function() {
    // Inicialización de variables
    const contactForm = document.getElementById('contactForm');
    const formInputs = contactForm.querySelectorAll('input, textarea, select');
    const submitButton = contactForm.querySelector('button[type="submit"]');

    // Configuración de validaciones
    const validations = {
        nombre: {
            required: true,
            minLength: 3,
            pattern: /^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/,
            messages: {
                required: 'Por favor, ingrese su nombre',
                minLength: 'El nombre debe tener al menos 3 caracteres',
                pattern: 'Por favor, ingrese un nombre válido'
            }
        },
        email: {
            required: true,
            pattern: /^[^\s@]+@[^\s@]+\.[^\s@]+$/,
            messages: {
                required: 'Por favor, ingrese su correo electrónico',
                pattern: 'Por favor, ingrese un correo electrónico válido'
            }
        },
        telefono: {
            required: true,
            pattern: /^\(\d{3}\)\s?\d{3}-\d{4}$/,
            messages: {
                required: 'Por favor, ingrese su teléfono',
                pattern: 'Por favor, ingrese un teléfono válido (809) 555-0123'
            }
        },
        mensaje: {
            required: true,
            minLength: 10,
            messages: {
                required: 'Por favor, ingrese su mensaje',
                minLength: 'El mensaje debe tener al menos 10 caracteres'
            }
        }
    };

    // Inicialización del mapa
    function initMap() {
        const mapElement = document.getElementById('map');
        if (!mapElement) return;

        const location = {
            lat: 18.4861,
            lng: -69.9312 // Coordenadas de Santo Domingo
        };

        const mapOptions = {
            zoom: 15,
            center: location,
            styles: [
                {
                    "featureType": "all",
                    "elementType": "geometry",
                    "stylers": [{"color": "#f5f5f5"}]
                },
                {
                    "featureType": "water",
                    "elementType": "geometry",
                    "stylers": [{"color": "#c9c9c9"}]
                }
                // Más estilos personalizados aquí
            ]
        };

        const map = new google.maps.Map(mapElement, mapOptions);

        const marker = new google.maps.Marker({
            position: location,
            map: map,
            title: 'Solfis',
            animation: google.maps.Animation.DROP
        });

        const infoWindow = new google.maps.InfoWindow({
            content: `
                <div class="map-info-window">
                    <h3>Solfis</h3>
                    <p>Precisión Financiera</p>
                    <p>Santo Domingo, República Dominicana</p>
                </div>
            `
        });

        marker.addListener('click', () => {
            infoWindow.open(map, marker);
        });
    }

    // Máscara para el teléfono
    function setupPhoneMask(input) {
        input.addEventListener('input', (e) => {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length >= 10) {
                value = `(${value.substring(0,3)}) ${value.substring(3,6)}-${value.substring(6,10)}`;
            }
            e.target.value = value;
        });
    }

    // Validación de campos
    function validateField(input) {
        const field = input.name;
        const value = input.value;
        const validation = validations[field];
        const formGroup = input.closest('.form-group');
        const errorElement = formGroup.querySelector('.error-message');
        let isValid = true;
        let errorMessage = '';

        if (!validation) return true;

        // Validaciones
        if (validation.required && !value) {
            isValid = false;
            errorMessage = validation.messages.required;
        } else if (validation.minLength && value.length < validation.minLength) {
            isValid = false;
            errorMessage = validation.messages.minLength;
        } else if (validation.pattern && !validation.pattern.test(value)) {
            isValid = false;
            errorMessage = validation.messages.pattern;
        }

        // Actualizar UI
        formGroup.classList.toggle('error', !isValid);
        if (errorElement) {
            errorElement.textContent = errorMessage;
        }

        return isValid;
    }

    // Validación del formulario completo
    function validateForm() {
        let isValid = true;
        formInputs.forEach(input => {
            if (!validateField(input)) {
                isValid = false;
            }
        });
        return isValid;
    }

    // Mostrar notificación
    function showNotification(message, type = 'success') {
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.innerHTML = `
            <div class="notification-content">
                <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'}"></i>
                <p>${message}</p>
            </div>
            <button class="notification-close">
                <i class="fas fa-times"></i>
            </button>
        `;

        document.body.appendChild(notification);

        // Animación de entrada
        requestAnimationFrame(() => {
            notification.classList.add('show');
        });

        // Botón de cerrar
        const closeBtn = notification.querySelector('.notification-close');
        closeBtn.addEventListener('click', () => {
            notification.classList.remove('show');
            setTimeout(() => notification.remove(), 300);
        });

        // Auto cerrar después de 5 segundos
        setTimeout(() => {
            if (document.body.contains(notification)) {
                notification.classList.remove('show');
                setTimeout(() => notification.remove(), 300);
            }
        }, 5000);
    }

    // Envío del formulario
    async function handleSubmit(e) {
        e.preventDefault();

        if (!validateForm()) {
            showNotification('Por favor, complete todos los campos correctamente.', 'error');
            return;
        }

        const formData = new FormData(contactForm);
        submitButton.disabled = true;
        const originalText = submitButton.innerHTML;
        submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enviando...';

        try {
            // Aquí iría la llamada al backend
            await new Promise(resolve => setTimeout(resolve, 1500)); // Simulación
            
            showNotification('¡Mensaje enviado con éxito! Nos pondremos en contacto pronto.');
            contactForm.reset();
            formInputs.forEach(input => {
                input.closest('.form-group').classList.remove('error', 'success');
            });

        } catch (error) {
            showNotification('Hubo un error al enviar el mensaje. Por favor, intente nuevamente.', 'error');
        } finally {
            submitButton.disabled = false;
            submitButton.innerHTML = originalText;
        }
    }

    // Event Listeners
    formInputs.forEach(input => {
        if (input.name === 'telefono') {
            setupPhoneMask(input);
        }

        input.addEventListener('blur', () => validateField(input));
        input.addEventListener('input', () => {
            if (input.closest('.form-group').classList.contains('error')) {
                validateField(input);
            }
        });
    });

    contactForm.addEventListener('submit', handleSubmit);

    // Inicializar el mapa si existe el script de Google Maps
    if (typeof google !== 'undefined' && google.maps) {
        initMap();
    }
});