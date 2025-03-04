// admin.js
document.addEventListener('DOMContentLoaded', function() {
    // Inicialización del Dashboard
    const Dashboard = {
        init() {
            this.initCharts();
            this.setupEventListeners();
            this.initDataTables();
        },

        setupEventListeners() {
            // Toggle Sidebar en móvil
            const sidebarToggle = document.querySelector('.sidebar-toggle');
            const sidebar = document.querySelector('.admin-sidebar');
            
            sidebarToggle?.addEventListener('click', () => {
                sidebar?.classList.toggle('active');
            });

            // Cerrar sidebar al hacer click fuera
            document.addEventListener('click', (e) => {
                if (sidebar?.classList.contains('active') && 
                    !sidebar.contains(e.target) && 
                    !sidebarToggle.contains(e.target)) {
                    sidebar.classList.remove('active');
                }
            });

            // Manejo de acciones en la tabla
            document.querySelectorAll('.action-btn').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    const action = e.currentTarget.getAttribute('title').toLowerCase();
                    const row = e.currentTarget.closest('tr');
                    const itemId = row.dataset.id;
                    
                    if (action === 'eliminar') {
                        this.handleDelete(itemId, row);
                    } else if (action === 'editar') {
                        this.handleEdit(itemId);
                    }
                });
            });
        },

        initCharts() {
            const trafficCtx = document.getElementById('trafficChart');
            if (!trafficCtx) return;

            new Chart(trafficCtx, {
                type: 'line',
                data: {
                    labels: ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun'],
                    datasets: [{
                        label: 'Visitas',
                        data: [1500, 2000, 1800, 2200, 2400, 2100],
                        borderColor: '#2563eb',
                        backgroundColor: 'rgba(37, 99, 235, 0.1)',
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)'
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            });
        },

        initDataTables() {
            const tables = document.querySelectorAll('.admin-table');
            tables.forEach(table => {
                // Aquí se podría inicializar una librería de DataTables
                // Por ahora solo agregamos funcionalidad básica de ordenamiento
                this.makeTableSortable(table);
            });
        },

        makeTableSortable(table) {
            const headers = table.querySelectorAll('th');
            headers.forEach((header, index) => {
                if (header.classList.contains('no-sort')) return;

                header.addEventListener('click', () => {
                    this.sortTable(table, index);
                });
                header.style.cursor = 'pointer';
            });
        },

        sortTable(table, column) {
            const tbody = table.querySelector('tbody');
            const rows = Array.from(tbody.querySelectorAll('tr'));
            const isAsc = table.querySelectorAll('th')[column].classList.contains('asc');

            // Limpiar estados previos
            table.querySelectorAll('th').forEach(th => {
                th.classList.remove('asc', 'desc');
            });

            rows.sort((a, b) => {
                const aValue = a.cells[column].textContent;
                const bValue = b.cells[column].textContent;
                return isAsc ? 
                    bValue.localeCompare(aValue) : 
                    aValue.localeCompare(bValue);
            });

            // Actualizar estado de ordenamiento
            table.querySelectorAll('th')[column].classList.add(isAsc ? 'desc' : 'asc');

            // Limpiar y reagregar filas ordenadas
            while (tbody.firstChild) {
                tbody.removeChild(tbody.firstChild);
            }
            tbody.append(...rows);
        },

        async handleDelete(itemId, row) {
            if (confirm('¿Está seguro de que desea eliminar este elemento?')) {
                try {
                    // Aquí iría la llamada a la API
                    await this.deleteItem(itemId);
                    row.remove();
                    this.showNotification('Elemento eliminado con éxito', 'success');
                } catch (error) {
                    this.showNotification('Error al eliminar el elemento', 'error');
                }
            }
        },

        handleEdit(itemId) {
            window.location.href = `edit.html?id=${itemId}`;
        },

        async deleteItem(itemId) {
            // Simulación de llamada a API
            return new Promise((resolve) => {
                setTimeout(resolve, 500);
            });
        },

        showNotification(message, type = 'success') {
            const notification = document.createElement('div');
            notification.className = `notification notification-${type}`;
            notification.textContent = message;

            document.body.appendChild(notification);

            setTimeout(() => {
                notification.classList.add('show');
            }, 100);

            setTimeout(() => {
                notification.classList.remove('show');
                setTimeout(() => {
                    notification.remove();
                }, 300);
            }, 3000);
        }
    };

    // Inicializar Dashboard
    Dashboard.init();
});