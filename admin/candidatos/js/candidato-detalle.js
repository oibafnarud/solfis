/**
 * JavaScript para el perfil de candidato
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded');
    
    // Reubicar los tabs si están fuera de orden
    reorderTabs();
    
    // Gestión de pestañas
    setupTabs();
    
    // Inicializar gráficos si Chart.js está disponible
    if (typeof Chart !== 'undefined') {
        initializeCharts();
    } else {
        console.warn('Chart.js no está disponible');
    }
    
    // Animación de barras de dimensiones
    animateBars();
    
    // Configuración de modales
    setupModals();
    
    // Configuración de impresión
    setupPrintMode();
    
    // Debug info
    printDebugInfo();
});

/**
 * Reordena los tabs si están fuera de posición
 */
function reorderTabs() {
    const tabsContainer = document.querySelector('.tabs-container');
    const profileHeader = document.querySelector('.profile-header');
    
    // Si los tabs existen y están después del primer tab-content, moverlos
    if (tabsContainer && profileHeader) {
        const firstTabContent = document.querySelector('.tab-content');
        if (firstTabContent && tabsContainer.compareDocumentPosition(firstTabContent) & Node.DOCUMENT_POSITION_PRECEDING) {
            console.log('Reordenando tabs...');
            const parent = profileHeader.parentNode;
            parent.insertBefore(tabsContainer, profileHeader.nextSibling);
        }
    }
}

/**
 * Configura la funcionalidad de las pestañas
 */
function setupTabs() {
    const tabs = document.querySelectorAll('.tab');
    const tabContents = document.querySelectorAll('.tab-content');
    
    console.log('Tabs encontrados:', tabs.length);
    console.log('Tab contents encontrados:', tabContents.length);
    
    if (tabs.length > 0 && tabContents.length > 0) {
        tabs.forEach(tab => {
            tab.addEventListener('click', function() {
                const tabId = this.getAttribute('data-tab');
                console.log('Tab clicked:', tabId);
                
                // Actualizar clases activas en tabs
                tabs.forEach(t => t.classList.remove('active'));
                this.classList.add('active');
                
                // Actualizar contenido visible
                tabContents.forEach(content => {
                    content.classList.remove('active');
                    if (content.id === tabId) {
                        content.classList.add('active');
                    }
                });
                
                // Actualizar URL sin recargar página
                const candidatoId = getQueryParam('id');
                if (candidatoId) {
                    history.pushState(null, null, '?id=' + candidatoId + '&tab=' + tabId);
                } else {
                    history.pushState(null, null, '?tab=' + tabId);
                }
            });
        });
        
        // Si hay un parámetro de tab en la URL, activar esa pestaña
        const urlParams = new URLSearchParams(window.location.search);
        const tabParam = urlParams.get('tab');
        if (tabParam) {
            const tab = document.querySelector(`.tab[data-tab="${tabParam}"]`);
            if (tab) {
                tab.click();
            } else {
                console.warn('Tab no encontrado:', tabParam);
                // Si no se encuentra la pestaña solicitada, activar la primera
                tabs[0].click();
            }
        } else {
            // Si no hay parámetro, asegurarse de que la primera pestaña está activa
            // y su contenido también
            const firstTab = tabs[0];
            const firstTabId = firstTab.getAttribute('data-tab');
            const firstTabContent = document.getElementById(firstTabId);
            
            firstTab.classList.add('active');
            if (firstTabContent) {
                tabContents.forEach(content => content.classList.remove('active'));
                firstTabContent.classList.add('active');
            }
        }
    } else {
        console.error('No se encontraron pestañas o contenido de pestañas');
    }
    
    // Enlaces a otras pestañas dentro del contenido
    document.querySelectorAll('.tab-link').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const tabId = this.getAttribute('data-tab');
            const tab = document.querySelector(`.tab[data-tab="${tabId}"]`);
            if (tab) {
                tab.click();
            }
        });
    });
}

/**
 * Configura los modales
 */
function setupModals() {
    // Modal para editar nota
    const editNoteModal = document.getElementById('editNoteModal');
    if (editNoteModal) {
        editNoteModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const id = button.getAttribute('data-id');
            const titulo = button.getAttribute('data-titulo');
            const contenido = button.getAttribute('data-contenido');
            
            document.getElementById('edit_nota_id').value = id;
            document.getElementById('edit_titulo').value = titulo;
            document.getElementById('edit_contenido').value = contenido;
        });
    }
    
    // Modal para eliminar nota
    const deleteNoteModal = document.getElementById('deleteNoteModal');
    if (deleteNoteModal) {
        deleteNoteModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const id = button.getAttribute('data-id');
            
            document.getElementById('delete_nota_id').value = id;
        });
    }
}

/**
 * Inicializa todos los gráficos necesarios
 */
function initializeCharts() {
    try {
        console.log('Inicializando gráficos...');
        
        // Gráfico de resultado general (donut)
        initScoreChart('scoreChart');
        initScoreChart('evaluationScoreChart');
        
        // Gráfico de áreas
        initAreasChart();
        
        // Gráfico de competencias
        initCompetenciasChart();
        
        // Gráfico de aptitudes
        initAptitudesChart();
        
        // Gráfico de motivaciones
        initMotivacionesChart();
    } catch (error) {
        console.error('Error al inicializar gráficos:', error);
    }
}

/**
 * Inicializa un gráfico de puntuación estilo donut
 */
function initScoreChart(elementId) {
    const scoreChartEl = document.getElementById(elementId);
    if (!scoreChartEl) {
        console.warn(`Elemento no encontrado: ${elementId}`);
        return;
    }
    
    try {
        // Encontrar el contenedor padre con la clase profile-section-body
        const container = scoreChartEl.closest('.profile-section-body');
        if (!container) {
            console.warn(`Contenedor no encontrado para: ${elementId}`);
            return;
        }
        
        // Obtener datos del elemento h3 o h2 con clase mb-0
        const scoreElement = container.querySelector('.mb-0');
        if (!scoreElement) {
            console.warn(`Elemento de puntuación no encontrado para: ${elementId}`);
            return;
        }
        
        // Extraer solo números del texto
        const scoreText = scoreElement.textContent.replace(/[^\d]/g, '');
        const score = parseInt(scoreText);
        
        if (isNaN(score)) {
            console.warn(`Puntuación no válida para: ${elementId}`);
            return;
        }
        
        console.log(`Puntuación para ${elementId}:`, score);
        
        let colorClass;
        if (score >= 75) {
            colorClass = '#198754'; // success
        } else if (score >= 60) {
            colorClass = '#0dcaf0'; // info
        } else {
            colorClass = '#ffc107'; // warning
        }
        
        const scoreChart = new Chart(scoreChartEl, {
            type: 'doughnut',
            data: {
                labels: ['Puntuación', 'Restante'],
                datasets: [{
                    data: [score, 100 - score],
                    backgroundColor: [
                        colorClass,
                        '#f8f9fa'
                    ],
                    borderWidth: 0
                }]
            },
            options: {
                cutout: '75%',
                responsive: true,
                maintainAspectRatio: true,
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
    } catch (error) {
        console.error(`Error al inicializar gráfico ${elementId}:`, error);
    }
}

/**
 * Inicializa el gráfico de áreas
 */
function initAreasChart() {
    const areasChartEl = document.getElementById('areasChart');
    if (!areasChartEl) {
        console.warn('Elemento no encontrado: areasChart');
        return;
    }
    
    try {
        // Obtener el contenedor de las barras de competencias
        const container = areasChartEl.closest('.profile-section-body');
        if (!container) {
            console.warn('Contenedor no encontrado para areasChart');
            return;
        }
        
        const areaRows = container.querySelectorAll('.competency-row');
        if (areaRows.length === 0) {
            console.warn('No se encontraron filas de competencias para areasChart');
            return;
        }
        
        const areas = [];
        const scores = [];
        
        areaRows.forEach(row => {
            const label = row.querySelector('.competency-label');
            const score = row.querySelector('.competency-score');
            
            if (label && score) {
                areas.push(label.textContent.trim());
                scores.push(parseInt(score.textContent));
            }
        });
        
        if (areas.length === 0 || scores.length === 0) {
            console.warn('No se pudieron extraer datos para areasChart');
            return;
        }
        
        console.log('Datos del gráfico de áreas:', { areas, scores });
        
        const areasChart = new Chart(areasChartEl, {
            type: 'bar',
            data: {
                labels: areas,
                datasets: [{
                    label: 'Compatibilidad (%)',
                    data: scores,
                    backgroundColor: [
                        'rgba(54, 162, 235, 0.7)',
                        'rgba(75, 192, 192, 0.7)',
                        'rgba(255, 159, 64, 0.7)',
                        'rgba(153, 102, 255, 0.7)',
                        'rgba(255, 99, 132, 0.7)'
                    ],
                    borderColor: [
                        'rgba(54, 162, 235, 1)',
                        'rgba(75, 192, 192, 1)',
                        'rgba(255, 159, 64, 1)',
                        'rgba(153, 102, 255, 1)',
                        'rgba(255, 99, 132, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100
                    }
                }
            }
        });
    } catch (error) {
        console.error('Error al inicializar gráfico de áreas:', error);
    }
}

/**
 * Inicializa el gráfico de competencias
 */
function initCompetenciasChart() {
    const competenciasChartEl = document.getElementById('competenciasChart');
    if (!competenciasChartEl) {
        console.warn('Elemento no encontrado: competenciasChart');
        return;
    }
    
    try {
        const competencias = document.getElementById('competencias');
        if (!competencias) {
            console.warn('Contenedor de competencias no encontrado');
            return;
        }
        
        const compRows = competencias.querySelectorAll('.competency-row');
        if (compRows.length === 0) {
            console.warn('No se encontraron filas de competencias');
            
            // Usar datos de ejemplo si no hay datos reales
            const dimensiones = ['Comunicación', 'Trabajo en Equipo', 'Liderazgo', 'Adaptabilidad', 'Resolución de Problemas'];
            const valores = [75, 82, 65, 78, 70];
            
            createCompetenciasChart(competenciasChartEl, dimensiones, valores);
            return;
        }
        
        const dimensiones = [];
        const valores = [];
        
        compRows.forEach(row => {
            const label = row.querySelector('.competency-label');
            const score = row.querySelector('.competency-score');
            
            if (label && score) {
                dimensiones.push(label.textContent.trim());
                valores.push(parseInt(score.textContent));
            }
        });
        
        if (dimensiones.length === 0 || valores.length === 0) {
            console.warn('No se pudieron extraer datos para competenciasChart');
            return;
        }
        
        createCompetenciasChart(competenciasChartEl, dimensiones, valores);
    } catch (error) {
        console.error('Error al inicializar gráfico de competencias:', error);
    }
}

function createCompetenciasChart(element, dimensiones, valores) {
    console.log('Datos del gráfico de competencias:', { dimensiones, valores });
    
    const competenciasChart = new Chart(element, {
        type: 'radar',
        data: {
            labels: dimensiones,
            datasets: [{
                label: 'Competencias',
                data: valores,
                backgroundColor: 'rgba(54, 162, 235, 0.2)',
                borderColor: 'rgb(54, 162, 235)',
                borderWidth: 2,
                pointBackgroundColor: 'rgb(54, 162, 235)',
                pointBorderColor: '#fff',
                pointHoverBackgroundColor: '#fff',
                pointHoverBorderColor: 'rgb(54, 162, 235)'
            }]
        },
        options: {
            elements: {
                line: {
                    tension: 0.1
                }
            },
            scales: {
                r: {
                    angleLines: {
                        display: true
                    },
                    suggestedMin: 0,
                    suggestedMax: 100
                }
            }
        }
    });
}

/**
 * Inicializa el gráfico de aptitudes
 */
function initAptitudesChart() {
    const aptitudesChartEl = document.getElementById('aptitudesChart');
    if (!aptitudesChartEl) {
        console.warn('Elemento no encontrado: aptitudesChart');
        return;
    }
    
    try {
        // Intentar obtener datos reales
        const aptitudes = document.getElementById('aptitudes');
        const compRows = aptitudes ? aptitudes.querySelectorAll('.competency-row') : [];
        
        let etiquetas = [];
        let valores = [];
        
        if (compRows.length > 0) {
            compRows.forEach(row => {
                const label = row.querySelector('.competency-label');
                const score = row.querySelector('.competency-score');
                
                if (label && score) {
                    etiquetas.push(label.textContent.trim());
                    valores.push(parseInt(score.textContent));
                }
            });
        }
        
        // Si no hay datos reales, usar datos de ejemplo
        if (etiquetas.length === 0 || valores.length === 0) {
            console.log('Usando datos de ejemplo para aptitudesChart');
            etiquetas = ['Razonamiento Verbal', 'Razonamiento Numérico', 'Razonamiento Lógico', 'Atención al Detalle'];
            valores = [82, 65, 68, 73];
        }
        
        console.log('Datos del gráfico de aptitudes:', { etiquetas, valores });
        
        const aptitudesChart = new Chart(aptitudesChartEl, {
            type: 'bar',
            data: {
                labels: etiquetas,
                datasets: [{
                    label: 'Aptitudes (%)',
                    data: valores,
                    backgroundColor: [
                        'rgba(54, 162, 235, 0.7)',
                        'rgba(75, 192, 192, 0.7)',
                        'rgba(255, 159, 64, 0.7)',
                        'rgba(153, 102, 255, 0.7)'
                    ],
                    borderColor: [
                        'rgba(54, 162, 235, 1)',
                        'rgba(75, 192, 192, 1)',
                        'rgba(255, 159, 64, 1)',
                        'rgba(153, 102, 255, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100
                    }
                }
            }
        });
    } catch (error) {
        console.error('Error al inicializar gráfico de aptitudes:', error);
    }
}

/**
 * Inicializa el gráfico de motivaciones
 */
function initMotivacionesChart() {
    const motivacionesChartEl = document.getElementById('motivacionesChart');
    if (!motivacionesChartEl) {
        console.warn('Elemento no encontrado: motivacionesChart');
        return;
    }
    
    try {
        // Intentar obtener datos reales
        const motivaciones = [];
        const valores = [];
        
        // Buscar rows de motivaciones
        const motivacionRows = document.querySelectorAll('.motivation-row');
        if (motivacionRows.length > 0) {
            motivacionRows.forEach(row => {
                const label = row.querySelector('.motivation-label');
                const score = row.querySelector('.motivation-score');
                
                if (label && score) {
                    motivaciones.push(label.textContent.trim());
                    valores.push(parseInt(score.textContent));
                }
            });
        }
        
        // Si no hay datos, usar ejemplo
        if (motivaciones.length === 0 || valores.length === 0) {
            console.log('Usando datos de ejemplo para motivacionesChart');
            const etiquetas = [
                'Servicio/Contribución', 
                'Afiliación/Relaciones', 
                'Logro', 
                'Equilibrio vida-trabajo',
                'Reto/Desafío',
                'Autonomía',
                'Seguridad',
                'Poder'
            ];
            const datos = [9, 8, 7, 6, 4, 3, 2, 1];
            
            createMotivacionesChart(motivacionesChartEl, etiquetas, datos);
        } else {
            createMotivacionesChart(motivacionesChartEl, motivaciones, valores);
        }
    } catch (error) {
        console.error('Error al inicializar gráfico de motivaciones:', error);
    }
}

function createMotivacionesChart(element, etiquetas, datos) {
    console.log('Datos del gráfico de motivaciones:', { etiquetas, datos });
    
    const motivacionesChart = new Chart(element, {
        type: 'polarArea',
        data: {
            labels: etiquetas,
            datasets: [{
                data: datos,
                backgroundColor: [
                    'rgba(54, 162, 235, 0.7)',
                    'rgba(75, 192, 192, 0.7)',
                    'rgba(255, 159, 64, 0.7)',
                    'rgba(153, 102, 255, 0.7)',
                    'rgba(255, 99, 132, 0.7)',
                    'rgba(255, 205, 86, 0.7)',
                    'rgba(201, 203, 207, 0.7)',
                    'rgba(54, 162, 235, 0.4)'
                ]
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'right',
                }
            }
        }
    });
}

/**
 * Anima las barras de dimensiones con una transición suave
 */
function animateBars() {
    try {
        const dimensionBars = document.querySelectorAll('.dimension-progress');
        console.log('Animando barras de dimensiones:', dimensionBars.length);
        
        dimensionBars.forEach(bar => {
            // Guardar el ancho original
            const width = bar.getAttribute('style') ? 
                          bar.style.width : 
                          window.getComputedStyle(bar).width;
            
            // Resetear a 0
            bar.style.width = '0';
            
            // Después de un breve retraso, animar al ancho original
            setTimeout(() => {
                bar.style.width = width;
            }, 300);
        });
    } catch (error) {
        console.error('Error al animar barras:', error);
    }
}

/**
 * Configura el modo de impresión
 */
function setupPrintMode() {
    window.onbeforeprint = function() {
        try {
            // Ajustar estilos para impresión
            document.querySelectorAll('.container-fluid, .profile-section, .profile-header').forEach(el => {
                el.style.boxShadow = 'none';
                el.style.borderRadius = '0';
            });
            
            // Ocultar elementos innecesarios para impresión
            document.querySelectorAll('.no-print').forEach(el => {
                el.style.display = 'none';
            });
        } catch (error) {
            console.error('Error al configurar impresión:', error);
        }
    };
    
    window.onafterprint = function() {
        // Restaurar estilos después de imprimir
        location.reload();
    };
}

/**
 * Imprime información de depuración
 */
function printDebugInfo() {
    console.log("-------------- DEBUG INFO --------------");
    console.log("Tabs encontrados:", document.querySelectorAll('.tab').length);
    console.log("Contenidos de tabs encontrados:", document.querySelectorAll('.tab-content').length);
    
    // Listar los IDs de los contenidos de pestañas
    const tabContents = document.querySelectorAll('.tab-content');
    console.log("IDs de contenidos de tabs:");
    tabContents.forEach(content => {
        console.log("- " + content.id);
    });
    
    // Verificar que los data-tab de los tabs coinciden con los IDs de los contenidos
    const tabs = document.querySelectorAll('.tab');
    console.log("Valores data-tab de los tabs:");
    tabs.forEach(tab => {
        console.log("- " + tab.getAttribute('data-tab'));
    });
    
    console.log("--------------------------------------");
}

// Función auxiliar para obtener parámetros de URL
function getQueryParam(param) {
    const urlParams = new URLSearchParams(window.location.search);
    return urlParams.get(param);
}