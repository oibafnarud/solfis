<?php
/**
 * Panel de Administración para SolFis
 * admin/candidatos/exportar-pdf.php - Exportar resultados del candidato a PDF
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

// Verificar que se proporciona un ID de candidato
if (!isset($_GET['candidato_id']) || empty($_GET['candidato_id'])) {
    $_SESSION['error'] = "ID de candidato no proporcionado";
    header('Location: index.php');
    exit;
}

$candidato_id = (int)$_GET['candidato_id'];

// Obtener datos del candidato
$db = Database::getInstance();
$candidato_id = $db->real_escape_string($candidato_id);

$sql = "SELECT * FROM candidatos WHERE id = '$candidato_id'";
$result = $db->query($sql);

if (!$result || $result->num_rows === 0) {
    $_SESSION['error'] = "Candidato no encontrado";
    header('Location: index.php');
    exit;
}

$candidato = $result->fetch_assoc();

// Obtener pruebas completadas
$sqlPruebas = "SELECT sp.*, p.titulo as prueba_titulo, p.descripcion as prueba_descripcion
               FROM sesiones_prueba sp
               JOIN pruebas p ON sp.prueba_id = p.id
               WHERE sp.candidato_id = '$candidato_id' AND sp.estado = 'completada'
               ORDER BY sp.fecha_fin DESC";

$resultPruebas = $db->query($sqlPruebas);
$pruebas = [];

if ($resultPruebas && $resultPruebas->num_rows > 0) {
    while ($row = $resultPruebas->fetch_assoc()) {
        $pruebas[] = $row;
    }
}

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
                        END as nivel,
                        CASE 
                            WHEN AVG(r.valor) >= 90 THEN 'success' 
                            WHEN AVG(r.valor) >= 80 THEN 'success'
                            WHEN AVG(r.valor) >= 70 THEN 'info'
                            WHEN AVG(r.valor) >= 60 THEN 'primary' 
                            WHEN AVG(r.valor) >= 50 THEN 'warning'
                            WHEN AVG(r.valor) >= 35 THEN 'warning'
                            ELSE 'danger' 
                        END as clase_nivel
                 FROM resultados r
                 JOIN dimensiones d ON r.dimension_id = d.id
                 JOIN sesiones_prueba s ON r.sesion_id = s.id
                 WHERE s.candidato_id = '$candidato_id' AND s.estado = 'completada'
                 GROUP BY d.id
                 ORDER BY promedio DESC";

$resultDimensiones = $db->query($sqlDimensiones);
$dimensiones = [];

if ($resultDimensiones && $resultDimensiones->num_rows > 0) {
    while ($row = $resultDimensiones->fetch_assoc()) {
        $dimensiones[] = $row;
    }
}

// Calcular promedio global
$promedioGlobal = 0;
$totalDimensiones = count($dimensiones);

if ($totalDimensiones > 0) {
    $sumaPromedios = 0;
    foreach ($dimensiones as $dimension) {
        $sumaPromedios += $dimension['promedio'];
    }
$promedioGlobal = round($sumaPromedios / $totalDimensiones);
}

// Obtener índices compuestos
function calcularIndiceCompuesto($indice_id, $candidato_id) {
    $db = Database::getInstance();
    $indice_id = (int)$indice_id;
    $candidato_id = (int)$candidato_id;
    
    // Obtener componentes del índice
    $sql = "SELECT ic.origen_tipo, ic.origen_id, ic.ponderacion 
            FROM indices_componentes ic 
            WHERE ic.indice_id = $indice_id 
            ORDER BY ic.id";
    
    $result = $db->query($sql);
    
    if (!$result || $result->num_rows === 0) {
        // Si no hay componentes definidos, retornar valor por defecto
        return 0;
    }
    
    $totalValor = 0;
    $totalPonderacion = 0;
    
    while ($componente = $result->fetch_assoc()) {
        $valor_componente = 0;
        
        if ($componente['origen_tipo'] === 'dimension') {
            // Si es una dimensión, obtener el resultado de evaluación
            $dimension_id = (int)$componente['origen_id'];
            $sql_valor = "SELECT AVG(r.valor) as promedio 
                          FROM resultados r 
                          JOIN sesiones_prueba s ON r.sesion_id = s.id 
                          WHERE r.dimension_id = $dimension_id 
                          AND s.candidato_id = $candidato_id 
                          AND s.estado = 'completada'";
            
            $result_valor = $db->query($sql_valor);
            
            if ($result_valor && $result_valor->num_rows > 0) {
                $row_valor = $result_valor->fetch_assoc();
                $valor_componente = !is_null($row_valor['promedio']) ? floatval($row_valor['promedio']) : 0;
            }
        } else if ($componente['origen_tipo'] === 'indice') {
            // Si es otro índice, llamada recursiva
            $indice_origen_id = (int)$componente['origen_id'];
            $valor_componente = calcularIndiceCompuesto($indice_origen_id, $candidato_id);
        }
        
        $ponderacion = floatval($componente['ponderacion']);
        $totalValor += $valor_componente * $ponderacion;
        $totalPonderacion += $ponderacion;
    }
    
    // Si no hay ponderación total (error en datos), retornar 0
    if ($totalPonderacion == 0) {
        return 0;
    }
    
    // Calcular promedio ponderado y redondear
    return round($totalValor / $totalPonderacion);
}

function getIndicesCompuestos($candidato_id) {
    $db = Database::getInstance();
    $candidato_id = (int)$candidato_id;
    
    // Consulta para obtener los índices compuestos principales
    $sql = "SELECT id, nombre, descripcion FROM indices_compuestos 
            WHERE id IN (SELECT MIN(id) FROM indices_compuestos GROUP BY nombre) 
            ORDER BY id";
    
    $result = $db->query($sql);
    $indices = [];
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            // Por cada índice, calculamos su valor basado en las dimensiones evaluadas
            $indice_id = $row['id'];
            $valor = calcularIndiceCompuesto($indice_id, $candidato_id);
            
            // Determinar nivel según el valor
            $nivel = '';
            $class = '';
            
            if ($valor >= 90) {
                $nivel = 'Excepcional';
                $class = 'success';
            } else if ($valor >= 80) {
                $nivel = 'Sobresaliente';
                $class = 'success';
            } else if ($valor >= 70) {
                $nivel = 'Notable';
                $class = 'info';
            } else if ($valor >= 60) {
                $nivel = 'Adecuado';
                $class = 'primary';
            } else if ($valor >= 50) {
                $nivel = 'Moderado';
                $class = 'warning';
            } else if ($valor >= 35) {
                $nivel = 'En desarrollo';
                $class = 'warning';
            } else {
                $nivel = 'Incipiente';
                $class = 'danger';
            }
            
            $indices[] = [
                'id' => $indice_id,
                'nombre' => $row['nombre'],
                'descripcion' => $row['descripcion'],
                'valor' => $valor,
                'nivel' => $nivel,
                'class' => $class
            ];
        }
    }
    
    return $indices;
}

// Obtener índices compuestos
$indicesCompuestos = getIndicesCompuestos($candidato_id);

// Identificar fortalezas y áreas de mejora
$fortalezas = [];
$areasDesarrollo = [];

foreach ($dimensiones as $dimension) {
    if ($dimension['promedio'] >= 75) {
        $fortalezas[] = $dimension;
    } else if ($dimension['promedio'] < 60) {
        $areasDesarrollo[] = $dimension;
    }
}

// Limitar a las principales
$fortalezasPrincipales = array_slice($fortalezas, 0, 5);
$areasPrincipales = array_slice($areasDesarrollo, 0, 5);

// Título del PDF
$candidato_nombre = $candidato['nombre'] . ' ' . $candidato['apellido'];
$pdfTitle = "Informe de Evaluación - $candidato_nombre";
$pdfFilename = "informe_$candidato_id.pdf";

// Generar contenido HTML para el PDF
function generateHTMLContent($candidato, $pruebas, $dimensiones, $promedioGlobal, $indicesCompuestos, $fortalezasPrincipales, $areasPrincipales) {
    ob_start();
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Informe de Evaluación</title>
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
            .page-break {
                page-break-before: always;
            }
            .cols {
                display: table;
                width: 100%;
            }
            .col {
                display: table-cell;
                padding: 10px;
            }
            .text-success { color: #28a745; }
            .text-danger { color: #dc3545; }
            .text-warning { color: #ffc107; }
            .text-info { color: #17a2b8; }
        </style>
    </head>
    <body>
        <div class="header">
            <h1>Informe de Evaluación Psicométrica</h1>
            <p>Generado el: <?php echo date('d/m/Y H:i'); ?></p>
        </div>
        
        <div class="section">
            <h2>Información del Candidato</h2>
            <div class="info-box">
                <div class="cols">
                    <div class="col">
                        <p><strong>Nombre:</strong> <?php echo htmlspecialchars($candidato['nombre'] . ' ' . $candidato['apellido']); ?></p>
                        <p><strong>Email:</strong> <?php echo htmlspecialchars($candidato['email']); ?></p>
                        <p><strong>Teléfono:</strong> <?php echo htmlspecialchars($candidato['telefono'] ?? 'No especificado'); ?></p>
                    </div>
                    <div class="col">
                        <p><strong>Nivel educativo:</strong> 
                            <?php 
                            $educacion = '';
                            switch ($candidato['nivel_educativo'] ?? '') {
                                case 'bachiller': $educacion = 'Bachiller'; break;
                                case 'tecnico': $educacion = 'Técnico'; break;
                                case 'grado': $educacion = 'Grado Universitario'; break;
                                case 'postgrado': $educacion = 'Postgrado'; break;
                                case 'maestria': $educacion = 'Maestría'; break;
                                case 'doctorado': $educacion = 'Doctorado'; break;
                                default: $educacion = 'No especificado';
                            }
                            echo $educacion;
                            ?>
                        </p>
                        <p><strong>Experiencia:</strong> 
                            <?php 
                            $experiencia = '';
                            switch ($candidato['experiencia_general'] ?? '') {
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
                        <p><strong>Fecha del informe:</strong> <?php echo date('d/m/Y'); ?></p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="section">
            <h2>Resumen de Resultados</h2>
            <div class="info-box">
                <h3>Puntuación Global: <?php echo $promedioGlobal; ?>%</h3>
                <p><strong>Nivel:</strong> 
                    <?php 
                    $nivel = '';
                    if ($promedioGlobal >= 90) $nivel = 'Excepcional';
                    else if ($promedioGlobal >= 80) $nivel = 'Sobresaliente';
                    else if ($promedioGlobal >= 70) $nivel = 'Notable';
                    else if ($promedioGlobal >= 60) $nivel = 'Adecuado';
                    else if ($promedioGlobal >= 50) $nivel = 'Moderado';
                    else if ($promedioGlobal >= 35) $nivel = 'En desarrollo';
                    else $nivel = 'Incipiente';
                    echo $nivel;
                    ?>
                </p>
                
                <div class="progress-container">
                    <div class="progress-bar" style="width: <?php echo $promedioGlobal; ?>%; background-color: 
                        <?php 
                        if ($promedioGlobal >= 80) echo '#28a745';
                        else if ($promedioGlobal >= 60) echo '#17a2b8';
                        else if ($promedioGlobal >= 40) echo '#ffc107';
                        else echo '#dc3545';
                        ?>;">
                    </div>
                </div>
                
                <p>
                    <?php 
                    // Generar descripción general basada en el nivel
                    switch ($nivel) {
                        case 'Excepcional':
                            echo 'El candidato muestra un desempeño sobresaliente en las evaluaciones realizadas, superando ampliamente los estándares esperados en la mayoría de las dimensiones evaluadas.';
                            break;
                        case 'Sobresaliente':
                            echo 'El candidato presenta un perfil muy sólido con resultados por encima del promedio en la mayoría de las dimensiones evaluadas.';
                            break;
                        case 'Notable':
                            echo 'El candidato muestra un buen desempeño general, con resultados superiores al promedio en varias dimensiones clave.';
                            break;
                        case 'Adecuado':
                            echo 'El candidato presenta un perfil satisfactorio que cumple con los requisitos básicos en la mayoría de las dimensiones evaluadas.';
                            break;
                        case 'Moderado':
                            echo 'El candidato muestra un perfil aceptable pero con oportunidades de desarrollo en varias áreas evaluadas.';
                            break;
                        case 'En desarrollo':
                            echo 'El candidato presenta un perfil con desarrollo parcial de las competencias evaluadas, requiriendo formación adicional en múltiples áreas.';
                            break;
                        case 'Incipiente':
                            echo 'El candidato muestra un desarrollo limitado en la mayoría de las dimensiones evaluadas, requiriendo formación intensiva y desarrollo antes de asumir responsabilidades en estas áreas.';
                            break;
                    }
                    ?>
                </p>
            </div>
        </div>
        
        <div class="section">
            <h2>Dimensiones Evaluadas</h2>
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
                    <?php foreach ($dimensiones as $dimension): ?>
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
            <h2>Índices Compuestos</h2>
            <?php if (!empty($indicesCompuestos)): ?>
            <table>
                <thead>
                    <tr>
                        <th>Índice</th>
                        <th>Descripción</th>
                        <th>Puntaje</th>
                        <th>Nivel</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($indicesCompuestos as $indice): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($indice['nombre']); ?></td>
                        <td><?php echo htmlspecialchars($indice['descripcion']); ?></td>
                        <td><?php echo $indice['valor']; ?>%</td>
                        <td><?php echo $indice['nivel']; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <p>No hay suficientes datos para calcular índices compuestos.</p>
            <?php endif; ?>
        </div>
        
        <div class="page-break"></div>
        
        <div class="section">
            <h2>Fortalezas y Áreas de Desarrollo</h2>
            <div class="info-box">
                <div class="cols">
                    <div class="col">
                        <h3 class="text-success">Fortalezas</h3>
                        <?php if (!empty($fortalezasPrincipales)): ?>
                        <ul>
                            <?php foreach ($fortalezasPrincipales as $fortaleza): ?>
                            <li>
                                <strong><?php echo htmlspecialchars($fortaleza['nombre']); ?></strong> 
                                (<?php echo round($fortaleza['promedio']); ?>%) - 
                                <?php echo $fortaleza['nivel']; ?>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                        <?php else: ?>
                        <p>No se identificaron fortalezas destacadas en esta evaluación.</p>
                        <?php endif; ?>
                    </div>
                    <div class="col">
                        <h3 class="text-warning">Áreas de Desarrollo</h3>
                        <?php if (!empty($areasPrincipales)): ?>
                        <ul>
                            <?php foreach ($areasPrincipales as $area): ?>
                            <li>
                                <strong><?php echo htmlspecialchars($area['nombre']); ?></strong> 
                                (<?php echo round($area['promedio']); ?>%) - 
                                <?php echo $area['nivel']; ?>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                        <?php else: ?>
                        <p>No se identificaron áreas que requieran desarrollo prioritario.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="section">
            <h2>Conclusiones y Recomendaciones</h2>
            <div class="info-box">
                <h3>Perfil del Candidato</h3>
                <p>
                    <?php
                    // Generar conclusiones basadas en los resultados
                    echo "El candidato muestra un nivel " . strtolower($nivel) . " en esta evaluación. ";
                    
                    if (!empty($fortalezasPrincipales)) {
                        echo "Sus principales fortalezas se encuentran en ";
                        $nombres = array_map(function($dim) { 
                            return strtolower($dim['nombre']); 
                        }, array_slice($fortalezasPrincipales, 0, -1));
                        
                        echo implode(', ', array_slice($nombres, 0, -1));
                        if (count($nombres) > 1) {
                            echo " y " . end($nombres);
                        } else if (count($nombres) == 1) {
                            echo $nombres[0];
                        }
                        echo ". ";
                    }
                    
                    if (!empty($areasPrincipales)) {
                        echo "Las áreas que presentan oportunidad de desarrollo son ";
                        $nombres = array_map(function($dim) { 
                            return strtolower($dim['nombre']); 
                        }, array_slice($areasPrincipales, 0, -1));
                        
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
                    <?php if ($promedioGlobal >= 75): ?>
                    <li>Considerar al candidato para posiciones que requieran alto nivel de desempeño en <?php echo !empty($fortalezasPrincipales) ? strtolower($fortalezasPrincipales[0]['nombre']) : 'su área de especialidad'; ?>.</li>
                    <li>Aprovechar sus fortalezas asignándole proyectos donde pueda aplicar sus capacidades destacadas.</li>
                    <li>Ofrecer oportunidades de desarrollo en roles de liderazgo o mentoring en sus áreas de expertise.</li>
                    <?php elseif ($promedioGlobal >= 60): ?>
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
$html_content = generateHTMLContent($candidato, $pruebas, $dimensiones, $promedioGlobal, $indicesCompuestos, $fortalezasPrincipales, $areasPrincipales);

// En una implementación real, aquí se convertiría el HTML a PDF usando una biblioteca como TCPDF, FPDF o Dompdf
// Para este ejemplo, simplemente mostramos el HTML para "simular" el PDF

// Configurar las cabeceras para "descargar" como HTML (simulando PDF)
header('Content-Type: text/html; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $pdfFilename . '.html"');
header('Cache-Control: max-age=0');

// Imprimir el contenido HTML
echo $html_content;
exit;