// popup.js - Funcionalidad para el popup promocional

document.addEventListener('DOMContentLoaded', function() {
    // Variables
    const popup = document.getElementById('frontierPopup');
    const closeBtn = document.getElementById('closePopup');
    const cookieName = 'frontierPopupShown';
    const cookieDuration = 7; // días
    
    // Funciones
    function showPopup() {
        // Mostrar el popup después de 3 segundos
        setTimeout(() => {
            popup.classList.add('active');
            document.body.style.overflow = 'hidden'; // Prevenir scroll
        }, 3000);
    }
    
    function closePopup() {
        popup.classList.remove('active');
        document.body.style.overflow = ''; // Restaurar scroll
        // Establecer cookie para no mostrar el popup por un período
        setCookie(cookieName, 'true', cookieDuration);
    }
    
    function setCookie(name, value, days) {
        let expires = '';
        if (days) {
            const date = new Date();
            date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
            expires = '; expires=' + date.toUTCString();
        }
        document.cookie = name + '=' + (value || '') + expires + '; path=/';
    }
    
    function getCookie(name) {
        const nameEQ = name + '=';
        const ca = document.cookie.split(';');
        for (let i = 0; i < ca.length; i++) {
            let c = ca[i];
            while (c.charAt(0) === ' ') {
                c = c.substring(1, c.length);
            }
            if (c.indexOf(nameEQ) === 0) {
                return c.substring(nameEQ.length, c.length);
            }
        }
        return null;
    }
    
    // Event Listeners
    if (closeBtn) {
        closeBtn.addEventListener('click', closePopup);
    }
    
    // Cerrar al hacer clic fuera del popup
    popup.addEventListener('click', function(e) {
        if (e.target === popup) {
            closePopup();
        }
    });
    
    // Cerrar con la tecla ESC
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && popup.classList.contains('active')) {
            closePopup();
        }
    });
    
    // Mostrar popup solo si no se ha mostrado antes
    if (!getCookie(cookieName)) {
        showPopup();
    }
});