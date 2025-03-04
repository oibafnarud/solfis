// mobile-menu-jquery.js
$(document).ready(function() {
    // Toggle del menú móvil
    $('.mobile-menu-btn').click(function() {
        $(this).toggleClass('active');
        $('.navbar-menu').toggleClass('active');
        
        // Prevenir scroll
        if ($('.navbar-menu').hasClass('active')) {
            $('body').css('overflow', 'hidden');
        } else {
            $('body').css('overflow', '');
        }
    });
    
    // Toggle dropdowns
    $('.dropdown-toggle').click(function(e) {
        // Solo en móvil
        if ($(window).width() <= 768) {
            e.preventDefault();
            $(this).parent('.nav-dropdown').toggleClass('active');
            $(this).find('i').toggleClass('rotate');
            
            // Cerrar otros dropdowns
            $('.nav-dropdown').not($(this).parent('.nav-dropdown')).removeClass('active');
            $('.dropdown-toggle i').not($(this).find('i')).removeClass('rotate');
        }
    });
    
    // Cerrar menú al hacer clic en cualquier enlace
    $('.navbar-menu a:not(.dropdown-toggle)').click(function() {
        $('.mobile-menu-btn').removeClass('active');
        $('.navbar-menu').removeClass('active');
        $('body').css('overflow', '');
    });
    
    // Cerrar menú al hacer clic fuera
    $(document).click(function(e) {
        if (!$(e.target).closest('.navbar').length) {
            $('.mobile-menu-btn').removeClass('active');
            $('.navbar-menu').removeClass('active');
            $('body').css('overflow', '');
        }
    });
    
    // Prevenir cierre al hacer clic dentro del menú
    $('.navbar-menu').click(function(e) {
        e.stopPropagation();
    });
    
    // Resetear en resize
    $(window).resize(function() {
        if ($(window).width() > 768) {
            $('.mobile-menu-btn').removeClass('active');
            $('.navbar-menu').removeClass('active');
            $('.nav-dropdown').removeClass('active');
            $('.dropdown-toggle i').removeClass('rotate');
            $('body').css('overflow', '');
        }
    });
});