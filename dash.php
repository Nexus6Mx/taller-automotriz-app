<!DOCTYPE html>
<html lang="es" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Ejecutivo - ERR Automotriz</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        body { 
            font-family: 'Inter', sans-serif; 
            background-color: #0f172a; /* slate-900 */
        }
        .card {
            background-color: #1e293b; /* slate-800 */
            border: 1px solid #334155; /* slate-700 */
            border-radius: 0.75rem;
            padding: 1.5rem;
        }
        .card-title {
            color: #f1f5f9; /* slate-100 */
            font-size: 0.875rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 1rem;
        }
        .metric-value {
            color: #ffffff;
            font-size: 2.5rem;
            font-weight: 800;
            line-height: 1;
        }
        .metric-label {
            color: #94a3b8; /* slate-400 */
            font-size: 0.875rem;
            margin-top: 0.5rem;
        }
        .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 6px;
        }
        .dot-green { background-color: #10b981; }
        .dot-blue { background-color: #3b82f6; }
        .dot-orange { background-color: #f59e0b; }
        .dot-red { background-color: #ef4444; }
        
        /* Loading animation */
        .loading-pulse {
            animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: .5; }
        }
        
        /* Auto-update indicator */
        .update-indicator {
            position: fixed;
            top: 1rem;
            right: 1rem;
            padding: 0.5rem 1rem;
            background: #1e293b;
            border: 1px solid #334155;
            border-radius: 0.5rem;
            color: #94a3b8;
            font-size: 0.75rem;
            z-index: 1000;
        }
        .update-indicator.updating {
            border-color: #10b981;
            color: #10b981;
        }
    </style>
</head>
<body class="bg-slate-900 text-white p-6">
    
    <!-- Indicador de Auto-actualización -->
    <div id="update-indicator" class="update-indicator">
        <span id="update-text">Última actualización: Cargando...</span>
    </div>

    <!-- Header -->
    <header class="mb-8">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-bold text-white">Dashboard Ejecutivo</h1>
                <p class="text-slate-400 mt-1">Monitoreo en Tiempo Real - ERR Automotriz</p>
            </div>
            <div class="text-right">
                <div class="text-2xl font-bold text-emerald-400" id="current-time"></div>
                <div class="text-slate-400 text-sm" id="current-date"></div>
            </div>
        </div>
    </header>

    <!-- Grid de Métricas -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6" id="metrics-container">
        
        <!-- Proyección Financiera -->
        <div class="card col-span-1 md:col-span-2">
            <div class="flex justify-between items-start mb-4">
                <h2 class="card-title">Proyección Financiera - <span id="current-month"></span></h2>
                <button class="text-slate-400 hover:text-white">⋮</button>
            </div>
            <div class="flex items-center justify-between">
                <div>
                    <div class="metric-value text-emerald-400" id="total-facturado">$0</div>
                    <div class="metric-label">Facturado este mes</div>
                    <div class="mt-4">
                        <div class="text-sm text-slate-400">Meta: <span id="meta-mensual">$0</span></div>
                        <div class="w-full bg-slate-700 rounded-full h-2 mt-2">
                            <div id="progress-meta" class="bg-emerald-500 h-2 rounded-full transition-all duration-500" style="width: 0%"></div>
                        </div>
                        <div class="text-xs text-slate-500 mt-1"><span id="porcentaje-meta">0</span>% completado</div>
                    </div>
                </div>
                <div class="relative w-32 h-32">
                    <canvas id="financial-donut"></canvas>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4 mt-6 pt-4 border-t border-slate-700">
                <div>
                    <div class="text-2xl font-bold text-white" id="total-ordenes-mes">0</div>
                    <div class="text-sm text-slate-400">Órdenes este mes</div>
                </div>
                <div>
                    <div class="text-2xl font-bold text-white" id="ticket-promedio">$0</div>
                    <div class="text-sm text-slate-400">Ticket Promedio</div>
                </div>
            </div>
        </div>

        <!-- Facturación Últimos 30 Días -->
        <div class="card col-span-1 md:col-span-2">
            <h2 class="card-title">Facturación por Día (Últimos 30 días)</h2>
            <canvas id="daily-sales-chart" height="100"></canvas>
        </div>

        <!-- TOP 5 Mayores Ventas -->
        <div class="card col-span-1 md:col-span-2">
            <h2 class="card-title">Mayores Ventas (Últimos 90 días)</h2>
            <div id="highest-sales-list" class="space-y-3">
                <!-- Se llenará dinámicamente -->
            </div>
        </div>

        <!-- TOP 5 Menores Ventas -->
        <div class="card col-span-1 md:col-span-2">
            <h2 class="card-title">Menores Ventas (Últimos 90 días)</h2>
            <div id="lowest-sales-list" class="space-y-3">
                <!-- Se llenará dinámicamente -->
            </div>
        </div>

        <!-- Distribución por Estado -->
        <div class="card">
            <h2 class="card-title">Órdenes por Estado (30 días)</h2>
            <canvas id="status-distribution-chart"></canvas>
        </div>

        <!-- Facturación Mensual -->
        <div class="card col-span-1 md:col-span-2 lg:col-span-3">
            <h2 class="card-title">Facturación Mensual (Últimos 12 meses)</h2>
            <canvas id="monthly-sales-chart" height="80"></canvas>
        </div>

        <!-- TOP 10 Clientes -->
        <div class="card col-span-1 md:col-span-2">
            <h2 class="card-title">Top 10 Clientes (Últimos 90 días)</h2>
            <canvas id="top-clients-chart" height="150"></canvas>
        </div>

        <!-- Estadísticas Generales -->
        <div class="card col-span-1 md:col-span-2">
            <h2 class="card-title">Estadísticas Históricas</h2>
            <div class="grid grid-cols-2 gap-6 mt-4">
                <div>
                    <div class="metric-value text-blue-400" id="total-historico">$0</div>
                    <div class="metric-label">Total Histórico Facturado</div>
                </div>
                <div>
                    <div class="metric-value text-cyan-400" id="ordenes-historico">0</div>
                    <div class="metric-label">Órdenes Totales</div>
                </div>
            </div>
        </div>

    </div>

    <script src="/assets/js/app.js"></script>
    <script>
        // Configuración
        const AUTO_UPDATE_INTERVAL = 5 * 60 * 1000; // 5 minutos en milisegundos
        let charts = {};
        
        // Verificar autenticación
        const token = localStorage.getItem('authToken');
        if (!token) {
            alert('Debes iniciar sesión primero');
            window.location.href = '/login.html';
        }

        // Actualizar fecha y hora
        function updateDateTime() {
            const now = new Date();
            document.getElementById('current-time').textContent = now.toLocaleTimeString('es-MX', { 
                hour: '2-digit', 
                minute: '2-digit' 
            });
            document.getElementById('current-date').textContent = now.toLocaleDateString('es-MX', { 
                weekday: 'long', 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric' 
            });
        }
        setInterval(updateDateTime, 1000);
        updateDateTime();

        // Formatear moneda
        function formatCurrency(value) {
            return new Intl.NumberFormat('es-MX', { 
                style: 'currency', 
                currency: 'MXN' 
            }).format(value);
        }

        // Cargar métricas
        async function loadMetrics() {
            const indicator = document.getElementById('update-indicator');
            const updateText = document.getElementById('update-text');
            
            try {
                indicator.classList.add('updating');
                updateText.textContent = 'Actualizando...';
                
                const response = await fetch('/api/dashboard/metrics.php', {
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Content-Type': 'application/json'
                    }
                });

                if (!response.ok) {
                    if (response.status === 401 || response.status === 403) {
                        alert('Sesión expirada o sin permisos');
                        window.location.href = '/login.html';
                        return;
                    }
                    throw new Error('Error al cargar métricas');
                }

                const data = await response.json();
                
                if (data.success) {
                    renderMetrics(data.metrics);
                    const lastUpdate = new Date(data.timestamp).toLocaleTimeString('es-MX');
                    updateText.textContent = `Última actualización: ${lastUpdate}`;
                } else {
                    throw new Error(data.message || 'Error desconocido');
                }
                
            } catch (error) {
                console.error('Error:', error);
                updateText.textContent = 'Error al actualizar';
            } finally {
                indicator.classList.remove('updating');
            }
        }

        // Renderizar métricas
        function renderMetrics(metrics) {
            // Proyección Financiera
            const fp = metrics.financial_projection;
            document.getElementById('current-month').textContent = fp.mes_actual;
            document.getElementById('total-facturado').textContent = formatCurrency(fp.total_facturado);
            document.getElementById('meta-mensual').textContent = formatCurrency(fp.meta_mensual);
            document.getElementById('porcentaje-meta').textContent = fp.porcentaje_meta;
            document.getElementById('progress-meta').style.width = `${Math.min(fp.porcentaje_meta, 100)}%`;
            document.getElementById('total-ordenes-mes').textContent = fp.total_ordenes;
            document.getElementById('ticket-promedio').textContent = formatCurrency(fp.ticket_promedio);
            
            // Gráfico de dona financiera
            renderFinancialDonut(fp.porcentaje_meta);
            
            // Facturación diaria
            renderDailySalesChart(metrics.daily_sales);
            
            // Mayores y menores ventas
            renderSalesList('highest-sales-list', metrics.highest_sales, true);
            renderSalesList('lowest-sales-list', metrics.lowest_sales, false);
            
            // Distribución por estado
            renderStatusChart(metrics.status_distribution);
            
            // Facturación mensual
            renderMonthlySalesChart(metrics.monthly_sales);
            
            // Top clientes
            renderTopClientsChart(metrics.top_clients);
            
            // Estadísticas generales
            document.getElementById('total-historico').textContent = formatCurrency(metrics.general_stats.total_historico);
            document.getElementById('ordenes-historico').textContent = metrics.general_stats.total_ordenes_historico.toLocaleString();
        }

        // Gráfico de dona financiera
        function renderFinancialDonut(percentage) {
            const ctx = document.getElementById('financial-donut').getContext('2d');
            
            if (charts.financialDonut) {
                charts.financialDonut.destroy();
            }
            
            charts.financialDonut = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    datasets: [{
                        data: [percentage, 100 - percentage],
                        backgroundColor: ['#10b981', '#334155'],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    cutout: '75%',
                    plugins: {
                        legend: { display: false },
                        tooltip: { enabled: false }
                    }
                },
                plugins: [{
                    afterDraw: (chart) => {
                        const ctx = chart.ctx;
                        const centerX = (chart.chartArea.left + chart.chartArea.right) / 2;
                        const centerY = (chart.chartArea.top + chart.chartArea.bottom) / 2;
                        
                        ctx.save();
                        ctx.font = 'bold 20px Inter';
                        ctx.fillStyle = '#10b981';
                        ctx.textAlign = 'center';
                        ctx.textBaseline = 'middle';
                        ctx.fillText(`${Math.round(percentage)}%`, centerX, centerY);
                        ctx.restore();
                    }
                }]
            });
        }

        // Gráfico de ventas diarias
        function renderDailySalesChart(dailySales) {
            const ctx = document.getElementById('daily-sales-chart').getContext('2d');
            
            if (charts.dailySales) {
                charts.dailySales.destroy();
            }
            
            const labels = dailySales.map(d => new Date(d.fecha).toLocaleDateString('es-MX', { month: 'short', day: 'numeric' }));
            const data = dailySales.map(d => d.total);
            
            charts.dailySales = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Facturación',
                        data: data,
                        borderColor: '#3b82f6',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4,
                        pointRadius: 3,
                        pointBackgroundColor: '#3b82f6'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: '#1e293b',
                            titleColor: '#f1f5f9',
                            bodyColor: '#cbd5e1',
                            borderColor: '#334155',
                            borderWidth: 1,
                            callbacks: {
                                label: (context) => formatCurrency(context.parsed.y)
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: { color: '#334155', drawBorder: false },
                            ticks: { 
                                color: '#94a3b8',
                                callback: (value) => '$' + (value / 1000).toFixed(0) + 'k'
                            }
                        },
                        x: {
                            grid: { display: false },
                            ticks: { color: '#94a3b8' }
                        }
                    }
                }
            });
        }

        // Lista de ventas (mayores/menores)
        function renderSalesList(containerId, sales, isHighest) {
            const container = document.getElementById(containerId);
            const color = isHighest ? 'emerald' : 'orange';
            
            container.innerHTML = sales.map((sale, index) => `
                <div class="flex items-center justify-between p-3 bg-slate-700/50 rounded-lg">
                    <div class="flex items-center gap-3 flex-1">
                        <div class="text-2xl font-bold text-${color}-400">${index + 1}</div>
                        <div class="flex-1">
                            <div class="text-white font-medium">${sale.cliente}</div>
                            <div class="text-slate-400 text-sm">Orden #${sale.orden_id} • ${sale.status}</div>
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="text-xl font-bold text-${color}-400">${formatCurrency(sale.total)}</div>
                    </div>
                </div>
            `).join('');
        }

        // Gráfico de distribución por estado
        function renderStatusChart(distribution) {
            const ctx = document.getElementById('status-distribution-chart').getContext('2d');
            
            if (charts.statusDistribution) {
                charts.statusDistribution.destroy();
            }
            
            const colors = ['#10b981', '#3b82f6', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899'];
            
            charts.statusDistribution = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: distribution.map(d => d.status),
                    datasets: [{
                        data: distribution.map(d => d.cantidad),
                        backgroundColor: colors.slice(0, distribution.length),
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: { color: '#94a3b8', padding: 10, font: { size: 11 } }
                        },
                        tooltip: {
                            backgroundColor: '#1e293b',
                            titleColor: '#f1f5f9',
                            bodyColor: '#cbd5e1',
                            borderColor: '#334155',
                            borderWidth: 1
                        }
                    }
                }
            });
        }

        // Gráfico de facturación mensual
        function renderMonthlySalesChart(monthlySales) {
            const ctx = document.getElementById('monthly-sales-chart').getContext('2d');
            
            if (charts.monthlySales) {
                charts.monthlySales.destroy();
            }
            
            const labels = monthlySales.map(m => {
                const [year, month] = m.mes.split('-');
                return new Date(year, month - 1).toLocaleDateString('es-MX', { month: 'short', year: '2-digit' });
            });
            const data = monthlySales.map(m => m.total);
            
            charts.monthlySales = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Facturación Mensual',
                        data: data,
                        backgroundColor: '#3b82f6',
                        borderRadius: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: '#1e293b',
                            titleColor: '#f1f5f9',
                            bodyColor: '#cbd5e1',
                            borderColor: '#334155',
                            borderWidth: 1,
                            callbacks: {
                                label: (context) => formatCurrency(context.parsed.y)
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: { color: '#334155', drawBorder: false },
                            ticks: { 
                                color: '#94a3b8',
                                callback: (value) => '$' + (value / 1000).toFixed(0) + 'k'
                            }
                        },
                        x: {
                            grid: { display: false },
                            ticks: { color: '#94a3b8' }
                        }
                    }
                }
            });
        }

        // Gráfico de top clientes
        function renderTopClientsChart(topClients) {
            const ctx = document.getElementById('top-clients-chart').getContext('2d');
            
            if (charts.topClients) {
                charts.topClients.destroy();
            }
            
            const labels = topClients.map(c => c.cliente.length > 20 ? c.cliente.substring(0, 20) + '...' : c.cliente);
            const data = topClients.map(c => c.total);
            
            charts.topClients = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Total Facturado',
                        data: data,
                        backgroundColor: '#10b981',
                        borderRadius: 4
                    }]
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: '#1e293b',
                            titleColor: '#f1f5f9',
                            bodyColor: '#cbd5e1',
                            borderColor: '#334155',
                            borderWidth: 1,
                            callbacks: {
                                label: (context) => formatCurrency(context.parsed.x)
                            }
                        }
                    },
                    scales: {
                        x: {
                            beginAtZero: true,
                            grid: { color: '#334155', drawBorder: false },
                            ticks: { 
                                color: '#94a3b8',
                                callback: (value) => '$' + (value / 1000).toFixed(0) + 'k'
                            }
                        },
                        y: {
                            grid: { display: false },
                            ticks: { color: '#94a3b8', font: { size: 11 } }
                        }
                    }
                }
            });
        }

        // Inicializar y configurar auto-actualización
        loadMetrics();
        setInterval(loadMetrics, AUTO_UPDATE_INTERVAL);
    </script>
</body>
</html>
