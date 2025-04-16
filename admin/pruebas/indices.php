<?php
/**
 * Panel de Administración para SolFis
 * admin/pruebas/indices.php - Gestionar índices compuestos
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

// Obtener listado de índices compuestos
$indices = $testManager->getIndicesCompuestos();

// Título de la página
$pageTitle = 'Gestión de Índices Compuestos - Panel de Administración';

// Incluir la vista
include '../includes/header.php';
?>

<div class="admin-main">
    <div class="container-fluid">
        <div class="row">
            <?php include '../includes/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Gestión de Índices Compuestos</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="index.php" class="btn btn-sm btn-outline-secondary me-2">
                            <i class="fas fa-arrow-left"></i> Volver a Pruebas
                        </a>
                        <a href="indice-editar.php" class="btn btn-sm btn-primary">
                            <i class="fas fa-plus"></i> Nuevo Índice
                        </a>
                    </div>
                </div>
                
                <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>
                
                <div class="card">
                    <div class="card-body">
                        <?php if (empty($indices)): ?>
                        <div class="alert alert-info">
                            No hay índices compuestos definidos. Puede crear uno nuevo utilizando el botón "Nuevo Índice".
                        </div>
                        <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Nombre</th>
                                        <th>Descripción</th>
                                        <th>Componentes</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($indices as $indice): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($indice['nombre']); ?></td>
                                        <td><?php echo htmlspecialchars(substr($indice['descripcion'], 0, 100) . (strlen($indice['descripcion']) > 100 ? '...' : '')); ?></td>
                                        <td>
                                            <?php 
                                            $componentes = $testManager->getComponentesIndice($indice['id']);
                                            echo count($componentes) . ' componente(s)';
                                            ?>
                                        </td>
                                        <td>
                                            <a href="indice-editar.php?id=<?php echo $indice['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-edit"></i> Editar
                                            </a>
                                            <a href="indice-eliminar.php?id=<?php echo $indice['id']; ?>" class="btn btn-sm btn-outline-danger" 
                                               onclick="return confirm('¿Está seguro de eliminar este índice? Esta acción no se puede deshacer.')">
                                                <i class="fas fa-trash"></i> Eliminar
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="mt-4">
                    <h3>Información sobre Índices Compuestos</h3>
                    <div class="card">
                        <div class="card-body">
                            <p>Los índices compuestos son métricas que combinan resultados de diferentes pruebas y dimensiones para proporcionar una evaluación más completa de las competencias de un candidato.</p>
                            
                            <h5>¿Cómo funcionan?</h5>
                            <ul>
                                <li><strong>Componentes ponderados:</strong> Cada índice está formado por múltiples componentes (dimensiones o incluso otros índices) con ponderaciones específicas.</li>
                                <li><strong>Normalización:</strong> Los valores se normalizan a una escala de 0-100 para facilitar la comparación.</li>
                                <li><strong>Interpretación:</strong> Se asignan niveles interpretativos (Excepcional, Sobresaliente, etc.) según rangos predefinidos.</li>
                                <li><strong>Aplicación:</strong> Los índices compuestos se utilizan para evaluar la compatibilidad de candidatos con perfiles de puestos específicos.</li>
                            </ul>
                            
                            <h5>Ejemplos de Índices Compuestos</h5>
                            <ul>
                                <li><strong>Capacidad Analítica:</strong> Combina Razonamiento Lógico, Razonamiento Numérico y Atención al Detalle.</li>
                                <li><strong>Orientación al Cliente:</strong> Combina Comunicación, Empatía y Resolución de Problemas.</li>
                                <li><strong>Liderazgo:</strong> Combina Toma de Decisiones, Comunicación y Estabilidad Emocional.</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>