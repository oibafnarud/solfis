<?php
/**
 * Panel de Administración para SolFis
 * admin/candidatos/generar-informe.php - Genera PDF con informe del candidato
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
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error'] = "ID de candidato no proporcionado";
    header('Location: index.php');
    exit;
}

$candidato_id = (int)$_GET['id'];

// Instanciar gestores
$candidateManager = new CandidateManager();
$testManager = null;
$hasTestManager = false;
$pruebasCompletadas = [];
$evaluationResults = [];
$dimensiones = [];
$resultados = [];

// Obtener datos del candidato
$candidato = $candidateManager->getCandidateById($candidato_id);

if (!$candidato) {
    $_SESSION['error'] = "Candidato no encontrado";
    header('Location: index.php');
    exit;
}

// Verificar si existe el TestManager para obtener resultados de pruebas
if (file_exists('../../includes/TestManager.php')) {
    require_once '../../includes/TestManager.php';
    if (class_exists('TestManager')) {
        $testManager = new TestManager();
        $hasTestManager = true;
        
        // Obtener pruebas completadas por el candidato
        try {
            $pruebasCompletadas = $testManager->getCompletedTests($candidato_id);
            
            // Obtener resultados por dimensiones
            $db = Database::getInstance();
            
            $dimensionsQuery = "SELECT d.id, d.nombre, AVG(r.valor) as promedio, 
                                CASE 
                                    WHEN AVG(r.valor) >= 90 THEN 'Excepcional' 
                                    WHEN AVG(r.valor) >= 80 THEN 'Sobresaliente'
                                    WHEN AVG(r.valor) >= 70 THEN 'Notable'
                                    WHEN AVG(r.valor) >= 60 THEN 'Adecuado' 
                                    WHEN AVG(r.valor) >= 50 THEN 'Moderado'
                                    WHEN AVG(r.valor) >= 35 THEN 'En desarrollo'
                                    ELSE 'Incipiente' 
                                END as nivel,
                                d.categoria
                                FROM resultados r
                                JOIN dimensiones d ON r.dimension_id = d.id
                                JOIN sesiones_prueba s ON r.sesion_id = s.id
                                WHERE s.candidato_id = $candidato_id AND s.estado = 'completada'
                                GROUP BY d.id
                                ORDER BY promedio DESC";
            
            $result = $db->query($dimensionsQuery);
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $evaluationResults[] = $row;
                    
                    // Clasificar dimensiones por categoría
                    if (!isset($dimensiones[$row['categoria']])) {
                        $dimensiones[$row['categoria']] = [];
                    }
                    $dimensiones[$row['categoria']][] = $row;
                }
            }
            
            // Obtener todos los resultados individuales
            $resultadosQuery = "SELECT r.*, d.nombre as dimension_nombre, d.categoria
                               FROM resultados r
                               JOIN dimensiones d ON r.dimension_id = d.id
                               JOIN sesiones_prueba s ON r.sesion_id = s.id
                               WHERE s.candidato_id = $candidato_id AND s.estado = 'completada'
                               ORDER BY r.fecha_registro DESC";
            
            $result = $db->query($resultadosQuery);
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $resultados[] = $row;
                }
            }
        } catch (Exception $e) {
            // Si hay error, registrarlo pero continuar
            error_log("Error al obtener resultados de pruebas: " . $e->getMessage());
        }
    }
}

// Calcular promedio de resultados
$promedioResultados = 0;
$countResultados = 0;

if (!empty($evaluationResults)) {
    $totalPromedio = 0;
    foreach ($evaluationResults as $result) {
        $totalPromedio += $result['promedio'];
        $countResultados++;
    }
    
    if ($countResultados > 0) {
        $promedioResultados = round($totalPromedio / $countResultados);
    }
}

// Nivel de evaluación global
function getNivelEvaluacion($valor) {
    if ($valor >= 90) return ['texto' => 'Excepcional', 'descripcion' => 'Desempeño sobresaliente, muy por encima de la media', 'color' => '#006400', 'class' => 'success'];
    else if ($valor >= 80) return ['texto' => 'Sobresaliente', 'descripcion' => 'Desempeño destacado, por encima de la media', 'color' => '#008000', 'class' => 'success'];
    else if ($valor >= 70) return ['texto' => 'Notable', 'descripcion' => 'Buen desempeño, superior a la media', 'color' => '#90EE90', 'class' => 'info'];
    else if ($valor >= 60) return ['texto' => 'Adecuado', 'descripcion' => 'Desempeño satisfactorio, cumple con lo esperado', 'color' => '#FFFF00', 'class' => 'primary'];
    else if ($valor >= 50) return ['texto' => 'Moderado', 'descripcion' => 'Desempeño aceptable, en el promedio esperado', 'color' => '#FFFFE0', 'class' => 'warning'];
    else if ($valor >= 35) return ['texto' => 'En desarrollo', 'descripcion' => 'Desempeño por debajo del promedio, necesita desarrollo', 'color' => '#FFA500', 'class' => 'warning'];
    else return ['texto' => 'Incipiente', 'descripcion' => 'Desempeño significativamente bajo, requiere atención especial', 'color' => '#FF0000', 'class' => 'danger'];
}

$nivelEvaluacion = getNivelEvaluacion($promedioResultados);

// Obtener fortalezas y debilidades
$fortalezas = [];
$debilidades = [];

foreach ($evaluationResults as $result) {
    if ($result['promedio'] >= 75) {
        $fortalezas[] = $result['nombre'] . ' (' . round($result['promedio']) . '%)';
    } else if ($result['promedio'] < 60) {
        $debilidades[] = $result['nombre'] . ' (' . round($result['promedio']) . '%)';
    }
}

// Limitar a las 5 principales fortalezas y debilidades
$fortalezas = array_slice($fortalezas, 0, 5);
$debilidades = array_slice($debilidades, 0, 5);

// Obtener fecha actual para registro
$currentDate = date('Y-m-d H:i:s');

// Obtener usuario que genera el informe
$usuario = $auth->getCurrentUser();
$usuario_id = $usuario['id'];
$usuario_nombre = $usuario['username'];

// Registrar generación del informe en la base de datos (si existiera tal tabla)
try {
    $db = Database::getInstance();
    $query = "INSERT INTO informes_generados (candidato_id, usuario_id, tipo, fecha_generacion) 
              VALUES ($candidato_id, $usuario_id, 'pdf', '$currentDate')";
    $db->query($query);
    $informe_id = $db->insert_id;
} catch (Exception $e) {
    // Si la tabla no existe o hay otro error, continuamos sin registrar
    error_log("Error al registrar generación de informe: " . $e->getMessage());
}

// Obtener experiencia laboral actual del candidato
$experiencia = null;
try {
    $db = Database::getInstance();
    $query = "SELECT * FROM experiencia_laboral 
              WHERE candidato_id = $candidato_id
              ORDER BY fecha_fin DESC, fecha_inicio DESC
              LIMIT 1";
    $result = $db->query($query);
    if ($result && $result->num_rows > 0) {
        $experiencia = $result->fetch_assoc();
    }
} catch (Exception $e) {
    // Si la tabla no existe o hay otro error, continuamos sin la información
    error_log("Error al obtener experiencia laboral: " . $e->getMessage());
}

// Obtener estudios/educación del candidato
$educacion = null;
try {
    $db = Database::getInstance();
    $query = "SELECT * FROM educacion 
              WHERE candidato_id = $candidato_id
              ORDER BY fecha_fin DESC, fecha_inicio DESC
              LIMIT 1";
    $result = $db->query($query);
    if ($result && $result->num_rows > 0) {
        $educacion = $result->fetch_assoc();
    }
} catch (Exception $e) {
    // Si la tabla no existe o hay otro error, continuamos sin la información
    error_log("Error al obtener información educativa: " . $e->getMessage());
}

// Cargar la librería TCPDF para generar el PDF
require_once '../../vendor/autoload.php';

// Crear nueva instancia de TCPDF
class MYPDF extends TCPDF {
    public function Header() {
        // Logo
        $image_file = K_PATH_IMAGES.'logo.png';
        $this->Image($image_file, 15, 10, 40, '', 'PNG', '', 'T', false, 300, '', false, false, 0, false, false, false);
        // Set font
        $this->SetFont('helvetica', 'B', 20);
        // Title
        $this->Cell(0, 15, 'INFORME DE EVALUACIÓN', 0, false, 'C', 0, '', 0, false, 'M', 'M');
    }

    public function Footer() {
        // Position at 15 mm from bottom
        $this->SetY(-15);
        // Set font
        $this->SetFont('helvetica', 'I', 8);
        // Page number and date
        $this->Cell(0, 10, 'Página '.$this->getAliasNumPage().'/'.$this->getAliasNbPages().'     Generado el: '.date('d/m/Y H:i'), 0, false, 'C', 0, '', 0, false, 'T', 'M');
    }
}

// Crear objeto PDF
$pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// Configurar la información del documento
$pdf->SetCreator('SolFis Consultores');
$pdf->SetAuthor('SolFis RRHH');
$pdf->SetTitle('Informe de Evaluación - ' . $candidato['nombre'] . ' ' . $candidato['apellido']);
$pdf->SetSubject('Evaluación de Candidato');
$pdf->SetKeywords('candidato, evaluación, informe, resultados');

// Configurar márgenes
$pdf->SetMargins(15, 15, 15);
$pdf->SetHeaderMargin(5);
$pdf->SetFooterMargin(10);

// Configurar saltos de página automáticos
$pdf->SetAutoPageBreak(TRUE, 15);

// Configurar fuente predeterminada
$pdf->SetFont('helvetica', '', 11);

// Añadir página
$pdf->AddPage();

// Generar contenido del PDF

// Información del candidato
$pdf->SetFont('helvetica', 'B', 14);
$pdf->Cell(0, 10, 'Información del Candidato', 0, 1, 'L');
$pdf->SetFont('helvetica', '', 11);

$pdf->Cell(40, 7, 'Nombre completo:', 0, 0, 'L');
$pdf->Cell(0, 7, $candidato['nombre'] . ' ' . $candidato['apellido'], 0, 1, 'L');

$pdf->Cell(40, 7, 'Email:', 0, 0, 'L');
$pdf->Cell(0, 7, $candidato['email'], 0, 1, 'L');

if (!empty($candidato['telefono'])) {
    $pdf->Cell(40, 7, 'Teléfono:', 0, 0, 'L');
    $pdf->Cell(0, 7, $candidato['telefono'], 0, 1, 'L');
}

if (!empty($candidato['ubicacion'])) {
    $pdf->Cell(40, 7, 'Ubicación:', 0, 0, 'L');
    $pdf->Cell(0, 7, $candidato['ubicacion'], 0, 1, 'L');
}

// Mostrar experiencia y educación si están disponibles
if ($experiencia) {
    $pdf->Cell(40, 7, 'Experiencia actual:', 0, 0, 'L');
    $expText = $experiencia['cargo'] . ' en ' . $experiencia['empresa'];
    if (!empty($experiencia['fecha_inicio'])) {
        $expText .= ' (desde ' . date('m/Y', strtotime($experiencia['fecha_inicio'])) . ')';
    }
    $pdf->Cell(0, 7, $expText, 0, 1, 'L');
}

if (!empty($candidato['experiencia_general'])) {
    $pdf->Cell(40, 7, 'Experiencia general:', 0, 0, 'L');
    $expGeneral = '';
    switch ($candidato['experiencia_general']) {
        case 'sin-experiencia': $expGeneral = 'Sin experiencia'; break;
        case 'menos-1': $expGeneral = 'Menos de 1 año'; break;
        case '1-3': $expGeneral = '1-3 años'; break;
        case '3-5': $expGeneral = '3-5 años'; break;
        case '5-10': $expGeneral = '5-10 años'; break;
        case 'mas-10': $expGeneral = 'Más de 10 años'; break;
    }
    $pdf->Cell(0, 7, $expGeneral, 0, 1, 'L');
}

// Añadir línea separadora
$pdf->Ln(5);
$pdf->Line(15, $pdf->GetY(), 195, $pdf->GetY());
$pdf->Ln(5);

// Resumen ejecutivo
$pdf->SetFont('helvetica', 'B', 14);
$pdf->Cell(0, 10, 'Resumen Ejecutivo', 0, 1, 'L');
$pdf->SetFont('helvetica', '', 11);

// Generar texto de resumen
$resumenText = "El candidato {$candidato['nombre']} {$candidato['apellido']} ha completado ";
$resumenText .= count($pruebasCompletadas) . " pruebas de evaluación, mostrando un perfil ";
$resumenText .= strtolower($nivelEvaluacion['texto']) . " con una puntuación promedio de {$promedioResultados}%.";

// Añadir fortalezas si hay
if (!empty($fortalezas)) {
    $resumenText .= "\n\nSus principales fortalezas se encuentran en las áreas de " . implode(", ", $fortalezas) . ".";
}

// Añadir debilidades si hay
if (!empty($debilidades)) {
    $resumenText .= "\n\nLas áreas con mayor oportunidad de desarrollo son " . implode(", ", $debilidades) . ".";
}

// Escribir el resumen
$pdf->MultiCell(0, 7, $resumenText, 0, 'L', 0, 1, '', '', true, 0, false, true, 0, 'T', false);

// Añadir espacio después del resumen
$pdf->Ln(3);

// Añadir gráficos (habría que generarlos previamente)
// En una implementación real, aquí se usaría una librería para generar gráficos
// o cargar imágenes pre-generadas de los gráficos

// Ejemplo simplificado para mostrar nivel de evaluación general
$pdf->SetFont('helvetica', 'B', 14);
$pdf->Cell(0, 10, 'Evaluación General: ' . $nivelEvaluacion['texto'] . ' (' . $promedioResultados . '%)', 0, 1, 'C');
$pdf->SetFont('helvetica', '', 11);
$pdf->Cell(0, 7, $nivelEvaluacion['descripcion'], 0, 1, 'C');

$pdf->Ln(5);

// Tabla de resultados detallados
if (!empty($evaluationResults)) {
    $pdf->AddPage();
    
    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->Cell(0, 10, 'Resultados Detallados', 0, 1, 'L');
    $pdf->SetFont('helvetica', '', 11);
    
    // Crear tabla
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(80, 7, 'Dimensión', 1, 0, 'C', 0);
    $pdf->Cell(40, 7, 'Puntuación', 1, 0, 'C', 0);
    $pdf->Cell(55, 7, 'Nivel', 1, 1, 'C', 0);
    $pdf->SetFont('helvetica', '', 10);
    
    // Llenar tabla con resultados
    foreach ($evaluationResults as $result) {
        $pdf->Cell(80, 7, $result['nombre'], 1, 0, 'L', 0);
        $pdf->Cell(40, 7, round($result['promedio']) . '%', 1, 0, 'C', 0);
        $pdf->Cell(55, 7, $result['nivel'], 1, 1, 'C', 0);
    }
    
    $pdf->Ln(5);
}

// Secciones por categorías de dimensiones
$categoriaTitulos = [
    'cognitiva' => 'Aptitudes Cognitivas',
    'personalidad' => 'Perfil de Personalidad',
    'competencia' => 'Competencias Fundamentales',
    'motivacion' => 'Perfil Motivacional'
];

// Procesar cada categoría si hay datos
foreach ($categoriaTitulos as $categoria => $titulo) {
    if (isset($dimensiones[$categoria]) && !empty($dimensiones[$categoria])) {
        $pdf->AddPage();
        
        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->Cell(0, 10, $titulo, 0, 1, 'L');
        $pdf->SetFont('helvetica', '', 11);
        
        // Descripción específica por categoría
        switch ($categoria) {
            case 'cognitiva':
                $pdf->MultiCell(0, 7, "Esta sección evalúa las capacidades intelectuales y de procesamiento de información del candidato, incluyendo diferentes tipos de razonamiento, atención y habilidades analíticas.", 0, 'L', 0, 1, '', '', true, 0, false, true, 0, 'T', false);
                break;
                
            case 'personalidad':
                $pdf->MultiCell(0, 7, "Esta sección describe los rasgos de personalidad del candidato en el contexto laboral, mostrando sus tendencias de comportamiento y preferencias en diferentes situaciones.", 0, 'L', 0, 1, '', '', true, 0, false, true, 0, 'T', false);
                break;
                
            case 'competencia':
                $pdf->MultiCell(0, 7, "Esta sección evalúa las competencias profesionales del candidato, entendidas como conjuntos de conocimientos, habilidades y actitudes que se aplican en el desempeño laboral.", 0, 'L', 0, 1, '', '', true, 0, false, true, 0, 'T', false);
                break;
                
            case 'motivacion':
                $pdf->MultiCell(0, 7, "Esta sección analiza los factores que impulsan y motivan al candidato en el entorno laboral, identificando qué aspectos del trabajo le resultan más satisfactorios y energizantes.", 0, 'L', 0, 1, '', '', true, 0, false, true, 0, 'T', false);
                break;
        }
        
        $pdf->Ln(3);
        
        // Resultados de esta categoría
        foreach ($dimensiones[$categoria] as $dimension) {
            $pdf->SetFont('helvetica', 'B', 12);
            $pdf->Cell(0, 8, $dimension['nombre'] . ': ' . round($dimension['promedio']) . '% (' . $dimension['nivel'] . ')', 0, 1, 'L');
            $pdf->SetFont('helvetica', '', 11);
            
            // Crear una interpretación específica según la dimensión y nivel
            $interpretacion = '';
            switch ($categoria) {
                case 'cognitiva':
                    if ($dimension['promedio'] >= 75) {
                        $interpretacion = "Muestra una destacada capacidad en esta área cognitiva, lo que le permite procesar información compleja con eficacia y precisión. Esta fortaleza representa una ventaja significativa para roles que requieran este tipo de habilidad.";
                    } elseif ($dimension['promedio'] >= 60) {
                        $interpretacion = "Presenta un nivel adecuado en esta aptitud cognitiva, permitiéndole manejar situaciones y tareas habituales con eficacia, aunque podría beneficiarse de desarrollo adicional para escenarios más complejos.";
                    } else {
                        $interpretacion = "Muestra un desarrollo básico en esta área cognitiva, lo que podría representar un desafío en roles donde esta aptitud sea fundamental. Se recomienda formación específica para fortalecer esta habilidad.";
                    }
                    break;
                    
                case 'personalidad':
                    if ($dimension['promedio'] >= 75) {
                        $interpretacion = "Presenta un nivel elevado en esta dimensión de personalidad, lo que caracteriza significativamente su estilo de comportamiento e interacción en entornos laborales.";
                    } elseif ($dimension['promedio'] >= 50) {
                        $interpretacion = "Muestra un nivel moderado en esta dimensión de personalidad, indicando un equilibrio en la expresión de este rasgo según las circunstancias y contextos.";
                    } else {
                        $interpretacion = "Presenta un nivel bajo en esta dimensión de personalidad, lo que indica una menor presencia de este rasgo en su comportamiento habitual y preferencias.";
                    }
                    break;
                    
                case 'competencia':
                    if ($dimension['promedio'] >= 75) {
                        $interpretacion = "Demuestra un dominio sobresaliente de esta competencia, aplicándola de manera consistente y efectiva en diversos contextos y situaciones complejas.";
                    } elseif ($dimension['promedio'] >= 60) {
                        $interpretacion = "Muestra un nivel adecuado de esta competencia, aplicándola efectivamente en situaciones habituales pero pudiendo beneficiarse de desarrollo para contextos más desafiantes.";
                    } else {
                        $interpretacion = "Presenta un nivel básico de esta competencia, pudiendo aplicarla en situaciones estructuradas pero requiriendo desarrollo significativo para mayor efectividad.";
                    }
                    break;
                    
                case 'motivacion':
                    if ($dimension['promedio'] >= 75) {
                        $interpretacion = "Este factor representa una motivación principal para el candidato, siendo un aspecto determinante en su satisfacción laboral y energía en el trabajo.";
                    } elseif ($dimension['promedio'] >= 50) {
                        $interpretacion = "Este factor tiene una importancia moderada en la motivación del candidato, contribuyendo a su satisfacción laboral pero no siendo determinante por sí solo.";
                    } else {
                        $interpretacion = "Este factor tiene una baja relevancia motivacional para el candidato, no siendo un aspecto prioritario en sus preferencias laborales.";
                    }
                    break;
            }
            
            $pdf->MultiCell(0, 7, $interpretacion, 0, 'L', 0, 1, '', '', true, 0, false, true, 0, 'T', false);
            $pdf->Ln(3);
        }
    }
}

// Añadir página final con conclusiones y recomendaciones
$pdf->AddPage();

$pdf->SetFont('helvetica', 'B', 14);
$pdf->Cell(0, 10, 'Conclusiones y Recomendaciones', 0, 1, 'L');
$pdf->SetFont('helvetica', '', 11);

// Generar conclusiones
$conclusiones = "Basándose en los resultados obtenidos en las evaluaciones, {$candidato['nombre']} {$candidato['apellido']} muestra un perfil profesional caracterizado por ";

// Fortalezas destacadas
if (!empty($fortalezas)) {
    $conclusiones .= "fortalezas significativas en " . implode(", ", array_slice($fortalezas, 0, 2)) . ", ";
} else {
    $conclusiones .= "un desempeño equilibrado en diferentes áreas, ";
}

// Nivel general
if ($promedioResultados >= 75) {
    $conclusiones .= "presentando un potencial destacado para roles que requieran estas capacidades. ";
} elseif ($promedioResultados >= 60) {
    $conclusiones .= "mostrando un potencial adecuado para roles alineados con su perfil. ";
} else {
    $conclusiones .= "pudiendo beneficiarse de desarrollo adicional en áreas clave. ";
}

// Añadir texto según género
if ($candidato['genero'] === 'femenino') {
    $conclusiones .= "La candidata presenta habilidades destacadas en ";
} else {
    $conclusiones .= "El candidato presenta habilidades destacadas en ";
}

// Añadir competencias destacadas o texto genérico
if (isset($dimensiones['competencia']) && count($dimensiones['competencia']) > 0) {
    $competenciasTop = array_slice($dimensiones['competencia'], 0, 2);
    $competenciasNombres = array_map(function($comp) {
        return strtolower($comp['nombre']);
    }, $competenciasTop);
    $conclusiones .= implode(" y ", $competenciasNombres) . ", ";
} else {
    $conclusiones .= "diversas áreas profesionales, ";
}

// Completar conclusión según nivel
if ($promedioResultados >= 75) {
    $conclusiones .= "lo que le hace especialmente adecuado/a para posiciones de responsabilidad que requieran un alto nivel de desempeño.";
} elseif ($promedioResultados >= 60) {
    $conclusiones .= "lo que le permite desempeñarse satisfactoriamente en roles alineados con estas capacidades.";
} else {
    $conclusiones .= "aunque se beneficiaría de desarrollo adicional para maximizar su potencial profesional.";
}

// Escribir conclusiones
$pdf->MultiCell(0, 7, $conclusiones, 0, 'L', 0, 1, '', '', true, 0, false, true, 0, 'T', false);

$pdf->Ln(5);

// Recomendaciones
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 8, 'Recomendaciones:', 0, 1, 'L');
$pdf->SetFont('helvetica', '', 11);

// Generar recomendaciones según el perfil
$recomendaciones = [];

// Recomendación general según nivel
if ($promedioResultados >= 75) {
    $recomendaciones[] = "Considerar al candidato para posiciones de responsabilidad que aprovechen sus fortalezas destacadas.";
} elseif ($promedioResultados >= 60) {
    $recomendaciones[] = "Considerar al candidato para roles que se alineen con su perfil de competencias, con posibilidad de crecimiento.";
} else {
    $recomendaciones[] = "Evaluar la adecuación del candidato para roles específicos donde sus fortalezas puedan ser mejor aprovechadas.";
}

// Recomendaciones de desarrollo si hay áreas débiles
if (!empty($debilidades)) {
    $recomendaciones[] = "Proporcionar formación específica en " . implode(" y ", array_map(function($deb) {
        return explode(" (", $deb)[0]; // Eliminar el porcentaje entre paréntesis
    }, array_slice($debilidades, 0, 2))) . " para fortalecer estas áreas.";
}

// Recomendación sobre entorno laboral
if (isset($dimensiones['personalidad']) || isset($dimensiones['motivacion'])) {
    $recomendaciones[] = "Proporcionar un entorno laboral que favorezca sus principales motivadores y se adapte a su estilo de personalidad para maximizar su satisfacción y rendimiento.";
}

// Añadir seguimiento
$recomendaciones[] = "Realizar seguimiento periódico de su desempeño para validar los resultados de esta evaluación y ajustar el plan de desarrollo según sea necesario.";

// Escribir cada recomendación como un ítem de lista
foreach ($recomendaciones as $recomendacion) {
    $pdf->MultiCell(0, 7, "• " . $recomendacion, 0, 'L', 0, 1, '', '', true, 0, false, true, 0, 'T', false);
}

// Añadir nota final
$pdf->Ln(10);
$pdf->SetFont('helvetica', 'I', 10);
$pdf->MultiCell(0, 7, "Nota: Este informe se basa en los resultados de evaluaciones psicométricas y competenciales, y debe considerarse como una herramienta complementaria en procesos de selección y desarrollo, no como el único criterio de decisión.", 0, 'L', 0, 1, '', '', true, 0, false, true, 0, 'T', false);

// Añadir firma y datos de generación
$pdf->Ln(15);
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(0, 7, "Informe generado por: " . $usuario_nombre, 0, 1, 'L');
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(0, 7, "Fecha de generación: " . date('d/m/Y H:i'), 0, 1, 'L');

// Generar salida del PDF
$pdf->Output('Informe_' . $candidato['nombre'] . '_' . $candidato['apellido'] . '_' . date('Ymd') . '.pdf', 'I');