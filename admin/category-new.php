<?php
/**
 * Panel de Administración para el Blog de SolFis
 * admin/category-new.php - Página para crear nuevas categorías
 */

// Inicializar sesión
session_start();

// Incluir archivos necesarios
require_once '../config.php';
require_once '../includes/blog-system.php';

// Verificar autenticación
$auth = Auth::getInstance();
if (!$auth->isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// Instanciar clase de categorías
$category = new Category();

// Procesar el formulario de envío
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Recoger datos del formulario
    $name = $_POST['name'];
    $slug = empty($_POST['slug']) ? Helpers::slugify($name) : $_POST['slug'];
    $description = $_POST['description'] ?? '';
    
    // Validar datos
    $errors = [];
    
    if (empty($name)) {
        $errors[] = 'El nombre de la categoría es obligatorio.';
    }
    
    // Verificar que el slug sea único
    if (!empty($slug)) {
        $existingCategory = $category->getCategoryBySlug($slug);
        if ($existingCategory) {
            $errors[] = 'Ya existe una categoría con esta URL amigable. Por favor, elija otra.';
        }
    }
    
    // Si no hay errores, crear la categoría
    if (empty($errors)) {
        $categoryData = [
            'name' => $name,
            'slug' => $slug,
            'description' => $description
        ];
        
        if ($category->createCategory($categoryData)) {
            header('Location: categories.php?message=category-created');
            exit;
        } else {
            $errors[] = 'Ha ocurrido un error al crear la categoría.';
        }
    }
}

// Título de la página
$pageTitle = 'Nueva Categoría - Panel de Administración';
?>

<?php include 'includes/header.php'; ?>

<div class="container-fluid">
    <div class="row">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Nueva Categoría</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="categories.php" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> Volver a Categorías
                    </a>
                </div>
            </div>
            
            <?php if (isset($errors) && !empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo $error; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-body">
                    <form action="category-new.php" method="post">
                        <div class="mb-3">
                            <label for="name" class="form-label">Nombre *</label>
                            <input type="text" class="form-control" id="name" name="name" value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="slug" class="form-label">URL Amigable (Slug)</label>
                            <input type="text" class="form-control" id="slug" name="slug" value="<?php echo isset($_POST['slug']) ? htmlspecialchars($_POST['slug']) : ''; ?>" placeholder="Se generará automáticamente desde el nombre">
                            <div class="form-text">Deje en blanco para generar automáticamente desde el nombre.</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Descripción</label>
                            <textarea class="form-control" id="description" name="description" rows="4"><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">Crear Categoría</button>
                            <a href="categories.php" class="btn btn-outline-secondary">Cancelar</a>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Script para generar slug automáticamente -->
<script>
document.getElementById('name').addEventListener('blur', function() {
    const slugField = document.getElementById('slug');
    if (slugField.value === '') {
        const name = this.value;
        const slug = name.toLowerCase()
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/-+/g, '-')
            .replace(/^-|-$/g, '');
        slugField.value = slug;
    }
});
</script>

<?php include 'includes/footer.php'; ?>