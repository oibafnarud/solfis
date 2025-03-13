<?php
/**
 * Panel de Administración para el Blog de SolFis
 * admin/settings.php - Página de configuración del sitio
 */

// Inicializar sesión
session_start();

// Incluir archivos necesarios
require_once '../config.php';
require_once '../includes/blog-system.php';

// Verificar autenticación
$auth = Auth::getInstance();
if (!$auth->isLoggedIn() || !$auth->isAdmin()) {
    header('Location: login.php');
    exit;
}

// Definir configuraciones disponibles
$settings = [
    'site_name' => [
        'label' => 'Nombre del sitio',
        'type' => 'text',
        'default' => SITE_NAME
    ],
    'site_description' => [
        'label' => 'Descripción del sitio',
        'type' => 'textarea',
        'default' => SITE_DESCRIPTION
    ],
    'posts_per_page' => [
        'label' => 'Artículos por página',
        'type' => 'number',
        'default' => POSTS_PER_PAGE
    ],
    'comments_per_page' => [
        'label' => 'Comentarios por página',
        'type' => 'number',
        'default' => COMMENTS_PER_PAGE
    ],
    'enable_comments' => [
        'label' => 'Habilitar comentarios',
        'type' => 'checkbox',
        'default' => ENABLE_COMMENTS
    ],
    'require_comment_approval' => [
        'label' => 'Requerir aprobación de comentarios',
        'type' => 'checkbox',
        'default' => REQUIRE_COMMENT_APPROVAL
    ]
];

// Guardar configuraciones
$success = false;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Ruta al archivo de configuración
        $configFile = '../config.php';
        
        // Leer el contenido actual
        $content = file_get_contents($configFile);
        
        // Actualizar cada configuración
        foreach ($settings as $key => $info) {
            $value = $_POST[$key] ?? $info['default'];
            
            // Formatear el valor según el tipo
            switch ($info['type']) {
                case 'text':
                case 'textarea':
                    $value = "'" . addslashes($value) . "'";
                    break;
                case 'number':
                    $value = (int)$value;
                    break;
                case 'checkbox':
                    $value = isset($_POST[$key]) ? 'true' : 'false';
                    break;
            }
            
            // Reemplazar la definición en el archivo
            $pattern = "/define\(['\"]" . strtoupper($key) . "['\"],\s*.*?\);/";
            $replacement = "define('" . strtoupper($key) . "', $value);";
            $content = preg_replace($pattern, $replacement, $content);
        }
        
        // Guardar el archivo
        if (file_put_contents($configFile, $content) !== false) {
            $success = true;
        } else {
            $error = 'No se pudo escribir en el archivo de configuración.';
        }
    } catch (Exception $e) {
        $error = 'Error: ' . $e->getMessage();
    }
}

// Título de la página
$pageTitle = 'Configuración del Sitio - Panel de Administración';
?>

<?php include 'includes/header.php'; ?>

<div class="container-fluid">
    <div class="row">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Configuración del Sitio</h1>
            </div>
            
            <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                Configuración guardada correctamente.
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-body">
                    <form method="post" action="">
                        <div class="row">
                            <div class="col-md-6">
                                <h5 class="mb-3">Configuración General</h5>
                                
                                <?php foreach (['site_name', 'site_description'] as $key): ?>
                                <div class="mb-3">
                                    <label for="<?php echo $key; ?>" class="form-label"><?php echo $settings[$key]['label']; ?></label>
                                    
                                    <?php if ($settings[$key]['type'] === 'textarea'): ?>
                                    <textarea class="form-control" id="<?php echo $key; ?>" name="<?php echo $key; ?>" rows="3"><?php echo constant(strtoupper($key)); ?></textarea>
                                    <?php else: ?>
                                    <input type="<?php echo $settings[$key]['type']; ?>" class="form-control" id="<?php echo $key; ?>" name="<?php echo $key; ?>" value="<?php echo constant(strtoupper($key)); ?>">
                                    <?php endif; ?>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <div class="col-md-6">
                                <h5 class="mb-3">Configuración del Blog</h5>
                                
                                <?php foreach (['posts_per_page', 'comments_per_page'] as $key): ?>
                                <div class="mb-3">
                                    <label for="<?php echo $key; ?>" class="form-label"><?php echo $settings[$key]['label']; ?></label>
                                    <input type="<?php echo $settings[$key]['type']; ?>" class="form-control" id="<?php echo $key; ?>" name="<?php echo $key; ?>" value="<?php echo constant(strtoupper($key)); ?>" min="1" max="50">
                                </div>
                                <?php endforeach; ?>
                                
                                <?php foreach (['enable_comments', 'require_comment_approval'] as $key): ?>
                                <div class="mb-3 form-check">
                                    <input type="checkbox" class="form-check-input" id="<?php echo $key; ?>" name="<?php echo $key; ?>" <?php echo constant(strtoupper($key)) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="<?php echo $key; ?>"><?php echo $settings[$key]['label']; ?></label>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <button type="submit" class="btn btn-primary">Guardar Configuración</button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
</div>

<?php include 'includes/footer.php'; ?>