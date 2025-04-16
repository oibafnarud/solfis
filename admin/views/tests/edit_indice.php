<!-- admin/views/tests/edit_indice.php -->
<div class="content-wrapper">
    <div class="page-header">
        <h3 class="page-title">
            <span class="page-title-icon bg-gradient-primary text-white me-2">
                <i class="mdi mdi-puzzle"></i>
            </span> 
            <?php echo $indice['id'] > 0 ? 'Editar' : 'Crear'; ?> Índice Compuesto
        </h3>
        <nav aria-label="breadcrumb">
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="tests.php">Pruebas</a></li>
                <li class="breadcrumb-item"><a href="tests.php?action=indices">Índices Compuestos</a></li>
                <li class="breadcrumb-item active" aria-current="page"><?php echo $indice['id'] > 0 ? 'Editar' : 'Crear'; ?> Índice</li>
            </ul>
        </nav>
    </div>
    
    <div class="row">
        <div class="col-md-12 grid-margin stretch-card">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title">Información del Índice</h4>
                    <p class="card-description">
                        Complete la información del índice compuesto y sus componentes.
                    </p>
                    
                    <form class="forms-sample" method="post" action="tests.php?action=edit_indice<?php echo $indice['id'] > 0 ? '&id=' . $indice['id'] : ''; ?>">
                        <div class="form-group">
                            <label for="nombre">Nombre <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="nombre" name="nombre" 
                                   placeholder="Ej: Capacidad Analítica" 
                                   value="<?php echo htmlspecialchars($indice['nombre']); ?>" required>
                            <small class="form-text text-muted">Nombre descriptivo para el índice compuesto.</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="descripcion">Descripción</label>
                            <textarea class="form-control" id="descripcion" name="descripcion" rows="4" 
                                      placeholder="Describa qué mide este índice..."
                            ><?php echo htmlspecialchars($indice['descripcion']); ?></textarea>
                            <small class="form-text text-muted">Descripción detallada de lo que evalúa este índice y cómo interpretarlo.</small>
                        </div>
                        
                        <hr>
                        
                        <h4 class="card-title">Componentes del Índice</h4>
                        <p class="card-description">
                            Agregue los componentes que conforman este índice y su ponderación relativa.
                        </p>
                        
                        <?php if ($indice['id'] > 0 && !empty($componentes)): ?>
                        <div class="form-group">
                            <div class="form-check form-check-flat form-check-primary">
                                <label class="form-check-label">
                                    <input type="checkbox" class="form-check-input" name="clear_componentes" value="1">
                                    Eliminar componentes actuales y reemplazarlos
                                    <i class="input-helper"></i>
                                </label>
                                <small class="form-text text-muted">Si marca esta opción, se eliminarán todos los componentes actuales y se reemplazarán con los que agregue a continuación.</small>
                            </div>
                        </div>
                        
                        <div class="table-responsive mb-4">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Tipo</th>
                                        <th>Componente</th>
                                        <th>Ponderación</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($componentes as $componente): ?>
                                    <tr>
                                        <td><?php echo $componente['origen_tipo'] == 'dimension' ? 'Dimensión' : 'Índice'; ?></td>
                                        <td><?php echo htmlspecialchars($componente['componente_nombre']); ?></td>
                                        <td><?php echo number_format($componente['ponderacion'] * 100, 0); ?>%</td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?>
                        
                        <div id="componentes-container">
                            <!-- Los componentes se agregarán aquí dinámicamente -->
                        </div>
                        
                        <button type="button" class="btn btn-outline-primary btn-icon-text mb-4" id="add-componente-btn">
                            <i class="mdi mdi-plus-circle-outline btn-icon-prepend"></i>
                            Añadir Componente
                        </button>
                        
                        <div class="form-group text-center">
                            <button type="submit" class="btn btn-gradient-primary me-2">
                                <i class="mdi mdi-content-save"></i> 
                                <?php echo $indice['id'] > 0 ? 'Actualizar' : 'Crear'; ?> Índice
                            </button>
                            <a href="tests.php?action=indices" class="btn btn-light">Cancelar</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Template para componentes -->
<template id="componente-template">
    <div class="card mb-3 componente-item">
        <div class="card-body">
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Tipo de Componente <span class="text-danger">*</span></label>
                        <select class="form-control componente-tipo" name="componente_tipo[]" required>
                            <option value="">Seleccione tipo...</option>
                            <option value="dimension">Dimensión</option>
                            <option value="indice">Índice Compuesto</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Componente <span class="text-danger">*</span></label>
                        <select class="form-control componente-id" name="componente_id[]" required disabled>
                            <option value="">Primero seleccione un tipo</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Ponderación (%) <span class="text-danger">*</span></label>
                        <input type="number" class="form-control componente-ponderacion" 
                               name="componente_ponderacion[]" min="1" max="100" value="100" required>
                    </div>
                </div>
                <div class="col-md-1 d-flex align-items-center justify-content-center">
                    <button type="button" class="btn btn-danger btn-icon remove-componente-btn">
                        <i class="mdi mdi-delete"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Datos para las opciones de componentes
    const dimensiones = <?php echo json_encode($dimensiones); ?>;
    const indices = <?php echo json_encode($indicesDisponibles); ?>;
    
    // Elementos del DOM
    const componentesContainer = document.getElementById('componentes-container');
    const addComponenteBtn = document.getElementById('add-componente-btn');
    const componenteTemplate = document.getElementById('componente-template');
    
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
        });
        
        // Añadir a contenedor
        componentesContainer.appendChild(componenteNode);
    }
    
    // Configurar evento para añadir componente
    addComponenteBtn.addEventListener('click', addComponente);
    
    // Añadir al menos un componente si no hay ninguno
    if (componentesContainer.children.length === 0) {
        addComponente();
    }
});
</script>