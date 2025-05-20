/**
 * Scripts para la vista de resultados detallados del candidato
 * Utiliza Chart.js para visualizaciones
 */

// Datos principales - en una implementación real se obtendrían del backend
let candidatoData = {
    id: document.querySelector('input[name="candidato_id"]') ? document.querySelector('input[name="candidato_id"]').value : null,
    evaluacionGeneral: parseFloat(document.getElementById('gaugeValue')?.textContent || '0'),
    nivelEvaluacion: document.getElementById('evaluationLevel')?.textContent || ''
};

// Colores para gráficos
const chartColors = {
    primary: '#4e73df',
    success: '#1cc88a',
    info: '#36b9cc',
    warning: '#f6c23e',
    danger: '#e74a3b',
    secondary: '#858796',
    light: '#f8f9fc',
    dark: '#5a5c69',
    // Colores adicionales para gráficos con muchas series
    palette: [
        '#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b', 
        '#2e59d9', '#17a673', '#2c9faf', '#F4E869', '#e04a3b',
        '#8B82D0', '#7ED7C1', '#F8DE22', '#FF6969', '#C43EAA'
    ]
};

// Cuando el DOM está listo
document.addEventListener('DOMContentLoaded', function() {
    console.log('Inicializando gráficos para resultados del candidato...');
    
    // Inicializar gráficos principales
    initGeneralScoreGauge();
    initProfileRadarChart();
    initMotivationBarChart();
    
    // Inicializar gráficos por pestañas
    initCognitiveCharts();
    initPersonalityCharts();
    initMotivationCharts();
    initCompetencyCharts();
    initProfileFitCharts();
    
    // Botones y acciones
    setupEventListeners();
});

/**
 * Configura eventos para botones y acciones
 */
function setupEventListeners() {
    // Botón de exportar PDF
    const exportBtn = document.getElementById('exportPDF');
    if (exportBtn) {
        exportBtn.addEventListener('click', function() {
            const candidatoId = candidatoData.id;
            if (candidatoId) {
                window.open('generar-informe.php?id=' + candidatoId, '_blank');
            }
        });
    }
    
    // Botón de enviar por email
    const emailBtn = document.getElementById('sendEmail');
    if (emailBtn) {
        emailBtn.addEventListener('click', function() {
            const emailModal = new bootstrap.Modal(document.getElementById('emailModal'));
            emailModal.show();
        });
    }
    
    // Selección de perfiles para comparación
    const profileSelectors = document.querySelectorAll('.profile-select');
    profileSelectors.forEach(selector => {
        selector.addEventListener('click', function(e) {
            e.preventDefault();
            const profileId = this.getAttribute('data-profile-id');
            const profileName = this.textContent;
            
            document.getElementById('perfilDropdown').textContent = profileName;
            
            // Actualizar visualizaciones de ajuste al perfil
            updateProfileFitVisualizations(profileId, profileName);
        });
    });
}

/**
 * Inicializa el gauge de puntuación general
 */
function initGeneralScoreGauge() {
    const gaugeCtx = document.getElementById('generalScoreGauge');
    if (!gaugeCtx) return;
    
    // Obtener valor y determinar color según el nivel
    const score = candidatoData.evaluacionGeneral;
    let gaugeColor;
    
    switch (candidatoData.nivelEvaluacion) {
        case 'Excepcional':
        case 'Sobresaliente':
            gaugeColor = chartColors.success;
            break;
        case 'Notable':
            gaugeColor = chartColors.info;
            break;
        case 'Adecuado':
            gaugeColor = chartColors.primary;
            break;
        case 'Moderado':
            gaugeColor = chartColors.warning;
            break;
        default:
            gaugeColor = chartColors.danger;
    }
    
    // Crear gráfico gauge
    new Chart(gaugeCtx, {
        type: 'doughnut',
        data: {
            datasets: [{
                data: [score, 100 - score],
                backgroundColor: [gaugeColor, '#e9ecef'],
                borderWidth: 0,
                circumference: 180,
                rotation: 270
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '80%',
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    enabled: false
                }
            }
        }
    });
}

/**
 * Inicializa el gráfico de radar para el perfil general
 */
function initProfileRadarChart() {
    const radarCtx = document.getElementById('profileRadarChart');
    if (!radarCtx) return;
    
    // Recopilar datos de las dimensiones visibles en la página
    // En una implementación real, estos datos vendrían del backend
    const dimensiones = collectDimensionsFromPage();
    
    // Si no hay suficientes dimensiones, usar datos de ejemplo
    if (dimensiones.length < 5) {
        dimensiones.push(
            { name: 'Aptitudes Cognitivas', value: 78 },
            { name: 'Comunicación', value: 65 },
            { name: 'Trabajo en Equipo', value: 82 },
            { name: 'Adaptabilidad', value: 70 },
            { name: 'Responsabilidad', value: 88 },
            { name: 'Liderazgo', value: 60 },
            { name: 'Orientación a Resultados', value: 75 }
        );
    }
    
    // Limitar a 8 dimensiones para legibilidad
    const topDimensions = dimensiones.slice(0, 8);
    
    new Chart(radarCtx, {
        type: 'radar',
        data: {
            labels: topDimensions.map(dim => dim.name),
            datasets: [{
                label: 'Perfil del Candidato',
                data: topDimensions.map(dim => dim.value),
                backgroundColor: 'rgba(78, 115, 223, 0.2)',
                borderColor: chartColors.primary,
                borderWidth: 2,
                pointBackgroundColor: chartColors.primary,
                pointBorderColor: '#fff',
                pointHoverBackgroundColor: '#fff',
                pointHoverBorderColor: chartColors.primary
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                r: {
                    beginAtZero: true,
                    max: 100,
                    ticks: {
                        stepSize: 20,
                        display: false
                    },
                    pointLabels: {
                        font: {
                            size: 12
                        }
                    }
                }
            },
            plugins: {
                legend: {
                    display: false
                }
            }
        }
    });
}

/**
 * Inicializa el gráfico de barras para motivación
 */
function initMotivationBarChart() {
    const barCtx = document.getElementById('motivationBarChart');
    if (!barCtx) return;
    
    // Recopilar datos de motivaciones - en una implementación real vendrían del backend
    const motivations = collectMotivationsFromPage();
    
    // Si no hay suficientes motivaciones, usar datos de ejemplo
    if (motivations.length < 5) {
        motivations.push(
            { name: 'Logro', value: 85 },
            { name: 'Autonomía', value: 76 },
            { name: 'Reto', value: 72 },
            { name: 'Afiliación', value: 55 },
            { name: 'Poder', value: 42 },
            { name: 'Seguridad', value: 38 },
            { name: 'Equilibrio', value: 48 }
        );
    }
    
    // Ordenar de mayor a menor
    motivations.sort((a, b) => b.value - a.value);
    
    new Chart(barCtx, {
        type: 'bar',
        data: {
            labels: motivations.map(m => m.name),
            datasets: [{
                label: 'Nivel',
                data: motivations.map(m => m.value),
                backgroundColor: motivations.map((m, index) => chartColors.palette[index % chartColors.palette.length]),
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            indexAxis: 'y',
            scales: {
                x: {
                    beginAtZero: true,
                    max: 100
                }
            },
            plugins: {
                legend: {
                    display: false
                }
            }
        }
    });
}

/**
 * Inicializa los gráficos para la pestaña de aptitudes cognitivas
 */
function initCognitiveCharts() {
    const cognitiveCtx = document.getElementById('cognitiveBarChart');
    if (!cognitiveCtx) return;
    
    // Recopilar dimensiones cognitivas
    const cognitiveDimensions = collectDimensionsByCategory('cognitiva');
    
    // Si no hay suficientes, usar datos de ejemplo
    if (cognitiveDimensions.length < 3) {
        cognitiveDimensions.push(
            { name: 'Razonamiento Verbal', value: 85 },
            { name: 'Razonamiento Numérico', value: 75 },
            { name: 'Razonamiento Lógico', value: 90 },
            { name: 'Atención al Detalle', value: 80 }
        );
    }
    
    new Chart(cognitiveCtx, {
        type: 'bar',
        data: {
            labels: cognitiveDimensions.map(dim => dim.name),
            datasets: [{
                label: 'Puntuación',
                data: cognitiveDimensions.map(dim => dim.value),
                backgroundColor: cognitiveDimensions.map((dim, index) => chartColors.palette[index % chartColors.palette.length]),
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100
                }
            },
            plugins: {
                legend: {
                    display: false
                }
            }
        }
    });
}

/**
 * Inicializa los gráficos para la pestaña de personalidad
 */
function initPersonalityCharts() {
    const personalityCtx = document.getElementById('personalityPolarChart');
    if (!personalityCtx) return;
    
    // Recopilar dimensiones de personalidad
    const personalityDimensions = collectDimensionsByCategory('personalidad');
    
    // Si no hay suficientes, usar datos de ejemplo
    if (personalityDimensions.length < 3) {
        personalityDimensions.push(
            { name: 'Extroversión', value: 65 },
            { name: 'Estabilidad Emocional', value: 75 },
            { name: 'Apertura a Experiencias', value: 80 },
            { name: 'Cooperación', value: 60 },
            { name: 'Meticulosidad', value: 70 }
        );
    }
    
    new Chart(personalityCtx, {
        type: 'polarArea',
        data: {
            labels: personalityDimensions.map(dim => dim.name),
            datasets: [{
                data: personalityDimensions.map(dim => dim.value),
                backgroundColor: personalityDimensions.map((dim, index) => chartColors.palette[index % chartColors.palette.length] + '80'),
                borderColor: personalityDimensions.map((dim, index) => chartColors.palette[index % chartColors.palette.length]),
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                r: {
                    beginAtZero: true,
                    max: 100,
                    ticks: {
                        stepSize: 20,
                        display: false
                    }
                }
            },
            plugins: {
                legend: {
                    position: 'right'
                }
            }
        }
    });
}

/**
 * Inicializa los gráficos para la pestaña de motivaciones
 */
function initMotivationCharts() {
    const motivationalCtx = document.getElementById('motivationalRadarChart');
    const pieCtx = document.getElementById('motivationPieChart');
    if (!motivationalCtx || !pieCtx) return;
    
    // Recopilar datos de motivaciones
    const motivations = collectMotivationsFromPage();
    
    // Si no hay suficientes, usar datos de ejemplo
    if (motivations.length < 5) {
        motivations.push(
            { name: 'Logro', value: 85 },
            { name: 'Autonomía', value: 76 },
            { name: 'Reto', value: 72 },
            { name: 'Afiliación', value: 55 },
            { name: 'Poder', value: 42 },
            { name: 'Seguridad', value: 38 },
            { name: 'Equilibrio', value: 48 }
        );
    }
    
    // Gráfico de radar
    new Chart(motivationalCtx, {
        type: 'radar',
        data: {
            labels: motivations.map(m => m.name),
            datasets: [{
                label: 'Motivación',
                data: motivations.map(m => m.value),
                backgroundColor: 'rgba(78, 115, 223, 0.2)',
                borderColor: chartColors.primary,
                borderWidth: 2,
                pointBackgroundColor: chartColors.primary,
                pointBorderColor: '#fff',
                pointHoverBackgroundColor: '#fff',
                pointHoverBorderColor: chartColors.primary
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                r: {
                    beginAtZero: true,
                    max: 100,
                    ticks: {
                        stepSize: 20,
                        display: false
                    }
                }
            }
        }
    });
    
    // Gráfico de pie para las principales motivaciones
    // Ordenar y tomar las 3 mayores
    motivations.sort((a, b) => b.value - a.value);
    const topMotivations = motivations.slice(0, 3);
    
    new Chart(pieCtx, {
        type: 'pie',
        data: {
            labels: topMotivations.map(m => m.name),
            datasets: [{
                data: topMotivations.map(m => m.value),
                backgroundColor: [
                    chartColors.primary,
                    chartColors.success,
                    chartColors.info
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });
}

/**
 * Inicializa los gráficos para la pestaña de competencias
 */
function initCompetencyCharts() {
    const competencyCtx = document.getElementById('competencyRadarChart');
    if (!competencyCtx) return;
    
    // Recopilar dimensiones de competencias
    const competencyDimensions = collectDimensionsByCategory('competencia');
    
    // Si no hay suficientes, usar datos de ejemplo
    if (competencyDimensions.length < 3) {
        competencyDimensions.push(
            { name: 'Responsabilidad', value: 88 },
            { name: 'Integridad', value: 85 },
            { name: 'Adaptabilidad', value: 70 },
            { name: 'Comunicación', value: 65 },
            { name: 'Trabajo en Equipo', value: 82 }
        );
    }
    
    new Chart(competencyCtx, {
        type: 'radar',
        data: {
            labels: competencyDimensions.map(dim => dim.name),
            datasets: [{
                label: 'Nivel de Competencia',
                data: competencyDimensions.map(dim => dim.value),
                backgroundColor: 'rgba(28, 200, 138, 0.2)',
                borderColor: chartColors.success,
                borderWidth: 2,
                pointBackgroundColor: chartColors.success,
                pointBorderColor: '#fff',
                pointHoverBackgroundColor: '#fff',
                pointHoverBorderColor: chartColors.success
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                r: {
                    beginAtZero: true,
                    max: 100,
                    ticks: {
                        stepSize: 20,
                        display: false
                    }
                }
            }
        }
    });
}

/**
 * Inicializa los gráficos para la pestaña de ajuste al puesto
 */
function initProfileFitCharts() {
    // Esta función se llama cuando se selecciona un perfil
    // Por defecto, no muestra nada hasta que se seleccione un perfil
}

/**
 * Actualiza visualizaciones de ajuste al perfil seleccionado
 * @param {string} profileId - ID del perfil seleccionado
 * @param {string} profileName - Nombre del perfil
 */
function updateProfileFitVisualizations(profileId, profileName) {
    updateFitChart(profileId, profileName);
    updateFitTable(profileId, profileName);
    updateFitGauge(profileId);
    updateFitStrengthsGaps(profileId, profileName);
    updateFitRecommendations(profileId, profileName);
}

/**
 * Actualiza el gráfico de radar de ajuste al perfil
 * @param {string} profileId - ID del perfil
 * @param {string} profileName - Nombre del perfil
 */
function updateFitChart(profileId, profileName) {
    const fitCtx = document.getElementById('profileFitChart');
    if (!fitCtx) return;
    
    // En una implementación real, estos datos vendrían de una petición AJAX
    // Aquí simulamos datos para visualización
    const allDimensions = collectDimensionsFromPage();
    
    // Generar valores "ideales" simulados para el perfil seleccionado
    const idealValues = allDimensions.map(dim => {
        // Generar un valor "ideal" cercano al valor real pero con alguna variación
        let ideal = dim.value + Math.floor(Math.random() * 20) - 10;
        // Asegurar que esté dentro del rango 0-100
        ideal = Math.min(100, Math.max(0, ideal));
        return {
            name: dim.name,
            value: dim.value,
            ideal: ideal
        };
    });
    
    // Limitar a 8 dimensiones para legibilidad
    const chartDimensions = idealValues.slice(0, 8);
    
    // Si hay un gráfico existente, destruirlo
    if (window.fitChart instanceof Chart) {
        window.fitChart.destroy();
    }
    
    // Crear nuevo gráfico
    window.fitChart = new Chart(fitCtx, {
        type: 'radar',
        data: {
            labels: chartDimensions.map(dim => dim.name),
            datasets: [
                {
                    label: 'Candidato',
                    data: chartDimensions.map(dim => dim.value),
                    backgroundColor: 'rgba(78, 115, 223, 0.2)',
                    borderColor: chartColors.primary,
                    borderWidth: 2,
                    pointBackgroundColor: chartColors.primary,
                    pointBorderColor: '#fff'
                },
                {
                    label: profileName,
                    data: chartDimensions.map(dim => dim.ideal),
                    backgroundColor: 'rgba(28, 200, 138, 0.2)',
                    borderColor: chartColors.success,
                    borderWidth: 2,
                    pointBackgroundColor: chartColors.success,
                    pointBorderColor: '#fff',
                    pointStyle: 'triangle'
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                r: {
                    beginAtZero: true,
                    max: 100,
                    ticks: {
                        stepSize: 20,
                        display: false
                    }
                }
            }
        }
    });
}

/**
 * Actualiza la tabla comparativa de ajuste al perfil
 * @param {string} profileId - ID del perfil
 * @param {string} profileName - Nombre del perfil
 */
function updateFitTable(profileId, profileName) {
    const tableBody = document.getElementById('fitComparisonTable');
    if (!tableBody) return;
    
    // Limpiar tabla
    tableBody.innerHTML = '';
    
    // En una implementación real, estos datos vendrían de una petición AJAX
    // Aquí simulamos datos para visualización
    const allDimensions = collectDimensionsFromPage();
    
    // Generar valores "ideales" simulados para el perfil seleccionado
    const idealValues = allDimensions.map(dim => {
        // Generar un valor "ideal" cercano al valor real pero con alguna variación
        let ideal = dim.value + Math.floor(Math.random() * 20) - 10;
        // Asegurar que esté dentro del rango 0-100
        ideal = Math.min(100, Math.max(0, ideal));
        return {
            name: dim.name,
            value: dim.value,
            ideal: ideal
        };
    });
    
    // Crear filas para la tabla
    idealValues.forEach(dim => {
        const diff = dim.value - dim.ideal;
        const fitClass = Math.abs(diff) <= 10 ? 'success' : (Math.abs(diff) <= 20 ? 'warning' : 'danger');
        const fitSymbol = diff >= 0 ? '<i class="fas fa-arrow-up"></i>' : '<i class="fas fa-arrow-down"></i>';
        
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${dim.name}</td>
            <td>${dim.value}%</td>
            <td>${dim.ideal}%</td>
            <td class="text-${fitClass}">
                ${fitSymbol} ${Math.abs(diff)}%
            </td>
        `;
        
        tableBody.appendChild(row);
    });
}

/**
 * Actualiza el gauge de ajuste al perfil
 * @param {string} profileId - ID del perfil
 */
function updateFitGauge(profileId) {
    const fitGauge = document.getElementById('fitScoreGauge');
    if (!fitGauge) return;
    
    // Calcular puntuación de ajuste simulada
    // En una implementación real, esto vendría del backend
    const fitScore = Math.floor(Math.random() * 30) + 50; // 50-80%
    
    // Determinar color y texto según la puntuación
    let fitColor, fitText;
    if (fitScore >= 85) {
        fitColor = chartColors.success;
        fitText = 'Ajuste Excelente';
    } else if (fitScore >= 70) {
        fitColor = chartColors.info;
        fitText = 'Ajuste Alto';
    } else if (fitScore >= 50) {
        fitColor = chartColors.warning;
        fitText = 'Ajuste Moderado';
    } else {
        fitColor = chartColors.danger;
        fitText = 'Ajuste Bajo';
    }
    
    // Actualizar texto del gauge
    document.getElementById('fitGaugeValue').textContent = `${fitScore}%`;
    document.getElementById('fitScoreText').textContent = fitText;
    
    // Si hay un gráfico existente, destruirlo
    if (window.fitGaugeChart instanceof Chart) {
        window.fitGaugeChart.destroy();
    }
    
    // Crear nuevo gauge
    const ctx = fitGauge.getContext('2d');
    window.fitGaugeChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            datasets: [{
                data: [fitScore, 100 - fitScore],
                backgroundColor: [fitColor, '#e9ecef'],
                borderWidth: 0,
                circumference: 180,
                rotation: 270
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '80%',
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    enabled: false
                }
            }
        }
    });
}

/**
 * Actualiza las fortalezas y brechas de ajuste al perfil
 * @param {string} profileId - ID del perfil
 * @param {string} profileName - Nombre del perfil
 */
function updateFitStrengthsGaps(profileId, profileName) {
    const strengthsList = document.getElementById('fitStrengthsList');
    const gapsList = document.getElementById('fitGapsList');
    if (!strengthsList || !gapsList) return;
    
    // Limpiar listas
    strengthsList.innerHTML = '';
    gapsList.innerHTML = '';
    
    // En una implementación real, estos datos vendrían de una petición AJAX
    // Aquí simulamos datos para visualización
    const allDimensions = collectDimensionsFromPage();
    
    // Generar valores "ideales" simulados para el perfil seleccionado
    const comparisonData = allDimensions.map(dim => {
        // Generar un valor "ideal" cercano al valor real pero con alguna variación
        let ideal = dim.value + Math.floor(Math.random() * 20) - 10;
        // Asegurar que esté dentro del rango 0-100
        ideal = Math.min(100, Math.max(0, ideal));
        return {
            name: dim.name,
            value: dim.value,
            ideal: ideal,
            diff: Math.abs(dim.value - ideal)
        };
    });
    
    // Encontrar fortalezas (diferencia <= 10%)
    const strengths = comparisonData
        .filter(dim => dim.diff <= 10)
        .sort((a, b) => a.diff - b.diff);
    
    // Encontrar brechas (diferencia > 15%)
    const gaps = comparisonData
        .filter(dim => dim.diff > 15)
        .sort((a, b) => b.diff - a.diff);
    
    // Añadir fortalezas a la lista
    if (strengths.length > 0) {
        strengths.forEach(strength => {
            const li = document.createElement('li');
            li.innerHTML = `<strong>${strength.name}</strong>: Candidato ${strength.value}% - Ideal ${strength.ideal}%`;
            strengthsList.appendChild(li);
        });
    } else {
        const li = document.createElement('li');
        li.textContent = 'No se encontraron áreas de alto ajuste';
        strengthsList.appendChild(li);
    }
    
    // Añadir brechas a la lista
    if (gaps.length > 0) {
        gaps.forEach(gap => {
            const li = document.createElement('li');
            li.innerHTML = `<strong>${gap.name}</strong>: Candidato ${gap.value}% - Ideal ${gap.ideal}% <span class="text-danger">(Diferencia: ${gap.diff}%)</span>`;
            gapsList.appendChild(li);
        });
    } else {
        const li = document.createElement('li');
        li.textContent = 'No se encontraron brechas significativas';
        gapsList.appendChild(li);
    }
}

/**
 * Actualiza las recomendaciones basadas en el ajuste al perfil
 * @param {string} profileId - ID del perfil
 * @param {string} profileName - Nombre del perfil
 */
function updateFitRecommendations(profileId, profileName) {
    const recsContainer = document.getElementById('fitRecommendations');
    if (!recsContainer) return;
    
    // Obtener puntuación de ajuste del gauge
    const fitScore = parseFloat(document.getElementById('fitGaugeValue').textContent);
    
    // Limpiar contenedor
    recsContainer.innerHTML = '';
    
    // Generar recomendaciones basadas en la puntuación
    let recommendations = `<p>Basándose en el análisis de ajuste al perfil <strong>${profileName}</strong>, se recomienda:</p><ul>`;
    
    // Recomendaciones según el nivel de ajuste
    if (fitScore >= 80) {
        recommendations += `
            <li>Considerar al candidato como altamente adecuado para la posición</li>
            <li>Evaluar la compatibilidad cultural con el equipo actual</li>
            <li>Verificar expectativas salariales y de desarrollo profesional</li>
        `;
    } else if (fitScore >= 65) {
        recommendations += `
            <li>Considerar al candidato como una opción viable para la posición</li>
            <li>Realizar entrevistas adicionales enfocadas en las áreas de desarrollo identificadas</li>
            <li>Evaluar la disposición del candidato para formación complementaria</li>
        `;
    } else if (fitScore >= 50) {
        recommendations += `
            <li>Evaluar si las áreas de desarrollo pueden ser compensadas con formación específica</li>
            <li>Considerar el potencial de crecimiento del candidato a mediano plazo</li>
            <li>Valorar otras opciones que presenten mejor ajuste al perfil requerido</li>
        `;
    } else {
        recommendations += `
            <li>El candidato no presenta un ajuste adecuado para este perfil específico</li>
            <li>Considerar al candidato para otras posiciones más alineadas con su perfil</li>
            <li>Mantener en la base de datos para futuras oportunidades más adecuadas</li>
        `;
    }
    
    recommendations += '</ul>';
    recsContainer.innerHTML = recommendations;
}

/**
 * Recopila datos de dimensiones que aparecen en la página
 * @returns {Array} Array de objetos {name, value}
 */
function collectDimensionsFromPage() {
    const dimensions = [];
    
    // Buscar en varios contenedores donde podrían estar las dimensiones
    // 1. Buscar en tabla de resultados
    const resultRows = document.querySelectorAll('.results-table tbody tr');
    resultRows.forEach(row => {
        const cols = row.querySelectorAll('td');
        if (cols.length >= 2) {
            const name = cols[0].textContent.trim();
            // Extraer números del texto
            const valueText = cols[1].textContent.trim();
            const valueMatch = valueText.match(/\d+/);
            const value = valueMatch ? parseInt(valueMatch[0]) : 0;
            
            if (name && value > 0) {
                dimensions.push({ name, value });
            }
        }
    });
    
    // 2. Buscar en tarjetas de detalles cognitivos
    document.querySelectorAll('#cognitiveDetailsCards .card').forEach(card => {
        const nameEl = card.querySelector('.h5');
        const valueEl = card.querySelector('.progress-bar');
        
        if (nameEl && valueEl) {
            const name = nameEl.textContent.trim();
            let value = 0;
            
            // Intentar obtener valor del aria-valuenow
            if (valueEl.hasAttribute('aria-valuenow')) {
                value = parseInt(valueEl.getAttribute('aria-valuenow'));
            } 
            // Si no está disponible, intentar extraerlo del ancho
            else if (valueEl.style.width) {
                const widthMatch = valueEl.style.width.match(/\d+/);
                value = widthMatch ? parseInt(widthMatch[0]) : 0;
            }
            
            if (name && value > 0) {
                dimensions.push({ name, value });
            }
        }
    });
    
    // 3. Buscar en acordeón de personalidad
    document.querySelectorAll('#personalityDetailsAccordion .card').forEach(card => {
        const nameEl = card.querySelector('.btn-link');
        const valueEl = card.querySelector('.badge');
        
        if (nameEl && valueEl) {
            const name = nameEl.textContent.trim();
            // Extraer número del texto
            const valueText = valueEl.textContent.trim();
            const valueMatch = valueText.match(/\d+/);
            const value = valueMatch ? parseInt(valueMatch[0]) : 0;
            
            if (name && value > 0) {
                dimensions.push({ name, value });
            }
        }
    });
    
    // 4. Buscar en tarjetas de competencias
    document.querySelectorAll('#competencyDetailsCards .card').forEach(card => {
        const nameEl = card.querySelector('.card-header h6');
        const valueEl = card.querySelector('.competency-value');
        
        if (nameEl && valueEl) {
            const name = nameEl.textContent.trim();
            // Extraer número del texto
            const valueText = valueEl.textContent.trim();
            const valueMatch = valueText.match(/\d+/);
            const value = valueMatch ? parseInt(valueMatch[0]) : 0;
            
            if (name && value > 0) {
                dimensions.push({ name, value });
            }
        }
    });
    
    // Si no se encontraron dimensiones, retornar array vacío
    return dimensions;
}

/**
 * Recopila dimensiones por categoría
 * @param {string} category - Categoría a filtrar
 * @returns {Array} Array de objetos {name, value}
 */
function collectDimensionsByCategory(category) {
    // En una implementación real, esta información vendría del backend
    // Aquí simulamos filtrando por nombres típicos
    
    const allDimensions = collectDimensionsFromPage();
    let filteredDimensions = [];
    
    switch (category) {
        case 'cognitiva':
            // Filtrar dimensiones cognitivas por nombre típico
            filteredDimensions = allDimensions.filter(dim => 
                dim.name.toLowerCase().includes('razonamiento') || 
                dim.name.toLowerCase().includes('atención') ||
                dim.name.toLowerCase().includes('verbal') ||
                dim.name.toLowerCase().includes('numérico') ||
                dim.name.toLowerCase().includes('lógico') ||
                dim.name.toLowerCase().includes('espacial')
            );
            break;
            
        case 'personalidad':
            // Filtrar dimensiones de personalidad por nombre típico
            filteredDimensions = allDimensions.filter(dim => 
                dim.name.toLowerCase().includes('extroversión') || 
                dim.name.toLowerCase().includes('apertura') ||
                dim.name.toLowerCase().includes('estabilidad') ||
                dim.name.toLowerCase().includes('cooperación') ||
                dim.name.toLowerCase().includes('meticulosidad')
            );
            break;
            
        case 'competencia':
            // Filtrar competencias por nombre típico
            filteredDimensions = allDimensions.filter(dim => 
                dim.name.toLowerCase().includes('comunicación') || 
                dim.name.toLowerCase().includes('liderazgo') ||
                dim.name.toLowerCase().includes('trabajo en equipo') ||
                dim.name.toLowerCase().includes('responsabilidad') ||
                dim.name.toLowerCase().includes('adaptabilidad') ||
                dim.name.toLowerCase().includes('orientación a resultados') ||
                dim.name.toLowerCase().includes('integridad')
            );
            break;
    }
    
    return filteredDimensions;
}

/**
 * Recopila información de motivaciones
 * @returns {Array} Array de objetos {name, value}
 */
function collectMotivationsFromPage() {
    const motivations = [];
    
    // Buscar en el núcleo motivacional
    document.querySelectorAll('.core-motivation').forEach(item => {
        const titleEl = item.querySelector('.motivation-title');
        const progressBar = item.querySelector('.progress-bar');
        
        if (titleEl && progressBar) {
            // Extraer nombre sin el número del badge
            const fullTitle = titleEl.textContent.trim();
            const name = fullTitle.replace(/^\d+\s+/, '').trim();
            
            // Extraer valor del ancho de la barra de progreso
            let value = 0;
            if (progressBar.style.width) {
                const match = progressBar.style.width.match(/\d+/);
                value = match ? parseInt(match[0]) : 0;
            } else if (progressBar.hasAttribute('aria-valuenow')) {
                value = parseInt(progressBar.getAttribute('aria-valuenow'));
            }
            
            if (name && value > 0) {
                motivations.push({ name, value });
            }
        }
    });
    
    // Si hay menos de 3 motivaciones, añadir algunas adicionales de ejemplo
    if (motivations.length < 3) {
        // Nombres típicos de motivaciones que no hayamos encontrado
        const commonMotivations = ['Logro', 'Poder', 'Afiliación', 'Seguridad', 'Autonomía', 'Servicio', 'Reto', 'Equilibrio'];
        
        // Filtrar las que ya tenemos
        const existingNames = motivations.map(m => m.name);
        const missingMotivations = commonMotivations.filter(m => !existingNames.includes(m));
        
        // Añadir motivaciones faltantes con valores aleatorios
        missingMotivations.forEach((name, index) => {
            // Solo añadir hasta completar 7 motivaciones en total
            if (motivations.length < 7) {
                // Valor aleatorio entre 30 y 90
                const value = Math.floor(Math.random() * 60) + 30;
                motivations.push({ name, value });
            }
        });
    }
    
    return motivations;
}