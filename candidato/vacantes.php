<?php
// Inicializar sesión
session_start();

// Verificar que el usuario esté autenticado como candidato
if (!isset($_SESSION['candidato_id'])) {
    header('Location: login.php');
    exit;
}

// Incluir archivos necesarios
require_once '../includes/jobs-system.php';

// Instanciar clases necesarias
$candidateManager = new CandidateManager();
$vacancyManager = new VacancyManager();
$categoryManager = new CategoryManager();

// Obtener datos del candidato
$candidato_id = $_SESSION['candidato_id'];
$candidato = $candidateManager->getCandidateById($candidato_id);

// Si no existe el candidato, cerrar sesión
if (!$candidato) {
    session_destroy();
    header('Location: login.php?error=candidato_no_encontrado');
    exit;
}

// Obtener parámetros de filtro
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$q = isset($_GET['q']) ? $_GET['q'] : '';
$ubicacion = isset($_GET['ubicacion']) ? $_GET['ubicacion'] : '';
$categoriaId = isset($_GET['categoria']) ? (int)$_GET['categoria'] : 0;
$modalidad = isset($_GET['modalidad']) ? $_GET['modalidad'] : '';
$orderBy = isset($_GET['orden']) ? $_GET['orden'] : 'fecha_desc';

// Preparar filtros
$filters = [
    'estado' => 'publicada',
    'busqueda' => $q,
    'ubicacion' => $ubicacion,
    'categoria' => $categoriaId,
    'modalidad' => $modalidad,
    'orden' => $orderBy
];

// Obtener vacantes
$vacantesData = $vacancyManager->getVacancies($page, 9, $filters);
$vacantes = $vacantesData['vacancies'] ?? [];
$totalVacantes = $vacantesData['total'] ?? 0;
$totalPages = $vacantesData['pages'] ?? 1;

// Obtener categorías para filtro
$categorias = $categoryManager->getCategories();

// Título de la página
$site_title = "Explorar Vacantes - SolFis Talentos";
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $site_title; ?></title>
    
    <!-- CSS -->
    <link rel="stylesheet" href="../assets/css/normalize.css">
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="css/candidato.css">
    
    <!-- Estilos personalizados para la lista de vacantes -->
    <style>
        :root {
            --primary-color: #003366;
            --secondary-color: #0088cc;
            --accent-color: #ff9900;
            --success-color: #28a745;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
            --info-color: #17a2b8;
            --light: #f8f9fa;
            --dark: #343a40;
            --gray-100: #f8f9fa;
            --gray-200: #e9ecef;
            --gray-300: #dee2e6;
            --gray-400: #ced4da;
            --gray-500: #adb5bd;
            --gray-600: #6c757d;
            --gray-700: #495057;
            --gray-800: #343a40;
            --gray-900: #212529;
        }
        
        body {
            background-color: var(--gray-100);
            font-family: 'Poppins', sans-serif;
        }
        
        /* Layout de la página */
        .job-listing-layout {
            display: grid;
            grid-template-columns: 280px 1fr;
            gap: 1.5rem;
        }
        
        /* Barra lateral de filtros */
        .filter-sidebar {
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            padding: 1.5rem;
            position: sticky;
            top: 80px;
            height: fit-content;
        }
        
        .filter-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        
        .filter-header h3 {
            margin: 0;
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--gray-800);
        }
        
        .filter-reset {
            background: none;
            border: none;
            color: var(--gray-600);
            cursor: pointer;
            font-size: 0.875rem;
            padding: 0;
        }
        
        .filter-reset:hover {
            color: var(--danger-color);
            text-decoration: underline;
        }
        
        .filter-group {
            margin-bottom: 1.5rem;
        }
        
        .filter-group h3 {
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--gray-700);
            margin-top: 0;
            margin-bottom: 0.75rem;
        }
        
        .filter-label {
            display: block;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--gray-700);
        }
        
        .filter-search {
            position: relative;
        }
        
        .filter-search .form-control {
            padding-right: 40px;
        }
        
        .filter-search-btn {
            position: absolute;
            right: 0;
            top: 0;
            height: 100%;
            width: 40px;
            border: none;
            background: transparent;
            color: var(--gray-600);
            cursor: pointer;
        }
        
        .filter-search-btn:hover {
            color: var(--primary-color);
        }
        
        .filter-options {
            max-height: 200px;
            overflow-y: auto;
            padding-right: 0.5rem;
        }
        
        .filter-checkbox {
            display: flex;
            align-items: center;
            margin-bottom: 0.5rem;
        }
        
        .filter-checkbox input[type="checkbox"],
        .filter-checkbox input[type="radio"] {
            margin-right: 0.5rem;
        }
        
        .filter-checkbox label {
            font-size: 0.875rem;
            color: var(--gray-700);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: space-between;
            width: 100%;
        }
        
        .filter-count {
            color: var(--gray-500);
            font-size: 0.75rem;
        }
        
        .filter-actions {
            margin-top: 1.5rem;
        }
        
        .btn-filter-apply {
            width: 100%;
            padding: 0.5rem;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 0.25rem;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        
        .btn-filter-apply:hover {
            background-color: #00264d;
        }
        
        /* Contenido principal */
        .jobs-main {
            flex: 1;
        }
        
        .listing-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }
        
        .results-count {
            font-size: 0.9rem;
            color: var(--gray-600);
        }
        
        .results-count strong {
            color: var(--gray-800);
            font-weight: 600;
        }
        
        .mobile-filter-toggle {
            display: none;
            align-items: center;
            background-color: var(--gray-200);
            color: var(--gray-700);
            padding: 0.5rem 0.75rem;
            border-radius: 0.25rem;
            cursor: pointer;
            font-size: 0.875rem;
            font-weight: 500;
        }
        
        .mobile-filter-toggle i {
            margin-right: 0.5rem;
        }
        
        /* Lista de trabajos */
        .jobs-list {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1rem;
        }
        
        .job-list-card {
            display: flex;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            padding: 1.5rem;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .job-list-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
        
        .job-list-logo {
            flex-shrink: 0;
            width: 60px;
            height: 60px;
            margin-right: 1.5rem;
            background-color: var(--gray-100);
            border-radius: 8px;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .job-list-logo img {
            max-width: 100%;
            max-height: 100%;
        }
        
        .job-list-content {
            flex: 1;
            min-width: 0;
        }
        
        .job-list-title {
            display: flex;
            align-items: center;
            margin-bottom: 0.5rem;
        }
        
        .job-list-title h3 {
            margin: 0;
            font-size: 1.1rem;
            font-weight: 600;
            margin-right: 0.75rem;
        }
        
        .job-list-title h3 a {
            color: var(--gray-800);
            text-decoration: none;
        }
        
        .job-list-title h3 a:hover {
            color: var(--primary-color);
        }
        
        .badge-featured {
            background-color: var(--accent-color);
            color: white;
            font-size: 0.7rem;
            font-weight: 500;
            padding: 0.2rem 0.5rem;
            border-radius: 0.25rem;
        }
        
        .job-list-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
            margin-bottom: 0.75rem;
        }
        
        .job-meta-item {
            font-size: 0.875rem;
            color: var(--gray-600);
            display: flex;
            align-items: center;
        }
        
        .job-meta-item i {
            margin-right: 0.25rem;
        }
        
        .job-list-description {
            color: var(--gray-700);
            font-size: 0.9rem;
            margin-bottom: 1rem;
            overflow: hidden;
            text-overflow: ellipsis;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
        }
        
        .job-list-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-top: 1px solid var(--gray-200);
            padding-top: 1rem;
        }
        
        .job-date {
            font-size: 0.8rem;
            color: var(--gray-500);
        }
        
        .match-percentage {
            background-color: rgba(255, 153, 0, 0.1);
            color: var(--accent-color);
            font-weight: 500;
            padding: 0.25rem 0.5rem;
            border-radius: 1rem;
            font-size: 0.75rem;
            margin-right: 0.5rem;
            display: inline-flex;
            align-items: center;
        }
        
        .match-percentage i {
            margin-right: 0.25rem;
        }
        
        .btn-view-job {
            padding: 0.5rem 0.75rem;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 0.25rem;
            font-size: 0.875rem;
            font-weight: 500;
            text-decoration: none;
            transition: background-color 0.2s;
        }
        
        .btn-view-job:hover {
            background-color: #00264d;
        }
        
        /* Ningún resultado */
        .no-jobs-found {
            background-color: white;
            border-radius: 10px;
            padding: 3rem 1.5rem;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }
        
        .no-jobs-found i {
            font-size: 3rem;
            color: var(--gray-400);
            margin-bottom: 1rem;
        }
        
        .no-jobs-found h3 {
            margin-top: 0;
            margin-bottom: 0.5rem;
            color: var(--gray-800);
        }
        
        .no-jobs-found p {
            color: var(--gray-600);
            margin-bottom: 1.5rem;
        }
        
        /* Paginación */
        .pagination-container {
            margin-top: 2rem;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .page-item {
            margin: 0 0.25rem;
        }
        
        .pagination-link {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0.5rem 0.75rem;
            border-radius: 0.25rem;
            font-size: 0.875rem;
            text-decoration: none;
            transition: all 0.2s;
        }
        
        .pagination-link.number {
            min-width: 2rem;
            background-color: white;
            color: var(--gray-700);
            border: 1px solid var(--gray-300);
        }
        
        .pagination-link.number:hover,
        .pagination-link.prev:hover,
        .pagination-link.next:hover {
            background-color: var(--gray-200);
        }
        
        .pagination-link.number.active {
            background-color: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }
        
        .pagination-link.prev,
        .pagination-link.next {
            background-color: white;
            color: var(--gray-700);
            border: 1px solid var(--gray-300);
        }
        
        /* Formulario */
        .form-control {
            width: 100%;
            padding: 0.5rem 0.75rem;
            font-size: 0.875rem;
            line-height: 1.5;
            border: 1px solid var(--gray-300);
            border-radius: 0.25rem;
            transition: border-color 0.2s;
        }
        
        .form-control:focus {
            border-color: var(--secondary-color);
            outline: 0;
            box-shadow: 0 0 0 0.2rem rgba(0, 136, 204, 0.25);
        }
        
        /* Estilos para móvil */
        @media (max-width: 991px) {
            .job-listing-layout {
                grid-template-columns: 1fr;
            }
            
            .filter-sidebar {
                position: fixed;
                top: 0;
                left: -300px;
                height: 100vh;
                width: 280px;
                z-index: 1000;
                transition: left 0.3s ease;
                border-radius: 0;
                overflow-y: auto;
            }
            
            .filter-sidebar.active {
                left: 0;
            }
            
            .mobile-filter-toggle {
                display: flex;
            }
            
            .filter-backdrop {
                display: none;
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background-color: rgba(0, 0, 0, 0.5);
                z-index: 999;
            }
            
            .filter-backdrop.active {
                display: block;
            }
            
            .filter-actions {
                position: sticky;
                bottom: 0;
                background-color: white;
                padding-top: 1rem;
                padding-bottom: 1rem;
            }
            
            .filter-close {
                position: absolute;
                top: 1rem;
                right: 1rem;
                background: none;
                border: none;
                font-size: 1.25rem;
                color: var(--gray-600);
                cursor: pointer;
                display: block;
            }
        }
        
        @media (max-width: 767px) {
            .job-list-card {
                flex-direction: column;
            }
            
            .job-list-logo {
                margin-right: 0;
                margin-bottom: 1rem;
            }
            
            .job-list-meta {
                flex-direction: column;
                gap: 0.5rem;
                align-items: flex-start;
            }
            
            .job-list-footer {
                flex-direction: column;
                gap: 1rem;
                align-items: flex-start;
            }
        }
        
        /* Utilidades */
        .mb-4 {
            margin-bottom: 1.5rem;
        }
        
        /* Dashboard content updates */
        .dashboard-content {
            padding: 1.5rem;
        }
        
        /* Animaciones */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .animate-fade-in {
            animation: fadeIn 0.3s ease-in-out forwards;
        }
    </style>
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <!-- Navbar -->
    <?php include 'includes/navbar.php'; ?>
    
    <div class="dashboard-container">
        <!-- Sidebar -->
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="dashboard-content">
            <div class="content-header">
                <h1>Explora Nuestras Vacantes</h1>
                <p class="welcome-message">Encuentra oportunidades laborales que se adapten a tu perfil profesional</p>
            </div>
            
            <div class="job-listing-layout">
                <!-- Sidebar con filtros -->
                <aside class="filter-sidebar">
                    <div class="filter-header">
                        <h3>Filtros</h3>
                        <button type="button" class="filter-reset" id="resetFilters">Limpiar filtros</button>
                        <button type="button" class="filter-close d-md-none" id="closeFilters">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    
                    <form action="vacantes.php" method="GET" id="filterForm">
                        <!-- Buscador -->
                        <div class="filter-group">
                            <label for="q" class="filter-label">Palabra clave</label>
                            <div class="filter-search">
                                <input type="text" id="q" name="q" value="<?php echo htmlspecialchars($q); ?>" placeholder="Buscar..." class="form-control">
                                <button type="submit" class="filter-search-btn">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </div>
                        
                        <!-- Categoría -->
                        <div class="filter-group">
                            <h3>Categoría</h3>
                            <div class="filter-options">
                                <?php foreach ($categorias as $categoria): ?>
                                <div class="filter-checkbox">
                                    <input type="radio" id="categoria-<?php echo $categoria['id']; ?>" name="categoria" value="<?php echo $categoria['id']; ?>" <?php echo $categoriaId == $categoria['id'] ? 'checked' : ''; ?>>
                                    <label for="categoria-<?php echo $categoria['id']; ?>">
                                        <?php echo htmlspecialchars($categoria['nombre']); ?>
                                        <span class="filter-count">(<?php echo $categoria['vacantes_count'] ?? 0; ?>)</span>
                                    </label>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                        <!-- Ubicación -->
                        <div class="filter-group">
                            <h3>Ubicación</h3>
                            <div class="filter-options">
                                <div class="filter-checkbox">
                                    <input type="radio" id="ubicacion-sd" name="ubicacion" value="Santo Domingo" <?php echo $ubicacion === 'Santo Domingo' ? 'checked' : ''; ?>>
                                    <label for="ubicacion-sd">Santo Domingo</label>
                                </div>
                                <div class="filter-checkbox">
                                    <input type="radio" id="ubicacion-stgo" name="ubicacion" value="Santiago" <?php echo $ubicacion === 'Santiago' ? 'checked' : ''; ?>>
                                    <label for="ubicacion-stgo">Santiago</label>
                                </div>
                                <div class="filter-checkbox">
                                    <input type="radio" id="ubicacion-remoto" name="ubicacion" value="Remoto" <?php echo $ubicacion === 'Remoto' ? 'checked' : ''; ?>>
                                    <label for="ubicacion-remoto">Remoto</label>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Modalidad -->
                        <div class="filter-group">
                            <h3>Modalidad</h3>
                            <div class="filter-options">
                                <div class="filter-checkbox">
                                    <input type="radio" id="modalidad-presencial" name="modalidad" value="presencial" <?php echo $modalidad === 'presencial' ? 'checked' : ''; ?>>
                                    <label for="modalidad-presencial">Presencial</label>
                                </div>
                                <div class="filter-checkbox">
                                    <input type="radio" id="modalidad-remoto" name="modalidad" value="remoto" <?php echo $modalidad === 'remoto' ? 'checked' : ''; ?>>
                                    <label for="modalidad-remoto">Remoto</label>
                                </div>
                                <div class="filter-checkbox">
                                    <input type="radio" id="modalidad-hibrido" name="modalidad" value="hibrido" <?php echo $modalidad === 'hibrido' ? 'checked' : ''; ?>>
                                    <label for="modalidad-hibrido">Híbrido</label>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Ordenamiento -->
                        <div class="filter-group">
                            <h3>Ordenar por</h3>
                            <select name="orden" class="form-control">
                                <option value="fecha_desc" <?php echo $orderBy === 'fecha_desc' ? 'selected' : ''; ?>>Más recientes primero</option>
                                <option value="fecha_asc" <?php echo $orderBy === 'fecha_asc' ? 'selected' : ''; ?>>Más antiguos primero</option>
                                <option value="titulo_asc" <?php echo $orderBy === 'titulo_asc' ? 'selected' : ''; ?>>Título (A-Z)</option>
                                <option value="titulo_desc" <?php echo $orderBy === 'titulo_desc' ? 'selected' : ''; ?>>Título (Z-A)</option>
                            </select>
                        </div>
                        
                        <input type="hidden" name="page" value="1">
                        
                        <div class="filter-actions">
                            <button type="submit" class="btn-filter-apply">Aplicar Filtros</button>
                        </div>
                    </form>
                </aside>
                
                <!-- Listado principal -->
                <div class="jobs-main">
                    <div class="listing-header">
                        <div class="results-count">
                            <strong><?php echo $totalVacantes; ?></strong> vacantes encontradas
                        </div>
                        <div class="mobile-filter-toggle" id="mobileFilterToggle">
                            <i class="fas fa-filter"></i> Filtrar
                        </div>
                    </div>
                    
                    <!-- Lista de vacantes -->
                    <div class="jobs-list">
                        <?php if (empty($vacantes)): ?>
                            <div class="no-jobs-found">
                                <i class="fas fa-search"></i>
                                <h3>No se encontraron vacantes</h3>
                                <p>Intenta ajustar los filtros o realiza una nueva búsqueda.</p>
                                <a href="vacantes.php" class="btn-view-job">Ver todas las vacantes</a>
                            </div>
                        <?php else: ?>
                            <?php foreach ($vacantes as $vacante): ?>
                            <div class="job-list-card animate-fade-in">
                                <div class="job-list-logo">
                                    <img src="../img/logo-icon.png" alt="SolFis" class="company-logo">
                                </div>
                                <div class="job-list-content">
                                    <div class="job-list-title">
                                        <h3><a href="detalle-vacante.php?id=<?php echo $vacante['id']; ?>"><?php echo htmlspecialchars($vacante['titulo']); ?></a></h3>
                                        <?php if (isset($vacante['destacada']) && $vacante['destacada']): ?>
                                        <span class="badge-featured">Destacada</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="job-list-meta">
                                        <span class="job-meta-item"><i class="fas fa-briefcase"></i> <?php echo htmlspecialchars($vacante['categoria_nombre']); ?></span>
                                        <span class="job-meta-item"><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($vacante['ubicacion']); ?></span>
                                        <span class="job-meta-item"><i class="fas fa-building"></i> <?php echo ucfirst(htmlspecialchars($vacante['modalidad'])); ?></span>
                                        <span class="job-meta-item"><i class="fas fa-clock"></i> <?php echo ucfirst(str_replace('_', ' ', htmlspecialchars($vacante['tipo_contrato']))); ?></span>
                                    </div>
                                    <div class="job-list-description">
                                        <?php 
                                        // Función para truncar texto si existe
                                        if (function_exists('VacancyUtils::truncate')) {
                                            echo VacancyUtils::truncate(htmlspecialchars($vacante['descripcion']), 150);
                                        } else {
                                            // Función básica para truncar texto
                                            $text = htmlspecialchars($vacante['descripcion']);
                                            if (strlen($text) > 150) {
                                                $text = substr($text, 0, 150) . '...';
                                            }
                                            echo $text;
                                        }
                                        ?>
                                    </div>
                                    <div class="job-list-footer">
                                        <div>
                                            <span class="job-date"><i class="far fa-calendar-alt"></i> Publicada: <?php echo date('d M Y', strtotime($vacante['fecha_publicacion'])); ?></span>
                                            <?php if (isset($vacante['match_percentage'])): ?>
                                            <span class="match-percentage"><i class="fas fa-star"></i> <?php echo $vacante['match_percentage']; ?>% compatibilidad</span>
                                            <?php endif; ?>
                                        </div>
                                        <a href="detalle-vacante.php?id=<?php echo $vacante['id']; ?>" class="btn-view-job">Ver Vacante</a>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Paginación -->
                    <?php if ($totalPages > 1): ?>
                    <div class="pagination-container">
                        <ul class="pagination">
                            <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="pagination-link prev" href="<?php echo '?page=' . ($page - 1) . '&q=' . urlencode($q) . '&ubicacion=' . urlencode($ubicacion) . '&categoria=' . $categoriaId . '&modalidad=' . $modalidad . '&orden=' . $orderBy; ?>">
                                    <i class="fas fa-chevron-left"></i> Anterior
                                </a>
                            </li>
                            <?php endif; ?>
                            
                            <?php 
                            // Mostrar solo 5 páginas alrededor de la página actual
                            $startPage = max(1, $page - 2);
                            $endPage = min($totalPages, $page + 2);
                            
                            // Mostrar siempre la primera página
                            if ($startPage > 1) {
                                echo '<li class="page-item"><a class="pagination-link number" href="?page=1&q=' . urlencode($q) . '&ubicacion=' . urlencode($ubicacion) . '&categoria=' . $categoriaId . '&modalidad=' . $modalidad . '&orden=' . $orderBy . '">1</a></li>';
                                if ($startPage > 2) {
                                    echo '<li class="page-item"><span class="pagination-link number">...</span></li>';
                                }
                            }
                            
                            // Páginas centrales
                            for ($i = $startPage; $i <= $endPage; $i++): 
                            ?>
                            <li class="page-item">
                                <a class="pagination-link number <?php echo $i === $page ? 'active' : ''; ?>" href="<?php echo '?page=' . $i . '&q=' . urlencode($q) . '&ubicacion=' . urlencode($ubicacion) . '&categoria=' . $categoriaId . '&modalidad=' . $modalidad . '&orden=' . $orderBy; ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                            <?php endfor; ?>
                            
                            <?php 
                            // Mostrar siempre la última página
                            if ($endPage < $totalPages) {
                                if ($endPage < $totalPages - 1) {
                                    echo '<li class="page-item"><span class="pagination-link number">...</span></li>';
                                }
                                echo '<li class="page-item"><a class="pagination-link number" href="?page=' . $totalPages . '&q=' . urlencode($q) . '&ubicacion=' . urlencode($ubicacion) . '&categoria=' . $categoriaId . '&modalidad=' . $modalidad . '&orden=' . $orderBy . '">' . $totalPages . '</a></li>';
                            }
                            ?>
                            
                            <?php if ($page < $totalPages): ?>
                            <li class="page-item">
                                <a class="pagination-link next" href="<?php echo '?page=' . ($page + 1) . '&q=' . urlencode($q) . '&ubicacion=' . urlencode($ubicacion) . '&categoria=' . $categoriaId . '&modalidad=' . $modalidad . '&orden=' . $orderBy; ?>">
                                    Siguiente <i class="fas fa-chevron-right"></i>
                                </a>
                            </li>
                            <?php endif; ?>
                        </ul>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
    
    <!-- Overlay para filtros en móvil -->
    <div class="filter-backdrop" id="filterBackdrop"></div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Variables
            const mobileFilterToggle = document.getElementById('mobileFilterToggle');
            const filterSidebar = document.querySelector('.filter-sidebar');
            const filterBackdrop = document.getElementById('filterBackdrop');
            const closeFilters = document.getElementById('closeFilters');
            const resetFilters = document.getElementById('resetFilters');
            const radioFilters = document.querySelectorAll('.filter-checkbox input[type="radio"]');
            const orderSelect = document.querySelector('select[name="orden"]');
            
            // Toggle filtros en móvil
            if (mobileFilterToggle) {
                mobileFilterToggle.addEventListener('click', function() {
                    filterSidebar.classList.add('active');
                    filterBackdrop.classList.add('active');
                    document.body.style.overflow = 'hidden'; // Prevenir scroll
                });
            }
            
            // Cerrar filtros
            if (closeFilters) {
                closeFilters.addEventListener('click', function() {
                    filterSidebar.classList.remove('active');
                    filterBackdrop.classList.remove('active');
                    document.body.style.overflow = ''; // Restaurar scroll
                });
            }
            
            // Click en backdrop
            if (filterBackdrop) {
                filterBackdrop.addEventListener('click', function() {
                    filterSidebar.classList.remove('active');
                    filterBackdrop.classList.remove('active');
                    document.body.style.overflow = ''; // Restaurar scroll
                });
            }
            
            // Resetear filtros
            if (resetFilters) {
                resetFilters.addEventListener('click', function() {
                    window.location.href = 'vacantes.php';
                });
            }
            
            // Autosubmit al cambiar filtros de radio
            radioFilters.forEach(filter => {
                filter.addEventListener('change', function() {
                    document.getElementById('filterForm').submit();
                });
            });
            
            // Autosubmit al cambiar ordenamiento
            if (orderSelect) {
                orderSelect.addEventListener('change', function() {
                    document.getElementById('filterForm').submit();
                });
            }
        });
    </script>
</body>
</html>