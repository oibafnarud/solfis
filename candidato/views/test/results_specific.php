<!-- views/test/results_specific.php -->
<?php 
$title = "Resultados de " . htmlspecialchars($testInfo['titulo']);
include '../includes/candidate_header.php';
?>

<div class="container py-4">
    <div class="row">
        <div class="col-lg-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>Resultados: <?php echo htmlspecialchars($testInfo['titulo']); ?></h1>
                <div class="d-flex">
                    <a href="test.php?action=results" class="btn btn-outline-primary me-2">
                        <i class="fas fa-chart-pie"></i> Ver Todos Mis Resultados
                    </a>
                    <a href="test.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> Volver a Pruebas
                    </a>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-chart-line"></i> Resumen de Resultados</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-7">
                            <!-- Gráfico Radar para dimensiones -->
                            <canvas id="dimensionsChart" width="100%" height="300"></canvas>
                        </div>
                        <div class="col-md-5">
                            <?php
                            // Calcular promedio global de esta prueba
                            $totalValor = 0;
                            $totalItems = 0;
                            
                            foreach ($results as $result) {
                                $totalValor += $result['valor_normalizado'];
                                $totalItems++;
                            }
                            
                            $promedioGlobal = $totalItems > 0 ? round($totalValor / $totalItems) : 0;
                            
                            // Determinar nivel y color
                            $nivel = '';
                            $color = '';
                            $descripcion = '';
                            
                            if ($promedioGlobal >= 90) {
                                $nivel = 'Excepcional';
                                $color = 'success';
                                $descripcion = 'Has demostrado un nivel extraordinario en esta evaluación.';
                            } elseif ($promedioGlobal >= 80) {
                                $nivel = 'Sobresaliente';
                                $color = 'primary';
                                $descripcion = 'Has obtenido resultados muy por encima del promedio en esta evaluación.';
                            } elseif ($promedioGlobal >= 70) {
                                $nivel = 'Notable';
                                $color = 'info';
                                $descripcion = 'Has demostrado un buen dominio en la mayoría de las áreas evaluadas.';
                            } elseif ($promedioGlobal >= 60) {
                                $nivel = 'Adecuado';
                                $color = 'warning';
                                $descripcion = 'Tus resultados se encuentran en un nivel satisfactorio.';
                            } elseif ($promedioGlobal >= 50) {
                                $nivel = 'Moderado';
                                $color = 'warning';
                                $descripcion = 'Has mostrado un nivel aceptable en las áreas evaluadas, con oportunidades de mejora.';
                            } else {
                                $nivel = 'En desarrollo';
                                $color = 'danger';
                                $descripcion = 'Los resultados indican que hay áreas significativas que pueden desarrollarse más.';
                            }
                            ?>
                            
                            <h4 class="mb-3">Resultado Global</h4>
                            
                            <div class="text-center mb-4">
                                <div class="progress" style="height: 30px;">
                                    <div class="progress-bar bg-<?php echo $color; ?>" 
                                         role="progressbar" 
                                         style="width: <?php echo $promedioGlobal; ?>%;" 
                                         aria-valuenow="<?php echo $promedioGlobal; ?>" 
                                         aria-valuemin="0" 
                                         aria-valuemax="100">
                                        <?php echo $promedioGlobal; ?>%
                                    </div>
                                </div>
                                <h3 class="mt-2 text-<?php echo $color; ?>"><?php echo $nivel; ?></h3>
                            </div>
                            
                            <p><?php echo $descripcion; ?></p>
                            
                            <div class="mt-3">
                                <h5>Detalles de la evaluación:</h5>
                                <ul class="list-group">
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        Fecha de realización
                                        <span><?php echo date('d/m/Y', strtotime($results[0]['created_at'] ?? date('Y-m-d'))); ?></span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        Dimensiones evaluadas
                                        <span class="badge bg-primary rounded-pill"><?php echo count($results); ?></span>
                                    </li>
                                </ul>
                            </div>
                        </div>