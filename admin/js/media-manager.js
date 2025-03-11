/**
 * Media Manager para el panel de administración de SolFis
 */
document.addEventListener('DOMContentLoaded', function() {
    // Elementos del formulario
    const imageUploadForm = document.getElementById('imageUploadForm');
    const fileInput = document.getElementById('imageFile');
    const featuredImagePreview = document.getElementById('featuredImagePreview');
    const featuredImageInput = document.getElementById('image');
    const mediaGallery = document.querySelector('.media-gallery');
    const mediaItems = document.querySelectorAll('.media-item');
    
    // Mostrar vista previa al seleccionar archivo nuevo
    if (fileInput) {
        fileInput.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const previewContainer = document.getElementById('uploadPreview');
                    if (previewContainer) {
                        previewContainer.innerHTML = `<img src="${e.target.result}" class="img-fluid" alt="Vista previa">`;
                        previewContainer.style.display = 'block';
                    }
                };
                reader.readAsDataURL(file);
            }
        });
    }
    
    // Mostrar imagen destacada actual si existe
    if (featuredImageInput && featuredImageInput.value && featuredImagePreview) {
        featuredImagePreview.innerHTML = `<img src="../${featuredImageInput.value}" class="img-fluid" alt="Imagen destacada">`;
        featuredImagePreview.style.display = 'block';
    }
    
    // Manejar la selección de imágenes de la galería
    if (mediaItems) {
        mediaItems.forEach(item => {
            item.addEventListener('click', function() {
                const imagePath = this.getAttribute('data-path');
                const imageId = this.getAttribute('data-id');
                
                if (featuredImageInput && featuredImagePreview) {
                    featuredImageInput.value = imagePath;
                    featuredImagePreview.innerHTML = `<img src="../${imagePath}" class="img-fluid" alt="Imagen destacada">`;
                    featuredImagePreview.style.display = 'block';
                    
                    // Marcar la imagen seleccionada
                    mediaItems.forEach(mi => mi.classList.remove('selected'));
                    this.classList.add('selected');
                    
                    // Si estamos en una ventana emergente, cerrarla
                    const mediaModal = document.getElementById('mediaModal');
                    if (mediaModal && typeof bootstrap !== 'undefined') {
                        const modal = bootstrap.Modal.getInstance(mediaModal);
                        if (modal) modal.hide();
                    }
                }
            });
        });
    }
    
    // Actualizar la galería después de subir una imagen
    if (imageUploadForm) {
        imageUploadForm.addEventListener('submit', function(e) {
            // La subida se maneja por el formulario normal
            // Esta función es solo para preparar la actualización después
            // No es necesario prevenir el comportamiento predeterminado
        });
    }
});

// Función para seleccionar imagen desde el modal
function selectMedia(path, id) {
    const featuredImageInput = document.getElementById('image');
    const featuredImagePreview = document.getElementById('featuredImagePreview');
    
    if (featuredImageInput && featuredImagePreview) {
        featuredImageInput.value = path;
        featuredImagePreview.innerHTML = `<img src="../${path}" class="img-fluid" alt="Imagen destacada">`;
        featuredImagePreview.style.display = 'block';
        
        // Cerrar el modal
        const mediaModal = document.getElementById('mediaModal');
        if (mediaModal && typeof bootstrap !== 'undefined') {
            const modal = bootstrap.Modal.getInstance(mediaModal);
            if (modal) modal.hide();
        }
    }
}