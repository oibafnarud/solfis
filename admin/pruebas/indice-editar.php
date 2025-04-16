<?php
/**
 * Panel de Administración para SolFis
 * admin/pruebas/indice-editar.php - Crear/editar índice compuesto
 */

// Inicializar sesión
session_start();

// Incluir archivos necesarios
require_once '../config.php';
require_once '../../includes/blog-system.php';
require_once '../../includes/jobs-system.php';
require_once '../../includes/TestManager.php';

// Verificar autenticación
$auth = Auth::getInstance();
if (!$auth->isLoggedIn()) {
    header('Location: ../login.php');
    exit;
}

// Instanciar gestor de pruebas
$testManager = new TestManager();

// Verificar si es edición o creación
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$isEditing = $id > 0;

// Obtener datos del índice si es edición
$indice = $isEditing ? $testManager->getIndiceById($id) : null;

if ($isEditing && !$indice) {
    $_SESSION['error'] = "Índice no encontrado";
    header('Location: indices.php');
    exit;
}

// Inicializar datos para el formulario
$formData = $indice ?? [
    'id' => 0,
    'nombre' => '',
    'descripcion' => ''
];

// Obtener componentes del índice si es edición
$componentes = $isEditing ? $testManager->getComponentesIndice($id) : [];

// Obtener dimensiones disponibles para componentes
$dimensiones = $testManager->getDimensiones();

// Obtener otros índices compuestos disponibles (para componentes recursivos)
$indicesDisponibles = $testManager->getIndicesCompuestos();
// Filtrar el índice actual si es edición
if ($isEditing) {
    $indicesDisponibles = array_filter($indicesDisponibles, function($item) use ($id) {
        return $item['id'] != $id;
    });
}

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obtener datos del formulario
    $formData = [
        'id' => $id,
        'nombre' => $_POST['nombre'] ?? '',
        'descripcion' => $_POST['descripcion'] ?? '',
        'clear_componentes' => isset($_POST['clear_componentes']) && $_POST['clear_componentes'] == 1
    ];
    
    // Procesar componentes
    if (isset($_POST['componente_tipo']) && is_array($_POST['componente_tipo'])) {
        $componentesTipos = $_POST['componente_tipo'];
        $componentesIds = $_POST['componente_id'] ?? [];
        $componentesPonderaciones = $_POST['componente_ponderacion'] ?? [];
        
        $formData['componentes'] = [];
        
        for ($i = 0; $i < count($componentesTipos); $i++) {
            if (isset($componentesIds[$i]) && isset($componentesPonderaciones[$i])) {
                $formData['componentes'][] = [
                    'origen_tipo' => $componentesTipos[$i],
                    'origen_id' => (int)$componentesIds[$i],
                    'ponderacion' => (float)$componentesPonderaciones[$i]
                ];
            }
        }
    }
    
    // Guardar índice
    $resultado = $testManager->saveIndiceCompuesto($formData);
    
    if ($resultado['success']) {
        $_SESSION['success'] = $isEditing ? 
            "Índice actualizado correctamente" : 
            "Índice creado correctamente";
        header('Location: indices.php');
        exit;
    } else {
        $_SESSION['error'] = $resultado['message'];
    }
}

// Título de la página
$pageTitle = ($isEditing ? 'Editar' : 'Crear') . ' Índice Compuesto - Panel de Administración';

// Incluir la vista
include '../includes/header.php';
?>

<div class="admin-main">
    <div class="container-fluid">
        <div class="row">
            <?php include '../includes/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2"><?php echo $isEditing ? 'Editar' : 'Crear'; ?> Índice Compuesto</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="indices.php" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-arrow-left"></i> Volver a Índices
                        </a>
                    </div>
                </div>
                
                <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>
                
                <form action="indice-editar.php<?php echo $isEditing ? '?id=' . $id : ''; ?>" method="post">
                    <div class="card mb-4">
                        <div class="card-header">
                            <i class="fas fa-info-circle"></i> Información General
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label for="nombre" class="form-label">Nombre <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="nombre" name="nombre" value="<?php echo htmlspecialchars($formData['nombre']); ?>" required>
                                <div class="form-text">Nombre descriptivo del índice compuesto (ej: "Capacidad Analítica", "Liderazgo", etc.)</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="descripcion" class="form-label">Descripción</label>
                                <textarea class="form-control" id="descripcion" name="descripcion" rows="3"><?php echo htmlspecialchars($formData['descripcion']); ?></textarea>
                                <div class="form-text">Descripción detallada de lo que mide este índice y cómo se interpreta.</div>
                           </div>
                       </div>
                   </div>
                   
                   <div class="card mb-4">
                       <div class="card-header">
                           <i class="fas fa-puzzle-piece"></i> Componentes del Índice
                       </div>
                       <div class="card-body">
                           <div class="alert alert-info">
                               <i class="fas fa-info-circle"></i> Los componentes son las dimensiones o índices que conforman este índice compuesto. Cada componente tiene una ponderación que determina su importancia relativa en el cálculo final.
                           </div>
                           
                           <?php if ($isEditing && !empty($componentes)): ?>
                           <div class="mb-3">
                               <div class="form-check">
                                   <input class="form-check-input" type="checkbox" id="clear_componentes" name="clear_componentes" value="1">
                                   <label class="form-check-label" for="clear_componentes">
                                       Eliminar todos los componentes actuales y reemplazarlos con los nuevos
                                   </label>
                                   <div class="form-text text-danger">Si no marca esta opción, los nuevos componentes se añadirán a los ya existentes.</div>
                               </div>
                           </div>
                           
                           <div class="table-responsive mb-3">
                               <h5>Componentes Actuales</h5>
                               <table class="table table-bordered table-hover">
                                   <thead>
                                       <tr>
                                           <th>Tipo</th>
                                           <th>Nombre</th>
                                           <th>Ponderación</th>
                                       </tr>
                                   </thead>
                                   <tbody>
                                       <?php foreach ($componentes as $comp): ?>
                                       <tr>
                                           <td><?php echo $comp['origen_tipo'] == 'dimension' ? 'Dimensión' : 'Índice'; ?></td>
                                           <td><?php echo htmlspecialchars($comp['componente_nombre']); ?></td>
                                           <td><?php echo number_format($comp['ponderacion'] * 100, 0); ?>%</td>
                                       </tr>
                                       <?php endforeach; ?>
                                   </tbody>
                               </table>
                           </div>
                           <?php endif; ?>
                           
                           <h5>Nuevos Componentes</h5>
                           <div id="componentes-container">
                               <!-- Los componentes se agregarán aquí dinámicamente -->
                               <div class="text-center p-3 text-muted" id="no-components-message">
                                   No hay componentes añadidos. Use el botón "Añadir Componente" para agregar.
                               </div>
                           </div>
                           
                           <div class="d-grid gap-2 d-md-flex justify-content-md-start mb-3">
                               <button type="button" class="btn btn-outline-primary" id="add-componente-btn">
                                   <i class="fas fa-plus-circle"></i> Añadir Componente
                               </button>
                           </div>
                           
                           <!-- Template para nuevo componente -->
                           <template id="componente-template">
                               <div class="card mb-3 componente-item">
                                   <div class="card-body">
                                       <div class="row g-3">
                                           <div class="col-md-3">
                                               <label class="form-label">Tipo de Componente</label>
                                               <select class="form-select componente-tipo" name="componente_tipo[]" required>
                                                   <option value="">Seleccione tipo...</option>
                                                   <option value="dimension">Dimensión</option>
                                                   <option value="indice">Índice Compuesto</option>
                                               </select>
                                           </div>
                                           <div class="col-md-5">
                                               <label class="form-label">Componente</label>
                                               <select class="form-select componente-id" name="componente_id[]" required disabled>
                                                   <option value="">Primero seleccione un tipo</option>
                                               </select>
                                           </div>
                                           <div class="col-md-3">
                                               <label class="form-label">Ponderación (%)</label>
                                               <input type="number" class="form-control componente-ponderacion" name="componente_ponderacion[]" min="1" max="100" value="100" required>
                                           </div>
                                           <div class="col-md-1 d-flex align-items-end">
                                               <button type="button" class="btn btn-outline-danger remove-componente-btn">
                                                   <i class="fas fa-trash"></i>
                                               </button>
                                           </div>
                                       </div>
                                   </div>
                               </div>
                           </template>
                       </div>
                   </div>
                   
                   <div class="d-grid gap-2 d-md-flex justify-content-md-end mb-4">
                       <a href="indices.php" class="btn btn-outline-secondary">
                           <i class="fas fa-times"></i> Cancelar
                       </a>
                       <button type="submit" class="btn btn-primary">
                           <i class="fas fa-save"></i> <?php echo $isEditing ? 'Actualizar' : 'Crear'; ?> Índice
                       </button>
                   </div>
               </form>
           </main>
       </div>
   </div>
</div>

<!-- JavaScript para gestión dinámica de componentes -->
<script>
document.addEventListener('DOMContentLoaded', function() {
   // Datos para las opciones de componentes
   const dimensiones = <?php echo json_encode($dimensiones); ?>;
   const indices = <?php echo json_encode($indicesDisponibles); ?>;
   
   // Elementos del DOM
   const componentesContainer = document.getElementById('componentes-container');
   const addComponenteBtn = document.getElementById('add-componente-btn');
   const componenteTemplate = document.getElementById('componente-template');
   const noComponentsMessage = document.getElementById('no-components-message');
   
   // Función para actualizar mensaje "sin componentes"
   function updateNoComponentsMessage() {
       const componentItems = document.querySelectorAll('.componente-item');
       noComponentsMessage.style.display = componentItems.length > 0 ? 'none' : 'block';
   }
   
   // Función para añadir un nuevo componente
   function addComponente() {
       // Clonar template
       const componenteNode = componenteTemplate.content.cloneNode(true);
       const componenteItem = componenteNode.querySelector('.componente-item');
       
       // Obtener elementos del componente
       const tipoSelect = componenteNode.querySelector('.componente-tipo');
       const idSelect = componenteNode.querySelector('.componente-id');
       const removeBtn = componenteNode.querySelector('.remove-componente-btn');
       
       // Configurar evento de cambio en tipo
       tipoSelect.addEventListener('change', function() {
           const tipo = this.value;
           
           // Limpiar opciones actuales
           idSelect.innerHTML = '';
           idSelect.disabled = !tipo;
           
           if (tipo) {
               // Añadir opción inicial
               const defaultOption = document.createElement('option');
               defaultOption.value = '';
               defaultOption.textContent = `Seleccione ${tipo === 'dimension' ? 'dimensión' : 'índice'}...`;
               idSelect.appendChild(defaultOption);
               
               // Añadir opciones según tipo
               const options = tipo === 'dimension' ? dimensiones : indices;
               
               options.forEach(option => {
                   const optionElement = document.createElement('option');
                   optionElement.value = option.id;
                   optionElement.textContent = option.nombre;
                   idSelect.appendChild(optionElement);
               });
           }
       });
       
       // Configurar evento para eliminar componente
       removeBtn.addEventListener('click', function() {
           componenteItem.remove();
           updateNoComponentsMessage();
       });
       
       // Añadir a contenedor
       componentesContainer.appendChild(componenteNode);
       updateNoComponentsMessage();
   }
   
   // Configurar evento para añadir componente
   addComponenteBtn.addEventListener('click', addComponente);
   
   // Actualizar mensaje inicial
   updateNoComponentsMessage();
});
</script>

<?php include '../includes/footer.php'; ?>