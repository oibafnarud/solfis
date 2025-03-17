// navbar.js - Versión optimizada para móviles
document.addEventListener('DOMContentLoaded', function() {
    // Variables principales
    const navbar = document.querySelector('.navbar');
    const mobileMenuBtn = document.querySelector('.mobile-menu-btn');
    const navbarMenu = document.querySelector('.navbar-menu');
    const dropdownToggles = document.querySelectorAll('.dropdown-toggle');
    const navDropdowns = document.querySelectorAll('.nav-dropdown');
    
    // Efecto de scroll
    window.addEventListener('scroll', () => {
        if (window.scrollY > 50) {
            navbar.classList.add('scrolled');
        } else {
            navbar.classList.remove('scrolled');
        }
    });

    // Toggle del menú móvil - CORREGIDO
    if (mobileMenuBtn) {
        mobileMenuBtn.addEventListener('click', function() {
            // Toggle activo para el botón y el menú
            this.classList.toggle('active');
            navbarMenu.classList.toggle('active');
            
            // Controlar overflow del body para prevenir scroll cuando el menú está abierto
            if (navbarMenu.classList.contains('active')) {
                document.body.style.overflow = 'hidden';
            } else {
                document.body.style.overflow = '';
            }
        });
    }

    // Manejar dropdowns en móvil - IMPORTANTE
    dropdownToggles.forEach(toggle => {
        toggle.addEventListener('click', function(e) {
            // Solo en modo móvil
            if (window.innerWidth <= 768) {
                // Prevenir navegación
                e.preventDefault();
                
                // Toggle dropdown
                const parent = this.closest('.nav-dropdown');
                parent.classList.toggle('active');
                
                // Ajustar icono 
                const icon = this.querySelector('i');
                if (icon) {
                    if (parent.classList.contains('active')) {
                        icon.style.transform = 'rotate(180deg)';
                    } else {
                        icon.style.transform = 'rotate(0)';
                    }
                }
                
                // Cerrar otros dropdowns
                navDropdowns.forEach(dropdown => {
                    if (dropdown !== parent && dropdown.classList.contains('active')) {
                        dropdown.classList.remove('active');
                        const otherIcon = dropdown.querySelector('.dropdown-toggle i');
                        if (otherIcon) {
                            otherIcon.style.transform = 'rotate(0)';
                        }
                    }
                });
            }
        });
    });

    // Cerrar menú al hacer clic fuera
    document.addEventListener('click', (e) => {
        if (navbarMenu && navbarMenu.classList.contains('active') && 
            !navbar.contains(e.target)) {
            mobileMenuBtn.classList.remove('active');
            navbarMenu.classList.remove('active');
            document.body.style.overflow = '';
        }
    });

    // Cerrar al hacer clic en enlaces
    document.querySelectorAll('.navbar-menu a:not(.dropdown-toggle)').forEach(link => {
        link.addEventListener('click', function() {
            if (mobileMenuBtn && navbarMenu) {
                mobileMenuBtn.classList.remove('active');
                navbarMenu.classList.remove('active');
                document.body.style.overflow = '';
            }
        });
    });

    // Ajustar en resize
    window.addEventListener('resize', function() {
        if (window.innerWidth > 768) {
            // Resetear en desktop
            if (mobileMenuBtn && navbarMenu) {
                mobileMenuBtn.classList.remove('active');
                navbarMenu.classList.remove('active');
                document.body.style.overflow = '';
            }
            
            // Resetear dropdowns
            navDropdowns.forEach(dropdown => {
                if (dropdown.classList.contains('active')) {
                    dropdown.classList.remove('active');
                    const icon = dropdown.querySelector('.dropdown-toggle i');
                    if (icon) {
                        icon.style.transform = 'rotate(0)';
                    }
                }
            });
        }
    });
	
	// Mejorar manejo del menú desplegable en móvil
document.addEventListener('DOMContentLoaded', function() {
    const mobileMenuToggle = document.getElementById('mobileMenuToggle');
    const navbarMenu = document.getElementById('navbarMenu');
    const dropdownToggles = document.querySelectorAll('.dropdown-toggle');
    
    // Toggle menú móvil
    if (mobileMenuToggle && navbarMenu) {
        mobileMenuToggle.addEventListener('click', function() {
            navbarMenu.classList.toggle('active');
            this.classList.toggle('active');
        });
    }
    
    // Toggle submenús
    dropdownToggles.forEach(function(toggle) {
        toggle.addEventListener('click', function(e) {
            // Solo prevenir en móvil
            if (window.innerWidth <= 991) {
                e.preventDefault();
                const parent = this.closest('.nav-dropdown');
                
                // Cerrar otros submenús
                document.querySelectorAll('.nav-dropdown.active').forEach(function(dropdown) {
                    if (dropdown !== parent) {
                        dropdown.classList.remove('active');
                    }
                });
                
                // Alternar estado actual
                parent.classList.toggle('active');
            }
        });
    });
    
    // Cerrar menú al hacer clic fuera
    document.addEventListener('click', function(e) {
        if (!navbarMenu.contains(e.target) && !mobileMenuToggle.contains(e.target) && navbarMenu.classList.contains('active')) {
            navbarMenu.classList.remove('active');
            mobileMenuToggle.classList.remove('active');
        }
    });
    
    // Cerrar menú al cambiar de tamaño la ventana
    window.addEventListener('resize', function() {
        if (window.innerWidth > 991) {
            navbarMenu.classList.remove('active');
            mobileMenuToggle.classList.remove('active');
            document.querySelectorAll('.nav-dropdown.active').forEach(function(dropdown) {
                dropdown.classList.remove('active');
            });
        }
    });
});

});