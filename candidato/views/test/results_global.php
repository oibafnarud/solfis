<!-- views/test/results_global.php -->
<?php 
$title = "Mis Resultados de Evaluación";
include '../includes/candidate_header.php';
?>

<div class="container py-4">
    <div class="row">
        <div class="col-lg-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>Mis Resultados de Evaluación</h1>
                <a href="test.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left"></i> Volver a Pruebas
                </a>
            </div>
            
            <?php if (empty($results) && empty($indicesResults)): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> No has completado ninguna evaluación aún. Completa las pruebas asignadas para ver tus resultados.
            </div>
            <?php else: ?>
            
            <!-- Resumen General -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-chart-line"></i> Resumen de Resultados</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-8">
                            <!-- Gráfico Radar Principal -->
                            <canvas id="radarChart" width="100%" height="400"></canvas>
                        </div>
                        <div class="col-md-4">
                            <h5>Perfil General</h5>
                            <?php
                            // Calcular promedio general
                            $totalValor = 0;
                            $totalItems = 0;
                            
                            // Incluir resultados de dimensiones
                            foreach ($results as $result) {
                                $totalValor += $result['valor_normalizado'];
                                $totalItems++;
                            }
                            
                            // Incluir resultados de índices
                            foreach ($indicesResults as $result) {
                                $totalValor += $result['valor'];
                                $totalItems++;
                            }
                            
                            $promedioGeneral = $totalItems > 0 ? round($totalValor / $totalItems) : 0;
                            
                            // Determinar nivel y color
                            $nivel = '';
                            $color = '';
                            $descripcion = '';
                            
                            if ($promedioGeneral >= 90) {
                                $nivel = 'Excepcional';
                                $color = 'success';
                                $descripcion = 'Demuestra un nivel extraordinario en las competencias evaluadas.';
                            } elseif ($promedioGeneral >= 80) {
                                $nivel = 'Sobresaliente';
                                $color = 'primary';
                                $descripcion = 'Muestra un alto nivel de dominio en la mayoría de las áreas evaluadas.';
                            } elseif ($promedioGeneral >= 70) {
                                $nivel = 'Notable';
                                $color = 'info';
                                $descripcion = 'Presenta un buen desarrollo en las competencias evaluadas.';
                            } elseif ($promedioGeneral >= 60) {
                                $nivel = 'Adecuado';
                                $color = 'warning';
                                $descripcion = 'Muestra un nivel satisfactorio en la mayoría de las áreas.';
                            } elseif ($promedioGeneral >= 50) {
                                $nivel = 'Moderado';
                                $color = 'warning';
                                $descripcion = 'Tiene áreas de competencia desarrolladas y otras que requieren mejora.';
                            } else {
                                $nivel = 'En desarrollo';
                                $color = 'danger';
                                $descripcion = 'Presenta oportunidades significativas de desarrollo en varias áreas.';
                            }
                            ?>
                            
                            <div class="text-center mb-4">
                                <div class="progress" style="height: 30px;">
                                    <div class="progress-bar bg-<?php echo $color; ?>" 
                                         role="progressbar" 
                                         style="width: <?php echo $promedioGeneral; ?>%;" 
                                         aria-valuenow="<?php echo $promedioGeneral; ?>" 
                                         aria-valuemin="0" 
                                         aria-valuemax="100">
                                        <?php echo $promedioGeneral; ?>%
                                    </div>
                                </div>
                                <h4 class="mt-2 text-<?php echo $color; ?>"><?php echo $nivel; ?></h4>
                            </div>
                            
                            <p><?php echo $descripcion; ?></p>
                            
                            <div class="alert alert-info">
                                <p><strong>Nota:</strong> Este perfil representa un promedio general de todas tus evaluaciones. Revisa los resultados detallados para una comprensión más profunda de tus fortalezas y áreas de desarrollo.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Índices Compuestos -->
            <?php if (!empty($indicesResults)): ?>
            <div class="card mb-4">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="fas fa-puzzle-piece"></i> Índices Compuestos</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-7">
                            <canvas id="indicesChart" width="100%" height="300"></canvas>
                        </div>
                        <div class="col-md-5">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Índice</th>
                                            <th>Nivel</th>
                                            <th>Valor</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($indicesResults as $index => $result): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($result['indice_nombre']); ?></td>
                                            <td>
                                                <?php if (isset($result['nivel_nombre'])): ?>
                                                <span class="badge" style="background-color: <?php echo $result['nivel_color']; ?>;">
                                                    <?php echo htmlspecialchars($result['nivel_nombre']); ?>
                                                </span>
                                                <?php else: ?>
                                                <span class="badge bg-secondary">No definido</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo round($result['valor']); ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <div class="accordion" id="indicesAccordion">
                        <?php foreach ($indicesResults as $index => $result): ?>
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="heading<?php echo $index; ?>">
                                <button class="accordion-button <?php echo $index > 0 ? 'collapsed' : ''; ?>" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?php echo $index; ?>" aria-expanded="<?php echo $index === 0 ? 'true' : 'false'; ?>" aria-controls="collapse<?php echo $index; ?>">
                                    <strong><?php echo htmlspecialchars($result['indice_nombre']); ?></strong>
                                    <span class="ms-2 badge" style="background-color: <?php echo $result['nivel_color']; ?>;">
                                        <?php echo htmlspecialchars($result['nivel_nombre']); ?> (<?php echo round($result['valor']); ?>%)
                                    </span>
                                </button>
                            </h2>
                            <div id="collapse<?php echo $index; ?>" class="accordion-collapse collapse <?php echo $index === 0 ? 'show' : ''; ?>" aria-labelledby="heading<?php echo $index; ?>" data-bs-parent="#indicesAccordion">
                                <div class="accordion-body">
                                    <?php if (!empty($result['interpretacion'])): ?>
                                    <p><?php echo nl2br(htmlspecialchars($result['interpretacion'])); ?></p>
                                    <?php else: ?>
                                    <p><em>No hay interpretación disponible para este índice.</em></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Resultados por Dimensiones -->
            <?php if (!empty($results)): ?>
            <div class="card mb-4">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="fas fa-th-list"></i> Resultados por Dimensiones</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Dimensión</th>
                                    <th>Valor</th>
                                    <th>Nivel</th>
                                    <th>Detalle</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($results as $result): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($result['dimension_nombre']); ?></td>
                                    <td>
                                        <div class="progress" style="height: 20px;">
                                            <?php
                                            $resultClass = 'bg-secondary';
                                            if ($result['valor_normalizado'] >= 90) $resultClass = 'bg-success';
                                            elseif ($result['valor_normalizado'] >= 75) $resultClass = 'bg-primary';
                                            elseif ($result['valor_normalizado'] >= 60) $resultClass = 'bg-info';
                                            elseif ($result['valor_normalizado'] >= 40) $resultClass = 'bg-warning';
                                            else $resultClass = 'bg-danger';
                                            ?>
                                            <div class="progress-bar <?php echo $resultClass; ?>" role="progressbar" 
                                                 style="width: <?php echo $result['valor_normalizado']; ?>%;" 
                                                 aria-valuenow="<?php echo $result['valor_normalizado']; ?>" 
                                                 aria-valuemin="0" aria-valuemax="100">
                                                <?php echo round($result['valor_normalizado']); ?>%
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if (isset($result['nivel'])): ?>
                                        <span class="badge bg-<?php echo $resultClass; ?>">
                                            <?php echo htmlspecialchars($result['nivel']); ?>
                                        </span>
                                        <?php else: ?>
                                        <span class="badge bg-secondary">No definido</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-outline-primary" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#dimensionModal<?php echo $result['dimension_id']; ?>">
                                            <i class="fas fa-info-circle"></i> Ver
                                        </button>
                                        
                                        <!-- Modal para cada dimensión -->
                                        <div class="modal fade" id="dimensionModal<?php echo $result['dimension_id']; ?>" tabindex="-1" aria-labelledby="dimensionModalLabel<?php echo $result['dimension_id']; ?>" aria-hidden="true">
                                            <div class="modal-dialog modal-lg">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title" id="dimensionModalLabel<?php echo $result['dimension_id']; ?>">
                                                            <?php echo htmlspecialchars($result['dimension_nombre']); ?>
                                                        </h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="text-center mb-4">
                                                            <div class="progress" style="height: 30px;">
                                                                <div class="progress-bar <?php echo $resultClass; ?>" role="progressbar" 
                                                                     style="width: <?php echo $result['valor_normalizado']; ?>%;" 
                                                                     aria-valuenow="<?php echo $result['valor_normalizado']; ?>" 
                                                                     aria-valuemin="0" aria-valuemax="100">
                                                                    <?php echo round($result['valor_normalizado']); ?>%
                                                                </div>
                                                            </div>
                                                            <h4 class="mt-2"><?php echo htmlspecialchars($result['nivel'] ?? 'No definido'); ?></h4>
                                                        </div>
                                                        
                                                        <?php if (!empty($result['interpretacion'])): ?>
                                                        <div class="card mb-3">
                                                            <div class="card-header">Interpretación</div>
                                                            <div class="card-body">
                                                                <?php echo nl2br(htmlspecialchars($result['interpretacion'])); ?>
                                                            </div>
                                                        </div>
                                                        <?php endif; ?>
                                                        
                                                        <!-- Información adicional según dimensión -->
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Núcleo Motivacional si existe -->
            <?php if (!empty($motivationalCore)): ?>
            <div class="card mb-4">
                <div class="card-header bg-purple text-white" style="background-color: #6f42c1;">
                    <h5 class="mb-0"><i class="fas fa-star"></i> Núcleo Motivacional</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h5>Tus principales motivaciones:</h5>
                            <ol class="list-group list-group-numbered mb-4">
                                <li class="list-group-item d-flex justify-content-between align-items-start">
                                    <div class="ms-2 me-auto">
                                        <div class="fw-bold"><?php echo htmlspecialchars($motivationalCore['motivacion1_nombre']); ?></div>
                                        <?php 
                                        // Aquí podrías añadir una breve descripción de cada motivación
                                        ?>
                                    </div>
                                    <span class="badge bg-primary rounded-pill"><?php echo round($motivationalCore['motivacion1_valor']); ?></span>
                                </li>
                                <?php if (!empty($motivationalCore['motivacion2_nombre'])): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-start">
                                    <div class="ms-2 me-auto">
                                        <div class="fw-bold"><?php echo htmlspecialchars($motivationalCore['motivacion2_nombre']); ?></div>
                                    </div>
                                    <span class="badge bg-primary rounded-pill"><?php echo round($motivationalCore['motivacion2_valor']); ?></span>
                                </li>
                                <?php endif; ?>
                                <?php if (!empty($motivationalCore['motivacion3_nombre'])): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-start">
                                    <div class="ms-2 me-auto">
                                        <div class="fw-bold"><?php echo htmlspecialchars($motivationalCore['motivacion3_nombre']); ?></div>
                                    </div>
                                    <span class="badge bg-primary rounded-pill"><?php echo round($motivationalCore['motivacion3_valor']); ?></span>
                                </li>
                                <?php endif; ?>
                            </ol>
                            
                            <?php if (isset($motivationalCore['indice_claridad'])): ?>
                            <div class="card">
                                <div class="card-header">Índice de Claridad Motivacional (ICM)</div>
                                <div class="card-body">
                                    <h5 class="card-title">
                                        <?php 
                                        $icm = $motivationalCore['indice_claridad'];
                                        $icmTexto = '';
                                        
                                        if ($icm > 3) $icmTexto = 'Muy Definido';
                                        elseif ($icm >= 2) $icmTexto = 'Definido';
                                        elseif ($icm >= 1) $icmTexto = 'Moderadamente Definido';
                                        else $icmTexto = 'Difuso';
                                        
                                        echo $icmTexto . ' (' . number_format($icm, 1) . ')';
                                        ?>
                                    </h5>
                                    <p class="card-text">
                                        <?php
                                        switch ($icmTexto) {
                                            case 'Muy Definido':
                                                echo 'Tu perfil motivacional está muy claramente definido. Tienes preferencias muy marcadas que te impulsan en tu vida laboral.';
                                                break;
                                            case 'Definido':
                                                echo 'Tienes un perfil motivacional bien definido. Distingues claramente qué aspectos del trabajo son importantes para ti.';
                                                break;
                                            case 'Moderadamente Definido':
                                                echo 'Tu perfil motivacional muestra tendencias reconocibles, aunque no tan pronunciadas.';
                                                break;
                                            case 'Difuso':
                                                echo 'Tu perfil motivacional no muestra preferencias muy marcadas entre las diferentes motivaciones.';
                                                break;
                                        }
                                        ?>
                                    </p>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <h5>¿Qué significa esto?</h5>
                            <p>Tu núcleo motivacional refleja lo que te impulsa y energiza en el entorno laboral. Estos son los factores que más influyen en tu satisfacción y compromiso profesional.</p>
                            
                            <div class="alert alert-info">
                                <h6><i class="fas fa-lightbulb"></i> Entornos laborales recomendados:</h6>
                                <ul>
                                    <?php
                                    // Ejemplo - esto debería adaptarse según las motivaciones reales
                                    $recomendaciones = [];
                                    
                                    if (strpos($motivationalCore['motivacion1_nombre'], 'Logro') !== false || 
                                        strpos($motivationalCore['motivacion2_nombre'], 'Logro') !== false || 
                                        strpos($motivationalCore['motivacion3_nombre'], 'Logro') !== false) {
                                        $recomendaciones[] = 'Entornos orientados a resultados con metas claras y desafiantes';
                                    }
                                    
                                    if (strpos($motivationalCore['motivacion1_nombre'], 'Poder') !== false || 
                                        strpos($motivationalCore['motivacion2_nombre'], 'Poder') !== false || 
                                        strpos($motivationalCore['motivacion3_nombre'], 'Poder') !== false) {
                                        $recomendaciones[] = 'Posiciones con oportunidades de liderazgo e influencia';
                                    }
                                    
                                    if (strpos($motivationalCore['motivacion1_nombre'], 'Afiliación') !== false || 
                                        strpos($motivationalCore['motivacion2_nombre'], 'Afiliación') !== false || 
                                        strpos($motivationalCore['motivacion3_nombre'], 'Afiliación') !== false) {
                                        $recomendaciones[] = 'Ambientes colaborativos con énfasis en el trabajo en equipo';
                                    }
                                    
                                    if (strpos($motivationalCore['motivacion1_nombre'], 'Seguridad') !== false || 
                                        strpos($motivationalCore['motivacion2_nombre'], 'Seguridad') !== false || 
                                        strpos($motivationalCore['motivacion3_nombre'], 'Seguridad') !== false) {
                                        $recomendaciones[] = 'Organizaciones estables con estructura clara y previsibilidad';
                                    }
                                    
                                    if (strpos($motivationalCore['motivacion1_nombre'], 'Autonomía') !== false || 
                                        strpos($motivationalCore['motivacion2_nombre'], 'Autonomía') !== false || 
                                        strpos($motivationalCore['motivacion3_nombre'], 'Autonomía') !== false) {
                                        $recomendaciones[] = 'Entornos que permitan independencia en la toma de decisiones';
                                    }
                                    
                                    if (strpos($motivationalCore['motivacion1_nombre'], 'Servicio') !== false || 
                                        strpos($motivationalCore['motivacion2_nombre'], 'Servicio') !== false || 
                                        strpos($motivationalCore['motivacion3_nombre'], 'Servicio') !== false) {
                                        $recomendaciones[] = 'Organizaciones con propósito social claro y misión significativa';
                                    }
                                    
                                    if (strpos($motivationalCore['motivacion1_nombre'], 'Reto') !== false || 
                                        strpos($motivationalCore['motivacion2_nombre'], 'Reto') !== false || 
                                        strpos($motivationalCore['motivacion3_nombre'], 'Reto') !== false) {
                                        $recomendaciones[] = 'Ambientes dinámicos con problemas complejos para resolver';
                                    }
                                    
                                    if (strpos($motivationalCore['motivacion1_nombre'], 'Equilibrio') !== false || 
                                        strpos($motivationalCore['motivacion2_nombre'], 'Equilibrio') !== false || 
                                        strpos($motivationalCore['motivacion3_nombre'], 'Equilibrio') !== false) {
                                        $recomendaciones[] = 'Organizaciones que valoren el balance entre vida laboral y personal';
                                    }
                                    
                                    // Mostrar recomendaciones
                                    foreach ($recomendaciones as $recomendacion) {
                                        echo "<li>{$recomendacion}</li>";
                                    }
                                    
                                    if (empty($recomendaciones)) {
                                        echo "<li>No hay suficiente información para generar recomendaciones específicas</li>";
                                    }
                                    ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Scripts para gráficos -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Datos para gráficos
    <?php
    // Preparar datos para gráfico radar
    $radarLabels = [];
    $radarData = [];
    
    // Incluir índices compuestos
    foreach ($indicesResults as $result) {
        $radarLabels[] = $result['indice_nombre'];
        $radarData[] = round($result['valor']);
    }
    
    // Incluir dimensiones si no hay muchos índices
    if (count($indicesResults) < 7) {
        foreach ($results as $result) {
            $radarLabels[] = $result['dimension_nombre'];
            $radarData[] = round($result['valor_normalizado']);
        }
    }
    
    // Limitar a máximo 8 elementos para el radar
    if (count($radarLabels) > 8) {
        array_splice($radarLabels, 8);
        array_splice($radarData, 8);
    }
    
    // Preparar datos para gráfico de barras (índices)
    $indicesLabels = [];
    $indicesData = [];
    $indices