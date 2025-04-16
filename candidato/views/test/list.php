<!-- views/test/list.php -->
<?php 
$title = "Mis Evaluaciones Psicométricas";
include '../includes/candidate_header.php';
?>

<div class="container py-4">
    <div class="row">
        <div class="col-lg-12">
            <h1 class="mb-4">Mis Evaluaciones Psicométricas</h1>
            
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
            
            <!-- Resumen de progreso -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-chart-pie"></i> Resumen de mis evaluaciones</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="progress" style="height: 30px;">
                                <div class="progress-bar bg-success" role="progressbar" 
                                     style="width: <?php echo $completionPercentage; ?>%;" 
                                     aria-valuenow="<?php echo $completionPercentage; ?>" 
                                     aria-valuemin="0" 
                                     aria-valuemax="100">
                                    <?php echo $completionPercentage; ?>% completado
                                </div>
                            </div>
                            
                            <div class="d-flex justify-content-between mt-3">
                                <div class="text-center">
                                    <h3><?php echo $testsCount; ?></h3>
                                    <p>Total de pruebas</p>
                                </div>
                                <div class="text-center">
                                    <h3><?php echo $completedCount; ?></h3>
                                    <p>Completadas</p>
                                </div>
                                <div class="text-center">
                                    <h3><?php echo $pendingCount; ?></h3>
                                    <p>Pendientes</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="alert alert-info mb-0">
                                <h5><i class="fas fa-info-circle"></i> Información importante</h5>
                                <p>Estas evaluaciones están diseñadas para ayudarte a identificar tus fortalezas y áreas de mejora. No hay respuestas correctas o incorrectas, responde con honestidad para obtener resultados más precisos.</p>
                                
                                <?php if (!empty($pendingTests)): ?>
                                <p class="mb-0"><strong>Próximo paso:</strong> Completa las pruebas pendientes para obtener un perfil completo de tus competencias.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <!-- Pruebas Pendientes -->
                <div class="col-md-6">
                    <div class="card mb-4">
                        <div class="card-header bg-warning text-dark">
                            <h5 class="mb-0"><i class="fas fa-hourglass-half"></i> Pruebas Pendientes (<?php echo count($pendingTests); ?>)</h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($pendingTests)): ?>
                            <p class="text-center text-muted my-3">¡Felicidades! Has completado todas tus pruebas asignadas.</p>
                            <?php else: ?>
                            <div class="list-group">
                                <?php foreach ($pendingTests as $test): ?>
                                <div class="list-group-item">
                                    <div class="d-flex w-100 justify-content-between align-items-center">
                                        <h5 class="mb-1"><?php echo htmlspecialchars($test['prueba_titulo']); ?></h5>
                                        <a href="test.php?action=start&test_id=<?php echo $test['prueba_id']; ?>" class="btn btn-primary btn-sm">
                                            <i class="fas fa-play"></i> Iniciar
                                        </a>
                                    </div>
                                    <p class="mb-1"><?php echo htmlspecialchars($test['prueba_descripcion'] ?? ''); ?></p>
                                    <small class="text-muted">
                                        <i class="fas fa-clock"></i> Tiempo estimado: <?php echo $test['tiempo_estimado'] ?? 'No especificado'; ?> min
                                    </small>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Pruebas en Progreso -->
                    <?php if (!empty($inProgressTests)): ?>
                    <div class="card mb-4">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0"><i class="fas fa-spinner"></i> Pruebas En Progreso (<?php echo count($inProgressTests); ?>)</h5>
                        </div>
                        <div class="card-body">
                            <div class="list-group">
                                <?php foreach ($inProgressTests as $test): ?>
                                <div class="list-group-item">
                                    <div class="d-flex w-100 justify-content-between align-items-center">
                                        <h5 class="mb-1"><?php echo htmlspecialchars($test['prueba_titulo']); ?></h5>
                                        <a href="test.php?action=take&session_id=<?php echo $test['sesion_id']; ?>" class="btn btn-info btn-sm text-white">
                                            <i class="fas fa-edit"></i> Continuar
                                        </a>
                                    </div>
                                    <p class="mb-1">
                                        <?php
                                        // Calcular progreso
                                        $respondidas = $test['respuestas_count'] ?? 0;
                                        $total = $test['preguntas_count'] ?? 1; // Evitar división por cero
                                        $porcentaje = round(($respondidas / $total) * 100);
                                        ?>
                                        <div class="progress" style="height: 10px;">
                                            <div class="progress-bar bg-info" role="progressbar" 
                                                 style="width: <?php echo $porcentaje; ?>%;" 
                                                 aria-valuenow="<?php echo $porcentaje; ?>" 
                                                 aria-valuemin="0" 
                                                 aria-valuemax="100"></div>
                                        </div>
                                    </p>
                                    <small class="text-muted">
                                        Iniciada: <?php echo date('d/m/Y H:i', strtotime($test['fecha_inicio'])); ?> • 
                                        <?php echo $respondidas; ?> de <?php echo $total; ?> preguntas respondidas
                                    </small>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Pruebas Completadas -->
                <div class="col-md-6">
                    <div class="card mb-4">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0"><i class="fas fa-check-circle"></i> Pruebas Completadas (<?php echo count($completedTests); ?>)</h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($completedTests)): ?>
                            <p class="text-center text-muted my-3">Aún no has completado ninguna prueba.</p>
                            <?php else: ?>
                            <div class="list-group">
                                <?php foreach ($completedTests as $test): ?>
                                <div class="list-group-item">
                                    <div class="d-flex w-100 justify-content-between align-items-center">
                                        <h5 class="mb-1"><?php echo htmlspecialchars($test['prueba_titulo']); ?></h5>
                                        <a href="test.php?action=results&test_id=<?php echo $test['prueba_id']; ?>" class="btn btn-outline-success btn-sm">
                                            <i class="fas fa-chart-bar"></i> Ver Resultados
                                        </a>
                                    </div>
                                    <?php if (isset($test['resultado_global'])): ?>
                                    <div class="progress mt-2 mb-1" style="height: 10px;">
                                        <?php
                                        $resultClass = 'bg-secondary';
                                        if ($test['resultado_global'] >= 90) $resultClass = 'bg-success';
                                        elseif ($test['resultado_global'] >= 75) $resultClass = 'bg-primary';
                                        elseif ($test['resultado_global'] >= 60) $resultClass = 'bg-info';
                                        elseif ($test['resultado_global'] >= 40) $resultClass = 'bg-warning';
                                        else $resultClass = 'bg-danger';
                                        ?>
                                        <div class="progress-bar <?php echo $resultClass; ?>" role="progressbar" 
                                             style="width: <?php echo $test['resultado_global']; ?>%;" 
                                             aria-valuenow="<?php echo $test['resultado_global']; ?>" 
                                             aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                    <?php endif; ?>
                                    <small class="text-muted">
                                        Completada: <?php echo date('d/m/Y H:i', strtotime($test['fecha_fin'])); ?>
                                    </small>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <?php if (count($completedTests) > 1): ?>
                            <div class="d-grid gap-2 mt-3">
                                <a href="test.php?action=results" class="btn btn-outline-primary">
                                    <i class="fas fa-chart-pie"></i> Ver Resultados Completos
                                </a>
                            </div>
                            <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/candidate_footer.php'; ?>