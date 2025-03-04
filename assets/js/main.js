// main.js
document.addEventListener('DOMContentLoaded', function() {
    // Inicialización de la aplicación
    const App = {
        init() {
            this.initPreloader();
            this.initAOS();
            this.initScrollToTop();
            this.handleScroll();
            this.initSmoothScroll();
        },

        // Preloader
        initPreloader() {
            const preloader = document.querySelector('.preloader');
            window.addEventListener('load', () => {
                preloader.classList.add('fade-out');
                setTimeout(() => {
                    preloader.style.display = 'none';
                    document.body.classList.add('loaded');
                }, 500);
            });
        },

        // Inicializar AOS (Animate On Scroll)
        initAOS() {
            AOS.init({
                duration: 800,
                once: true,
                offset: 50,
                easing: 'ease-out-cubic'
            });
        },

        // Botón Scroll to Top
        initScrollToTop() {
            const scrollBtn = document.getElementById('scrollToTop');
            
            window.addEventListener('scroll', () => {
                if (window.pageYOffset > 300) {
                    scrollBtn.classList.add('visible');
                } else {
                    scrollBtn.classList.remove('visible');
                }
            });

            scrollBtn.addEventListener('click', () => {
                window.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
            });
        },

        // Manejo del scroll
        handleScroll() {
            let lastScroll = 0;
            const navbar = document.querySelector('.navbar');
            
            window.addEventListener('scroll', () => {
                const currentScroll = window.pageYOffset;
                
                // Navbar scroll effect
                if (currentScroll > lastScroll && currentScroll > 100) {
                    navbar.classList.add('navbar-hidden');
                } else {
                    navbar.classList.remove('navbar-hidden');
                }

                if (currentScroll > 50) {
                    navbar.classList.add('navbar-scrolled');
                } else {
                    navbar.classList.remove('navbar-scrolled');
                }

                lastScroll = currentScroll;
            });
        },

        // Smooth Scroll para navegación
        initSmoothScroll() {
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function(e) {
                    e.preventDefault();
                    const target = document.querySelector(this.getAttribute('href'));
                    
                    if (target) {
                        // Cerrar menú móvil si está abierto
                        const navbarMenu = document.querySelector('.navbar-menu');
                        const mobileMenuBtn = document.querySelector('.mobile-menu-btn');
                        
                        navbarMenu.classList.remove('active');
                        mobileMenuBtn.classList.remove('active');

                        // Scroll suave a la sección
                        target.scrollIntoView({
                            behavior: 'smooth',
                            block: 'start'
                        });

                        // Actualizar URL sin causar scroll
                        window.history.pushState(null, null, this.getAttribute('href'));
                    }
                });
            });
        }
    };

    // Inicializar aplicación
    App.init();
});