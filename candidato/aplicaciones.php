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
$applicationManager = new ApplicationManager();
$vacancyManager = new VacancyManager();

// Obtener datos del candidato
$candidato_id = $_SESSION['candidato_id'];
$candidato = $candidateManager->getCandidateById($candidato_id);

// Si no existe el candidato, cerrar sesión
if (!$candidato) {
    session_destroy();
    header('Location: login.php?error=candidato_no_encontrado');
    exit;
}

// Obtener aplicaciones del candidato
$aplicaciones = $applicationManager->getApplicationsByCandidate($candidato_id);

// Título de la página
$site_title = "Mis Aplicaciones - SolFis Talentos";
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
    
    <!-- Estilos personalizados para las aplicaciones -->
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
        
        /* Estilos para la tabla de aplicaciones */
        .applications-table-container {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            padding: 1.5rem;
            overflow-x: auto;
        }
        
        .applications-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }
        
        .applications-table th,
        .applications-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid var(--gray-200);
        }
        
        .applications-table th {
            background-color: var(--gray-100);
            font-weight: 600;
            color: var(--gray-700);
            position: sticky;
            top: 0;
            z-index: 10;
        }
        
        .applications-table th:first-child {
            border-top-left-radius: 0.5rem;
        }
        
        .applications-table th:last-child {
            border-top-right-radius: 0.5rem;
        }
        
        .applications-table tbody tr:hover {
            background-color: var(--gray-100);
        }
        
        .applications-table tbody tr:last-child td {
            border-bottom: none;
        }
        
        .applications-table td.job-info {
            max-width: 300px;
        }
        
        .applications-table .job-title {
            font-weight: 500;
            color: var(--gray-800);
            margin-bottom: 0.25rem;
            display: block;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .applications-table .job-title a {
            color: var(--gray-800);
            text-decoration: none;
        }
        
        .applications-table .job-title a:hover {
            color: var(--primary-color);
        }
        
        .applications-table .job-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            font-size: 0.8rem;
            color: var(--gray-600);
        }
        
        .applications-table .job-meta-item {
            display: flex;
            align-items: center;
        }
        
        .applications-table .job-meta-item i {
            margin-right: 0.25rem;
            font-size: 0.75rem;
        }
        
        /* Estado de la aplicación */
        .application-status {
            display: inline-flex;
            align-items: center;
            padding: 0.35rem 0.75rem;
            border-radius: 2rem;
            font-size: 0.75rem;
            font-weight: 500;
            white-space: nowrap;
        }
        
        .application-status.recibida {
            background-color: rgba(23, 162, 184, 0.1);
            color: var(--info-color);
        }
        
        .application-status.revision {
            background-color: rgba(0, 51, 102, 0.1);
            color: var(--primary-color);
        }
        
        .application-status.entrevista {
            background-color: rgba(255, 193, 7, 0.1);
            color: #d39e00;
        }
        
        .application-status.prueba {
            background-color: rgba(255, 193, 7, 0.1);
            color: #d39e00;
        }
        
        .application-status.oferta {
            background-color: rgba(40, 167, 69, 0.1);
            color: var(--success-color);
        }
        
        .application-status.contratado {
            background-color: rgba(40, 167, 69, 0.1);
            color: var(--success-color);
        }
        
        .application-status.rechazada {
            background-color: rgba(220, 53, 69, 0.1);
            color: var(--danger-color);
        }
        
        .application-status i {
            margin-right: 0.4rem;
        }
        
        /* Botones de acción */
        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }
        
        .btn-action {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background-color: var(--gray-200);
            color: var(--gray-700);
            border: none;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .btn-action:hover {
            background-color: var(--gray-300);
        }
        
        .btn-action.view {
            background-color: rgba(0, 136, 204, 0.1);
            color: var(--secondary-color);
        }
        
        .btn-action.view:hover {
            background-color: var(--secondary-color);
            color: white;
        }
        
        /* Card para información cuando no hay aplicaciones */
        .no-applications-card {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            padding: 3rem 1.5rem;
            text-align: center;
        }
        
        .no-applications-card i {
            font-size: 3.5rem;
            color: var(--gray-400);
            margin-bottom: 1.5rem;
        }
        
        .no-applications-card h3 {
            margin-top: 0;
            margin-bottom: 0.75rem;
            color: var(--gray-800);
            font-size: 1.5rem;
        }
        
        .no-applications-card p {
            color: var(--gray-600);
            margin-bottom: 1.5rem;
            max-width: 500px;
            margin-left: auto;
            margin-right: auto;
        }
        
        /* Filtros de aplicaciones */
        .applications-filters {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
            gap: 1rem;
        }
        
        .filter-group {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .filter-label {
            font-size: 0.875rem;
            color: var(--gray-700);
            font-weight: 500;
        }
        
        .filter-select {
            padding: 0.5rem 2rem 0.5rem 0.75rem;
            border: 1px solid var(--gray-300);
            border-radius: 0.25rem;
            font-size: 0.875rem;
            color: var(--gray-800);
            background-color: white;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='8' height='5' viewBox='0 0 8 5'%3E%3Cpath fill='%236c757d' d='M4 5L0 0h8z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 0.75rem center;
            background-size: 8px 5px;
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;
        }
        
        .search-box {
            position: relative;
        }
        
        .search-input {
            padding: 0.5rem 0.75rem;
            padding-left: 2.25rem;
            border: 1px solid var(--gray-300);
            border-radius: 0.25rem;
            font-size: 0.875rem;
            width: 300px;
            max-width: 100%;
        }
        
        .search-icon {
            position: absolute;
            left: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray-500);
            font-size: 0.875rem;
        }
        
        /* Paginación */
        .pagination-container {
            margin-top: 2rem;
            display: flex;
            justify-content: center;
        }
        
        .pagination {
            display: flex;
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .page-item {
            margin: 0 0.25rem;
        }
        
        .page-link {
            display: flex;
            align-items: center;
            justify-content: center;
            min-width: 2rem;
            height: 2.25rem;
            padding: 0 0.75rem;
            border: 1px solid var(--gray-300);
            border-radius: 0.25rem;
            background-color: white;
            color: var(--gray-700);
            text-decoration: none;
            font-size: 0.875rem;
            transition: all 0.2s;
        }
        
        .page-link:hover {
            background-color: var(--gray-200);
            border-color: var(--gray-400);
        }
        
        .page-item.active .page-link {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            color: white;
        }
        
        .page-item.disabled .page-link {
            color: var(--gray-500);
            pointer-events: none;
        }
        
        /* Estadísticas de aplicación */
        .application-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            padding: 1.25rem;
            text-align: center;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
        
        .stat-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: var(--gray-100);
            color: var(--gray-700);
            margin: 0 auto 0.75rem;
        }
        
        .stat-card.primary .stat-icon {
            background-color: rgba(0, 51, 102, 0.1);
            color: var(--primary-color);
        }
        
        .stat-card.info .stat-icon {
            background-color: rgba(23, 162, 184, 0.1);
            color: var(--info-color);
        }
        
        .stat-card.success .stat-icon {
            background-color: rgba(40, 167, 69, 0.1);
            color: var(--success-color);
        }
        
        .stat-card.warning .stat-icon {
            background-color: rgba(255, 193, 7, 0.1);
            color: #d39e00;
        }
        
        .stat-card.danger .stat-icon {
            background-color: rgba(220, 53, 69, 0.1);
            color: var(--danger-color);
        }
        
        .stat-value {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--gray-800);
            margin-bottom: 0.25rem;
        }
        
        .stat-label {
            font-size: 0.875rem;
            color: var(--gray-600);
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
        
        /* Media queries */
        @media (max-width: 991px) {
            .applications-table th:nth-child(3),
            .applications-table td:nth-child(3) {
                display: none;
            }
        }
        
        @media (max-width: 767px) {
            .applications-table th:nth-child(4),
            .applications-table td:nth-child(4) {
                display: none;
            }
            
            .applications-filters {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .search-input {
                width: 100%;
            }
        }
        
        @media (max-width: 575px) {
            .applications-table th:nth-child(2),
            .applications-table td:nth-child(2) {
                display: none;
            }
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
                <h1>Mis Aplicaciones</h1>
                <a href="vacantes.php" class="btn-outline-primary">
                    <i class="fas fa-search"></i> Explorar Vacantes
                </a>
            </div>
            
            <?php if (!empty($aplicaciones)): ?>
                <!-- Estadísticas de aplicaciones -->
                <?php
                // Contar aplicaciones por estado
                $conteo_estados = [
                    'total' => count($aplicaciones),
                    'recibida' => 0,
                    'revision' => 0,
                    'entrevista' => 0,
                    'prueba' => 0,
                    'oferta' => 0,
                    'contratado' => 0,
                    'rechazada' => 0
                ];
                
                foreach ($aplicaciones as $aplicacion) {
                    if (isset($conteo_estados[$aplicacion['estado']])) {
                        $conteo_estados[$aplicacion['estado']]++;
                    }
                }
                ?>
                
                <div class="application-stats animate-fade-in">
                    <div class="stat-card primary">
                        <div class="stat-icon">
                            <i class="fas fa-briefcase"></i>
                        </div>
                        <div class="stat-value"><?php echo $conteo_estados['total']; ?></div>
                        <div class="stat-label">Total de aplicaciones</div>
                    </div>
                    
                    <div class="stat-card info">
                        <div class="stat-icon">
                            <i class="fas fa-clipboard-check"></i>
                        </div>
                        <div class="stat-value"><?php echo $conteo_estados['recibida'] + $conteo_estados['revision']; ?></div>
                        <div class="stat-label">En proceso</div>
                    </div>
                    
                    <div class="stat-card warning">
                        <div class="stat-icon">
                            <i class="fas fa-user-tie"></i>
                        </div>
                        <div class="stat-value"><?php echo $conteo_estados['entrevista'] + $conteo_estados['prueba']; ?></div>
                        <div class="stat-label">En entrevista/prueba</div>
                    </div>
                    
                    <div class="stat-card success">
                        <div class="stat-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="stat-value"><?php echo $conteo_estados['oferta'] + $conteo_estados['contratado']; ?></div>
                        <div class="stat-label">Ofertas/Contrataciones</div>
                    </div>
                </div>
                
                <!-- Filtros de aplicaciones -->
                <div class="applications-filters animate-fade-in">
                    <div class="filter-group">
                        <label for="statusFilter" class="filter-label">Estado:</label>
                        <select id="statusFilter" class="filter-select">
                            <option value="">Todos los estados</option>
                            <option value="recibida">Recibida</option>
                            <option value="revision">En revisión</option>
                            <option value="entrevista">Entrevista</option>
                            <option value="prueba">Prueba</option>
                            <option value="oferta">Oferta</option>
                            <option value="contratado">Contratado</option>
                            <option value="rechazada">Rechazada</option>
                        </select>
                    </div>
                    
                    <div class="search-box">
                        <i class="fas fa-search search-icon"></i>
                        <input type="text" id="searchApplications" class="search-input" placeholder="Buscar por título o empresa...">
                    </div>
                </div>
                
                <!-- Tabla de aplicaciones -->
                <div class="applications-table-container animate-fade-in">
                    <table class="applications-table" id="applicationsTable">
                        <thead>
                            <tr>
                                <th>Posición</th>
                                <th>Fecha de aplicación</th>
                                <th>Categoría</th>
                                <th>Ubicación</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($aplicaciones as $aplicacion): ?>
                            <tr>
                                <td class="job-info">
                                    <span class="job-title">
                                        <a href="detalle-vacante.php?id=<?php echo $aplicacion['vacante_id']; ?>"><?php echo htmlspecialchars($aplicacion['vacante_titulo']); ?></a>
                                    </span>
                                    <div class="job-meta">
                                        <span class="job-meta-item">
                                            <i class="fas fa-building"></i> <?php echo ucfirst(htmlspecialchars($aplicacion['modalidad'])); ?>
                                        </span>
                                        <span class="job-meta-item">
                                            <i class="fas fa-clock"></i> <?php echo ucfirst(str_replace('_', ' ', htmlspecialchars($aplicacion['tipo_contrato']))); ?>
                                        </span>
                                    </div>
                                </td>
                                <td>
                                    <?php echo date('d/m/Y', strtotime($aplicacion['fecha_aplicacion'])); ?>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($aplicacion['categoria_nombre']); ?>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($aplicacion['vacante_ubicacion'] ?? $aplicacion['ubicacion'] ?? ''); ?>
                                </td>
                                <td>
                                    <span class="application-status <?php echo $aplicacion['estado']; ?>">
                                        <?php
                                        // Icono según estado
                                        $statusIcon = '';
                                        switch ($aplicacion['estado']) {
                                            case 'recibida':
                                                $statusIcon = 'fas fa-inbox';
                                                break;
                                            case 'revision':
                                                $statusIcon = 'fas fa-eye';
                                                break;
                                            case 'entrevista':
                                                $statusIcon = 'fas fa-user-tie';
                                                break;
                                            case 'prueba':
                                                $statusIcon = 'fas fa-clipboard-check';
                                                break;
                                            case 'oferta':
                                                $statusIcon = 'fas fa-file-contract';
                                                break;
                                            case 'contratado':
                                                $statusIcon = 'fas fa-handshake';
                                                break;
                                            case 'rechazada':
                                                $statusIcon = 'fas fa-times-circle';
                                                break;
                                            default:
                                                $statusIcon = 'fas fa-circle';
                                        }
                                        ?>
                                        <i class="<?php echo $statusIcon; ?>"></i>
                                        <?php echo ucfirst($aplicacion['estado']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="aplicacion.php?id=<?php echo $aplicacion['id']; ?>" class="btn-action view" title="Ver detalles">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <!-- Mensaje cuando no hay aplicaciones -->
                <div class="no-applications-card animate-fade-in">
                    <i class="fas fa-clipboard"></i>
                    <h3>No tienes aplicaciones aún</h3>
                    <p>Todavía no has aplicado a ninguna vacante. Explora nuestras vacantes disponibles y encuentra la oportunidad perfecta para ti.</p>
                    <a href="vacantes.php" class="btn-primary">
                        <i class="fas fa-search"></i> Explorar Vacantes
                    </a>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Filtrar por estado
            const statusFilter = document.getElementById('statusFilter');
            if (statusFilter) {
                statusFilter.addEventListener('change', filterApplications);
            }
            
            // Búsqueda
            const searchInput = document.getElementById('searchApplications');
            if (searchInput) {
                searchInput.addEventListener('keyup', filterApplications);
            }
            
            // Función para filtrar aplicaciones
            function filterApplications() {
                const table = document.getElementById('applicationsTable');
                if (!table) return;
                
                const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');
                const statusValue = statusFilter.value.toLowerCase();
                const searchValue = searchInput.value.toLowerCase();
                
                for (let i = 0; i < rows.length; i++) {
                    const row = rows[i];
                    const jobTitle = row.cells[0].getElementsByClassName('job-title')[0].textContent.toLowerCase();
                    const status = row.cells[4].textContent.toLowerCase();
                    const category = row.cells[2].textContent.toLowerCase();
                    
                    // Verificar si coincide con el filtro de estado y la búsqueda
                    const matchesStatus = statusValue === '' || status.includes(statusValue);
                    const matchesSearch = searchValue === '' || 
                                         jobTitle.includes(searchValue) || 
                                         category.includes(searchValue);
                    
                    // Mostrar u ocultar fila
                    row.style.display = (matchesStatus && matchesSearch) ? '' : 'none';
                }
            }
        });
    </script>
</body>
</html>