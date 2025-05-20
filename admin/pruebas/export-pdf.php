<?php
/**
 * Panel de Administración para SolFis
 * admin/pruebas/export-pdf.php - Exportar resultados a PDF
 */

// Inicializar sesión
session_start();

// Incluir archivos necesarios
require_once '../config.php';
require_once '../../includes/blog-system.php';
require_once '../../includes/jobs-system.php';

// Verificar autenticación
$auth = Auth::getInstance();
if (!$auth->isLoggedIn()) {
    header('Location: ../login.php');
    exit;
}

// Verificar que se proporciona un ID de sesión o candidato
if ((!isset($_GET['session_id']) || empty($_GET['session_id'])) && 
    (!isset($_GET['candidato_id']) || empty($_GET['candidato_id']))) {
    $_SESSION['error'] = "ID de sesión o candidato no proporcionado";
    header('Location: index.php');
    exit;
}

// Modo de exportación: 'session' para una sesión específica, 'candidate' para informe completo
$export_mode = isset($_GET['session_id']) ? 'session' : 'candidate';
$id = $export_mode === 'session' ? (int)$_GET['session_id'] : (int)$_GET['candidato_id'];

// Configurar datos según el modo
if ($export_mode === 'session') {
    // Exportar resultados de una sola prueba
    $pdf_title = "Resultados de Prueba - Sesión #$id";
    $pdf_filename = "resultados_prueba_$id.pdf";
} else {
    // Exportar informe completo del candidato
    $pdf_title = "Informe Completo de Evaluación - Candidato #$id";
    $pdf_filename = "informe_candidato_$id.pdf";
}

// Aquí iría la lógica real de generación del PDF con una biblioteca como FPDF, TCPDF o Dompdf
// Para este ejemplo, creamos un archivo HTML simple para descargar

// Obtener datos según el modo
$db = Database::getInstance();
$data = [];

if ($export_mode === 'session') {
    // Datos de una sesión específica
    $session_id = $db->real_escape_string($id);
    
    $sql = "SELECT sp.*, p.titulo as prueba_titulo, p.descripcion as prueba_descripcion, 
                   c.id as candidato_id, c.nombre as candidato_nombre, c.apellido as candidato_apellido, 
                   c.email as candidato_email, c.foto_path as candidato_foto
            FROM sesiones_prueba sp
            JOIN pruebas p ON sp.prueba_id = p.id
            JOIN candidatos c ON sp.candidato_id = c.id
            WHERE sp.id = '$session_id'";
    
    $result = $db->query($sql);
    
    if ($result && $result->num_rows > 0) {
        $data['prueba'] = $result->fetch_assoc();
        $data['candidato_id'] = $data['prueba']['candidato_id'];
        
        // Obtener resultados por dimensiones
        $sqlDimensiones = "SELECT d.id, d.nombre, d.descripcion, d.categoria, 
                            AVG(r.valor) as promedio,
                            MIN(r.valor) as valor_min,
                            MAX(r.valor) as valor_max,
                            COUNT(r.id) as num_respuestas,
                            CASE 
                                WHEN AVG(r.valor) >= 90 THEN 'Excepcional' 
                                WHEN AVG(r.valor) >= 80 THEN 'Sobresaliente'
                                WHEN AVG(r.valor) >= 70 THEN 'Notable'
                                WHEN AVG(r.valor) >= 60 THEN 'Adecuado' 
                                WHEN AVG(r.valor) >= 50 THEN 'Moderado'
                                WHEN AVG(r.valor) >= 35 THEN 'En desarrollo'
                                ELSE 'Incipiente' 
                            END as nivel
                     FROM resultados r
                     JOIN dimensiones d ON r.dimension_id = d.id
                     WHERE r.sesion_id = '$session_id'
                     GROUP BY d.id
                     ORDER BY promedio DESC";
        
        $resultDimensiones = $db->query($sqlDimensiones);
        $data['dimensiones'] = [];
        
        if ($resultDimensiones && $resultDimensiones->num_rows > 0) {
            while ($row = $resultDimensiones->fetch_assoc()) {
                $data['dimensiones'][] = $row;
            }
        }
        
        // Calcular promedio global
        $data['promedio_global'] = 0;
        if (!empty($data['dimensiones'])) {
            $total = 0;
            foreach ($data['dimensiones'] as $dim) {
                $total += $dim['promedio'];
            }
            $data['promedio_global'] = round($total / count($data['dimensiones']));
        }
    } else {
        die("Sesión de prueba no encontrada");
    }
} else {
    // Datos completos del candidato
    $candidato_id = $db->real_escape_string($id);
    
    $sql = "SELECT * FROM candidatos WHERE id = '$candidato_id'";
    $result = $db->query($sql);
    
    if ($result && $result->num_rows > 0) {
        $data['candidato'] = $result->fetch_assoc();
        
        // Obtener pruebas completadas
        $sqlPruebas = "SELECT sp.*, p.titulo as prueba_titulo, p.descripcion as prueba_descripcion
                       FROM sesiones_prueba sp
                       JOIN pruebas p ON sp.prueba_id = p.id
                       WHERE sp.candidato_id = '$candidato_id' AND sp.estado = 'completada'
                       ORDER BY sp.fecha_fin DESC";
        
        $resultPruebas = $db->query($sqlPruebas);
        $data['pruebas'] = [];
        
        if ($resultPruebas && $resultPruebas->num_rows > 0) {
            while ($row = $resultPruebas->fetch_assoc()) {
                $data['pruebas'][] = $row;
            }
        }
        
        // Obtener dimensiones evaluadas
        $sqlDimensiones = "SELECT d.id, d.nombre, d.categoria, 
                           AVG(r.valor) as promedio,
                           CASE 
                               WHEN AVG(r.valor) >= 90 THEN 'Excepcional' 
                               WHEN AVG(r.valor) >= 80 THEN 'Sobresaliente'
                               WHEN AVG(r.valor) >= 70 THEN 'Notable'
                               WHEN AVG(r.valor) >= 60 THEN 'Adecuado' 
                               WHEN AVG(r.valor) >= 50 THEN 'Moderado'
                               WHEN AVG(r.valor) >= 35 THEN 'En desarrollo'
                               ELSE 'Incipiente' 
                           END as nivel
                        FROM resultados r
                        JOIN dimensiones d ON r.dimension_id = d.id
                        JOIN sesiones_prueba sp ON r.sesion_id = sp.id
                        WHERE sp.candidato_id = '$candidato_id' AND sp.estado = 'completada'
                        GROUP BY d.id
                        ORDER BY promedio DESC";
        
        $resultDimensiones = $db->query($sqlDimensiones);
        $data['dimensiones'] = [];
        
        if ($resultDimensiones && $resultDimensiones->num_rows > 0) {
            while ($row = $resultDimensiones->fetch_assoc()) {
                $data['dimensiones'][] = $row;
            }
        }
        
        // Calcular promedio global
        $data['promedio_global'] = 0;
        if (!empty($data['dimensiones'])) {
            $total = 0;
            foreach ($data['dimensiones'] as $dim) {
                $total += $dim['promedio'];
            }
            $data['promedio_global'] = round($total / count($data['dimensiones']));
        }
    } else {
        die("Candidato no encontrado");
    }
}

// Función para generar HTML para el PDF
function generateHTMLContent($data, $export_mode) {
    ob_start();
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php echo $export_mode === 'session' ? 'Resultados de Prueba' : 'Informe Completo'; ?></title>
        <style>
            body {
                font-family: Arial, sans-serif;
                font-size: 12pt;
                line-height: 1.5;
                color: #333;
                margin: 20px;
            }
            h1, h2, h3 {
                color: #2c3e50;
            }
            .header {
                text-align: center;
                margin-bottom: 30px;
                border-bottom: 2px solid #3498db;
                padding-bottom: 10px;
            }
            .section {
                margin-bottom: 30px;
            }
            .info-box {
                background-color: #f8f9fa;
                border: 1px solid #ddd;
                padding: 15px;
                margin-bottom: 20px;
                border-radius: 5px;
            }
            table {
                width: 100%;
                border-collapse: collapse;
                margin-bottom: 20px;
            }
            table, th, td {
                border: 1px solid #ddd;
            }
            th, td {
                padding: 8px;
                text-align: left;
            }
            th {
                background-color: #f2f2f2;
            }
            .progress-container {
                width: 100%;
                background-color: #f1f1f1;
                border-radius: 5px;
                margin-bottom: 5px;
            }
            .progress-bar {
                height: 20px;
                border-radius: 5px;
            }
            .footer {
                margin-top: 30px;
                text-align: center;
                font-size: 10pt;
                color: #777;
                border-top: 1px solid #ddd;
                padding-top: 10px;
            }
        </style>
    </head>
    <body>
        <div class="header">
            <h1><?php echo $export_mode === 'session' ? 'Resultados de Evaluación' : 'Informe Completo de Evaluación'; ?></h1>
            <p>Generado el: <?php echo date('d/m/Y H:i'); ?></p>
        </div>
        
        <div class="section">
            <h2>Información del Candidato</h2>
            <div class="info-box">
                <?php if ($export_mode === 'session'): ?>
                <p><strong>Nombre:</strong> <?php echo htmlspecialchars($data['prueba']['candidato_nombre'] . ' ' . $data['prueba']['candidato_apellido']); ?></p>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($data['prueba']['candidato_email']); ?></p>
                <?php else: ?>
                <p><strong>Nombre:</strong> <?php echo htmlspecialchars($data['candidato']['nombre'] . ' ' . $data['candidato']['apellido']); ?></p>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($data['candidato']['email']); ?></p>
                <p><strong>Teléfono:</strong> <?php echo htmlspecialchars($data['candidato']['telefono'] ?? 'No especificado'); ?></p>
                <p><strong>Experiencia:</strong> 
                    <?php 
                    $experiencia = '';
                    switch ($data['candidato']['experiencia_general'] ?? '') {
                        case 'sin-experiencia': $experiencia = 'Sin experiencia'; break;
                        case 'menos-1': $experiencia = 'Menos de 1 año'; break;
                        case '1-3': $experiencia = '1-3 años'; break;
                        case '3-5': $experiencia = '3-5 años'; break;
                        case '5-10': $experiencia = '5-10 años'; break;
                        case 'mas-10': $experiencia = 'Más de 10 años'; break;
                        default: $experiencia = 'No especificada';
                    }
                    echo $experiencia;
                    ?>
                </p>
                <?php endif; ?>
            </div>
        </div>
        
        <?php if ($export_mode === 'session'): ?>
        <div class="section">
            <h2>Detalles de la Prueba</h2>
            <div class="info-box">
                <p><strong>Título:</strong> <?php echo htmlspecialchars($data['prueba']['prueba_titulo']); ?></p>
                <p><strong>Descripción:</strong> <?php echo htmlspecialchars($data['prueba']['prueba_descripcion'] ?? 'No disponible'); ?></p>
                <p><strong>Fecha de realización:</strong> <?php echo date('d/m/Y H:i', strtotime($data['prueba']['fecha_fin'])); ?></p>
            </div>
        </div>
        <?php else: ?>
        <div class="section">
            <h2>Evaluaciones Realizadas</h2>
            <table>
                <thead>
                    <tr>
                        <th>Prueba</th>
                        <th>Fecha</th>
                        <th>Duración</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data['pruebas'] as $prueba): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($prueba['prueba_titulo']); ?></td>
                        <td><?php echo date('d/m/Y', strtotime($prueba['fecha_fin'])); ?></td>
                        <td>
                            <?php 
                            $inicio = new DateTime($prueba['fecha_inicio']);
                            $fin = new DateTime($prueba['fecha_fin']);
                            $duracion = $inicio->diff($fin);
                            echo $duracion->format('%H:%I:%S'); 
                            ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
        
        <div class="section">
            <h2>Resultados Globales</h2>
            <div class="info-box">
                <p><strong>Puntuación Global:</strong> <?php echo $data['promedio_global']; ?>%</p>
                <p><strong>Nivel:</strong> 
                    <?php 
                    $nivel = '';
                    if ($data['promedio_global'] >= 90) $nivel = 'Excepcional';
                    else if ($data['promedio_global'] >= 80) $nivel = 'Sobresaliente';
                    else if ($data['promedio_global'] >= 70) $nivel = 'Notable';
                    else if ($data['promedio_global'] >= 60) $nivel = 'Adecuado';
                    else if ($data['promedio_global'] >= 50) $nivel = 'Moderado';
                    else if ($data['promedio_global'] >= 35) $nivel = 'En desarrollo';
                    else $nivel = 'Incipiente';
                    echo $nivel;
                    ?>
                </p>
                
                <div class="progress-container">
                    <div class="progress-bar" style="width: <?php echo $data['promedio_global']; ?>%; background-color: 
                        <?php 
                        if ($data['promedio_global'] >= 80) echo '#2ecc71';
                        else if ($data['promedio_global'] >= 60) echo '#3498db';
                        else if ($data['promedio_global'] >= 40) echo '#f39c12';
                        else echo '#e74c3c';
                        ?>;">
                    </div>
                </div>
            </div>
        </div>
        
        <div class="section">
            <h2>Resultados por Dimensión</h2>
            <table>
                <thead>
                    <tr>
                        <th>Dimensión</th>
                        <th>Categoría</th>
                        <th>Puntaje</th>
                        <th>Nivel</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data['dimensiones'] as $dimension): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($dimension['nombre']); ?></td>
                        <td><?php echo ucfirst(htmlspecialchars($dimension['categoria'] ?? 'General')); ?></td>
                        <td><?php echo round($dimension['promedio']); ?>%</td>
                        <td><?php echo $dimension['nivel']; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <div class="section">
            <h2>Conclusiones y Recomendaciones</h2>
            <div class="info-box">
                <h3>Perfil del Candidato</h3>
                <p>
                    <?php
                    // Generar conclusiones basadas en los resultados
                    echo "El candidato muestra un nivel " . strtolower($nivel) . " en esta evaluación. ";
                    
                    // Identificar fortalezas (dimensiones con promedio >= 75)
                    $fortalezas = array_filter($data['dimensiones'], function($dim) {
                        return $dim['promedio'] >= 75;
                    });
                    
                    if (!empty($fortalezas)) {
                        echo "Sus principales fortalezas se encuentran en ";
                        $nombres = array_map(function($dim) {
                            return strtolower($dim['nombre']);
                        }, array_slice($fortalezas, 0, 3));
                        
                        echo implode(', ', array_slice($nombres, 0, -1));
                        if (count($nombres) > 1) {
                            echo " y " . end($nombres);
                        } else if (count($nombres) == 1) {
                            echo $nombres[0];
                        }
                        echo ". ";
                    }
                    
                    // Identificar áreas de mejora (dimensiones con promedio < 60)
                    $areas_mejora = array_filter($data['dimensiones'], function($dim) {
                        return $dim['promedio'] < 60;
                    });
                    
                    if (!empty($areas_mejora)) {
                        echo "Las áreas que presentan oportunidad de desarrollo son ";
                        $nombres = array_map(function($dim) {
                            return strtolower($dim['nombre']);
                        }, array_slice($areas_mejora, 0, 3));
                        
                        echo implode(', ', array_slice($nombres, 0, -1));
                        if (count($nombres) > 1) {
                            echo " y " . end($nombres);
                        } else if (count($nombres) == 1) {
                            echo $nombres[0];
                        }
                        echo ".";
                    }
                    ?>
                </p>
                
                <h3>Recomendaciones</h3>
                <ul>
                    <?php if ($data['promedio_global'] >= 75): ?>
                    <li>Considerar al candidato para posiciones que requieran alto nivel de desempeño en <?php echo !empty($fortalezas) ? strtolower(reset($fortalezas)['nombre']) : 'su área de especialidad'; ?>.</li>
                    <li>Aprovechar sus fortalezas asignándole proyectos donde pueda aplicar sus capacidades destacadas.</li>
                    <li>Ofrecer oportunidades de desarrollo en roles de liderazgo o mentoring en sus áreas de expertise.</li>
                    <?php elseif ($data['promedio_global'] >= 60): ?>
                    <li>El candidato muestra un perfil adecuado para posiciones que requieran las competencias evaluadas.</li>
                    <li>Complementar con entrevistas enfocadas en las áreas de mejora identificadas.</li>
                    <li>Considerar un plan de desarrollo específico para potenciar áreas con oportunidad de mejora.</li>
                    <?php else: ?>
                    <li>Realizar evaluaciones adicionales para complementar estos resultados.</li>
                    <li>Considerar programas de formación específicos antes de asignar responsabilidades en las áreas con menor puntuación.</li>
                    <li>Evaluar la adecuación del candidato para posiciones que requieran menor énfasis en las áreas con puntuación baja.</li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
        
        <div class="footer">
            <p>Este documento ha sido generado automáticamente por el Sistema de Evaluación de SolFis.</p>
            <p>Confidencial - Para uso interno.</p>
        </div>
    </body>
    </html>
    <?php
    return ob_get_clean();
}

// Generar el contenido HTML
$html_content = generateHTMLContent($data, $export_mode);

// En una implementación real, aquí se convertiría el HTML a PDF
// Para este ejemplo, simplemente mostramos el HTML para descargar

// Configurar las cabeceras para "descargar" como HTML (simulando PDF)
header('Content-Type: text/html; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $pdf_filename . '.html"');
header('Cache-Control: max-age=0');

// Imprimir el contenido HTML
echo $html_content;
exit;