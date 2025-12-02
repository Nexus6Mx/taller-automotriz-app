<!DOCTYPE html>
<html lang="es" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Ejecutivo - ERR Automotriz</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/gridstack@10.1.0/dist/gridstack.min.css">
    <style>
        body { 
            font-family: 'Inter', sans-serif; 
            background-color: #0f172a; /* slate-900 */
        }
        .card {
            background-color: #1e293b; /* slate-800 */
            border: 1px solid #334155; /* slate-700 */
            border-radius: 0.75rem;
            padding: 0.875rem;
            overflow: hidden; /* Prevenir desbordamiento */
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            height: 100%;
        }
        .card-content {
            flex: 1;
            min-height: 0;
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }
        .card-scroll-area {
            flex: 1;
            min-height: 0;
            overflow-y: auto;
        }
        .card-footer {
            flex-shrink: 0;
        }
        .chart-container,
        .chart-container-small {
            position: relative;
            width: 100%;
            flex: 1;
            min-height: 160px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .chart-container-small {
            min-height: 140px;
        }
        .chart-container canvas,
        .chart-container-small canvas,
        .donut-container canvas {
            width: 100% !important;
            height: 100% !important;
        }
        .donut-container {
            position: relative;
            width: 100%;
            max-width: 240px;
            margin: 0 auto;
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            aspect-ratio: 1;
            min-height: 160px;
        }
        .card-title {
            color: #f1f5f9; /* slate-100 */
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 0.75rem;
            flex-shrink: 0;
        }
        .metric-value {
            color: #ffffff;
            font-size: 1.875rem;
            font-weight: 800;
            line-height: 1;
        }
        .metric-label {
            color: #94a3b8; /* slate-400 */
            font-size: 0.8125rem;
            margin-top: 0.25rem;
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
        
        /* Drag and drop ghost element */
        .dragging-ghost {
            opacity: 0.4;
            background: #334155;
        }
        
        /* Make card titles look draggable */
        .card-title {
            cursor: move;
            user-select: none;
        }
        
        /* Custom scrollbar */
        #pending-orders-list::-webkit-scrollbar,
        .card-scroll-area::-webkit-scrollbar {
            width: 8px;
        }
        #pending-orders-list::-webkit-scrollbar-track,
        .card-scroll-area::-webkit-scrollbar-track {
            background: #1e293b;
            border-radius: 4px;
        }
        #pending-orders-list::-webkit-scrollbar-thumb,
        .card-scroll-area::-webkit-scrollbar-thumb {
            background: #475569;
            border-radius: 4px;
        }
        #pending-orders-list::-webkit-scrollbar-thumb:hover,
        .card-scroll-area::-webkit-scrollbar-thumb:hover {
            background: #64748b;
        }
    </style>
</head>
<body class="bg-slate-900 text-white p-4">
    
    <!-- Header -->
    <header class="mb-4">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-bold text-white">Dashboard Ejecutivo</h1>
                <p class="text-slate-400 text-sm mt-0.5">Monitoreo en Tiempo Real - ERR Automotriz</p>
            </div>
            <div class="text-right">
                <div class="text-xl font-bold text-emerald-400" id="current-time"></div>
                <div class="text-slate-400 text-xs" id="current-date"></div>
            </div>
        </div>
    </header>

    <!-- Grid de Métricas -->
    <div class="grid-stack" id="metrics-container">
        
        <!-- Proyección Financiera -->
        <div class="grid-stack-item" gs-id="card-proyeccion" gs-x="0" gs-y="0" gs-w="6" gs-h="2">
            <div class="grid-stack-item-content card" id="card-proyeccion">
                <div class="flex justify-between items-start">
                    <h2 class="card-title">Proyección Financiera - <span id="current-month"></span></h2>
                    <button class="text-slate-400 hover:text-white">⋮</button>
                </div>
                <div class="card-content">
                    <div class="flex flex-1 items-center justify-between gap-4 min-h-0">
                        <div class="flex-1 min-w-0">
                            <div class="metric-value text-emerald-400" id="total-facturado">$0</div>
                            <div class="metric-label">Facturado este mes</div>
                            <div class="mt-2 space-y-2">
                                <div class="text-xs text-slate-400">Meta: <span id="meta-mensual">$0</span></div>
                                <div>
                                    <div class="w-full bg-slate-700 rounded-full h-1.5">
                                        <div id="progress-meta" class="bg-emerald-500 h-1.5 rounded-full transition-all duration-500" style="width: 0%"></div>
                                    </div>
                                    <div class="text-xs text-slate-500 mt-1"><span id="porcentaje-meta">0</span>% completado</div>
                                </div>
                            </div>
                        </div>
                        <div class="donut-container">
                            <canvas id="financial-donut"></canvas>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3 pt-3 border-t border-slate-700 card-footer">
                        <div>
                            <div class="text-xl font-bold text-white" id="total-ordenes-mes">0</div>
                            <div class="text-xs text-slate-400">Órdenes este mes</div>
                        </div>
                        <div>
                            <div class="text-xl font-bold text-white" id="ticket-promedio">$0</div>
                            <div class="text-xs text-slate-400">Ticket Promedio</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Facturación Últimos 30 Días -->
        <div class="grid-stack-item" gs-id="card-facturacion-diaria" gs-x="6" gs-y="0" gs-w="6" gs-h="2">
            <div class="grid-stack-item-content card" id="card-facturacion-diaria">
                <h2 class="card-title">Facturación por Día (Últimos 30 días)</h2>
                <div class="chart-container">
                    <canvas id="daily-sales-chart"></canvas>
                </div>
            </div>
        </div>

        <!-- TOP 5 Mayores Ventas -->
        <div class="grid-stack-item" gs-id="card-mayores-ventas" gs-x="0" gs-y="2" gs-w="4" gs-h="2">
            <div class="grid-stack-item-content card" id="card-mayores-ventas">
                <h2 class="card-title">Mayores Ventas (Últimos 90 días)</h2>
                <div id="highest-sales-list" class="card-scroll-area space-y-1">
                    <!-- Se llenará dinámicamente -->
                </div>
            </div>
        </div>

        <!-- Órdenes Antiguas Pendientes -->
        <div class="grid-stack-item" gs-id="card-ordenes-antiguas" gs-x="4" gs-y="2" gs-w="4" gs-h="2">
            <div class="grid-stack-item-content card" id="card-ordenes-antiguas">
                <h2 class="card-title">Órdenes Antiguas Pendientes de Entrega</h2>
                <div id="pending-orders-list" class="card-scroll-area space-y-1">
                    <!-- Se llenará dinámicamente -->
                </div>
            </div>
        </div>

        <!-- Distribución por Estado -->
        <div class="grid-stack-item" gs-id="card-distribucion-estado" gs-x="8" gs-y="2" gs-w="4" gs-h="2">
            <div class="grid-stack-item-content card" id="card-distribucion-estado">
                <h2 class="card-title">Órdenes por Estado (30 días)</h2>
                <div class="chart-container-small">
                    <canvas id="status-distribution-chart"></canvas>
                </div>
            </div>
        </div>

        <!-- Facturación Mensual -->
        <div class="grid-stack-item" gs-id="card-facturacion-mensual" gs-x="0" gs-y="4" gs-w="12" gs-h="3">
            <div class="grid-stack-item-content card" id="card-facturacion-mensual">
                <h2 class="card-title">Facturación Mensual (Últimos 12 meses)</h2>
                <div class="chart-container">
                    <canvas id="monthly-sales-chart"></canvas>
                </div>
            </div>
        </div>

        <!-- Reporteador de Órdenes -->
        <div class="grid-stack-item" gs-id="card-order-report" gs-x="0" gs-y="7" gs-w="6" gs-h="3">
            <div class="grid-stack-item-content card" id="card-order-report">
                <h2 class="card-title">Reporteador de Órdenes</h2>
                <div class="card-content">
                    <div class="space-y-3 flex-shrink-0">
                        <div>
                            <label class="block text-xs font-medium text-slate-400 mb-1">Estados (selecciona uno o más):</label>
                            <div id="report-status-filters" class="flex flex-wrap gap-2"></div>
                        </div>
                    </div>

                    <div id="report-results-container" class="mt-4 pt-4 border-t border-slate-700 flex-1 flex flex-col min-h-0" style="display: none;">
                        <div id="report-results-list" class="card-scroll-area space-y-1"></div>

                        <div id="report-results-summary" class="mt-2 pt-2 border-t border-slate-700 flex justify-between items-center flex-shrink-0">
                            <div>
                                <span class="text-sm text-slate-400">Total Órdenes:</span>
                                <span id="report-total-ordenes" class="font-bold text-lg text-white ml-2">0</span>
                            </div>
                            <div>
                                <span class="text-sm text-slate-400">Monto Total:</span>
                                <span id="report-monto-total" class="font-bold text-lg text-emerald-400 ml-2">$0.00</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- TOP 10 Clientes -->
        <div class="grid-stack-item" gs-id="card-top-clientes" gs-x="6" gs-y="7" gs-w="3" gs-h="3">
            <div class="grid-stack-item-content card" id="card-top-clientes">
                <h2 class="card-title">Top 10 Clientes (Últimos 90 días)</h2>
                <div class="chart-container">
                    <canvas id="top-clients-chart"></canvas>
                </div>
            </div>
        </div>

        <!-- Estadísticas Generales -->
        <div class="grid-stack-item" gs-id="card-estadisticas" gs-x="9" gs-y="7" gs-w="3" gs-h="3">
            <div class="grid-stack-item-content card" id="card-estadisticas">
                <h2 class="card-title">Estadísticas Históricas</h2>
                <div class="card-content">
                    <div class="grid grid-cols-2 gap-4 flex-shrink-0">
                        <div class="flex items-baseline gap-2">
                            <div class="metric-label whitespace-nowrap">Total Histórico:</div>
                            <div class="metric-value text-blue-400 text-xl" id="total-historico">$0</div>
                        </div>
                        <div class="flex items-baseline gap-2">
                            <div class="metric-label whitespace-nowrap">Órdenes Totales:</div>
                            <div class="metric-value text-cyan-400 text-xl" id="ordenes-historico">0</div>
                        </div>
                    </div>
                    
                    <!-- Desglose por estatus -->
                    <div id="historical-status-list" class="card-scroll-area pt-3 border-t border-slate-700 space-y-1">
                        <!-- Se llenará dinámicamente -->
                    </div>
                </div>
            </div>
        </div>

    </div>

    <script src="/assets/js/app.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/gridstack@10.1.0/dist/gridstack-all.min.js"></script>
    <script>
        // Configuración
        const AUTO_UPDATE_INTERVAL = 5 * 60 * 1000; // 5 minutos en milisegundos
        const GRID_LAYOUT_KEY = 'dashboardLayoutV2';
        let charts = {};
        let gridInstance = null;
        
        // Verificar autenticación
        const token = localStorage.getItem('authToken');
        if (!token) {
            alert('Debes iniciar sesión primero');
            window.location.href = '/login.html';
        }

        // Inicializar GridStack para arrastre y redimensión
        function serializeGridLayout() {
            if (!gridInstance) return [];
            return (gridInstance.engine?.nodes || []).map(node => ({
                x: node.x,
                y: node.y,
                w: node.w,
                h: node.h,
                id: node.el?.getAttribute('gs-id')
            })).filter(item => item.id);
        }

        function loadSavedGridLayout(savedLayout) {
            if (!Array.isArray(savedLayout) || !gridInstance) return;
            const gridContainer = document.getElementById('metrics-container');
            gridInstance.batchUpdate();
            savedLayout.forEach(item => {
                if (!item || typeof item !== 'object') return;
                const nodeEl = gridContainer.querySelector(`[gs-id="${item.id}"]`);
                if (nodeEl) {
                    gridInstance.update(nodeEl, {
                        x: item.x ?? 0,
                        y: item.y ?? 0,
                        w: item.w ?? 3,
                        h: item.h ?? 2
                    });
                }
            });
            gridInstance.commit();
        }

        function resizeCharts() {
            Object.values(charts).forEach(chart => {
                if (chart && typeof chart.resize === 'function') {
                    chart.resize();
                }
            });
        }

        function initGridLayout() {
            const gridContainer = document.getElementById('metrics-container');
            if (!gridContainer || typeof GridStack === 'undefined') {
                return;
            }

            gridInstance = GridStack.init({
                column: 12,
                float: true,
                cellHeight: '150px',
                minRow: 1,
                handle: '.card-title',
                resizable: {
                    handles: 'se'
                }
            }, gridContainer);

            const savedLayoutRaw = localStorage.getItem(GRID_LAYOUT_KEY);
            if (savedLayoutRaw) {
                try {
                    const savedLayout = JSON.parse(savedLayoutRaw);
                    loadSavedGridLayout(savedLayout);
                } catch (error) {
                    console.warn('No se pudo cargar el layout guardado:', error);
                }
            }

            gridInstance.on('change', () => {
                try {
                    const serializedLayout = serializeGridLayout();
                    localStorage.setItem(GRID_LAYOUT_KEY, JSON.stringify(serializedLayout));
                } catch (error) {
                    console.error('No se pudo guardar el layout:', error);
                }
                requestAnimationFrame(resizeCharts);
            });

            gridInstance.on('resizestop', () => {
                requestAnimationFrame(resizeCharts);
            });

            gridInstance.on('dragstop', () => {
                requestAnimationFrame(resizeCharts);
            });

            requestAnimationFrame(resizeCharts);
        }

        // Actualizar fecha y hora
        function updateDateTime() {
            const now = new Date();
            // Convertir a zona horaria de México
            const options = { timeZone: 'America/Mexico_City' };
            document.getElementById('current-time').textContent = now.toLocaleTimeString('es-MX', { 
                hour: '2-digit', 
                minute: '2-digit',
                timeZone: 'America/Mexico_City'
            });
            document.getElementById('current-date').textContent = now.toLocaleDateString('es-MX', { 
                weekday: 'long', 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric',
                timeZone: 'America/Mexico_City'
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

        // --- INICIO: LÓGICA DEL REPORTEADOR DE ÓRDENES ---

        const REPORT_STATUSES = [
            'Recibido', 'Cotizado', 'Autorizado', 'En reparación',
            'Reparado', 'Listo para entregar', 'Entregado pagado', 'Cancelado'
        ];

        // Colores base por estatus (mismos que el resto del tablero)
        const STATUS_COLOR_HEX = {
            'Recibido': '#3B82F6',
            'Cotizado': '#6B7280',
            'Autorizado': '#EAB308',
            'En reparación': '#EAB308',
            'Reparado': '#06B6D4',
            'Listo para entregar': '#06B6D4',
            'Entregado pagado': '#22C55E',
            'Cancelado': '#EF4444',
            'Entregado pendiente de pago': '#EF4444'
        };

        const DEFAULT_STATUS_COLOR = '#475569';

        const hexToRgba = (hex, alpha = 1) => {
            const normalized = hex.replace('#', '');
            const bigint = parseInt(normalized, 16);
            if (Number.isNaN(bigint) || normalized.length !== 6) {
                return `rgba(71, 85, 105, ${alpha})`;
            }
            const r = (bigint >> 16) & 255;
            const g = (bigint >> 8) & 255;
            const b = bigint & 255;
            return `rgba(${r}, ${g}, ${b}, ${alpha})`;
        };

        const getStatusColor = (status) => STATUS_COLOR_HEX[status] || DEFAULT_STATUS_COLOR;

        const applyStatusButtonStyle = (button, isActive) => {
            const status = button.dataset.status;
            const baseColor = getStatusColor(status);
            const bgAlpha = isActive ? 0.4 : 0.2;
            button.style.backgroundColor = hexToRgba(baseColor, bgAlpha);
            button.style.color = '#f8fafc';
            button.style.borderColor = isActive ? hexToRgba(baseColor, 0.9) : hexToRgba(baseColor, 0.35);
            button.style.boxShadow = isActive ? `0 0 0 1px ${hexToRgba(baseColor, 0.7)}` : 'none';
        };

        const statusFiltersContainer = document.getElementById('report-status-filters');
        const resultsContainer = document.getElementById('report-results-container');
        const resultsList = document.getElementById('report-results-list');
        const totalOrdenesEl = document.getElementById('report-total-ordenes');
        const montoTotalEl = document.getElementById('report-monto-total');

        if (statusFiltersContainer) {
            REPORT_STATUSES.forEach(status => {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'report-status-toggle rounded-full px-3 py-1 text-xs font-semibold cursor-pointer transition duration-150 ease-in-out border flex items-center gap-1';
                button.dataset.status = status;
                button.textContent = status;
                applyStatusButtonStyle(button, false);
                statusFiltersContainer.appendChild(button);
            });

            statusFiltersContainer.addEventListener('click', (e) => {
                const target = e.target.closest('.report-status-toggle');
                if (!target) return;

                target.classList.toggle('active');
                applyStatusButtonStyle(target, target.classList.contains('active'));

                runOrderReport();
            });
        }

        async function runOrderReport() {
            if (!statusFiltersContainer || !resultsContainer || !resultsList || !totalOrdenesEl || !montoTotalEl) {
                return;
            }

            const activeStatusButtons = statusFiltersContainer.querySelectorAll('.report-status-toggle.active');
            const selectedStatuses = Array.from(activeStatusButtons).map(btn => btn.dataset.status);

            resultsContainer.style.display = 'flex';
            resultsList.innerHTML = '<div class="text-slate-400 text-center py-4">Consultando...</div>';

            try {
                const response = await fetch('/api/dashboard/order_report.php', {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        fecha_inicio: null,
                        fecha_fin: null,
                        statuses: selectedStatuses
                    })
                });

                if (!response.ok) {
                    throw new Error(`Error HTTP: ${response.status}`);
                }

                const data = await response.json();

                if (data.success) {
                    const docIcon = `<svg class="w-4 h-4 text-cyan-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>`;

                    if (data.results && data.results.length > 0) {
                        resultsList.innerHTML = data.results.map(order => {
                            const orderDate = order.fecha ? new Date(order.fecha) : null;
                            const formattedDate = orderDate ? orderDate.toLocaleDateString('es-MX', { day: '2-digit', month: 'short', year: 'numeric' }) : 'Fecha N/D';
                            const orderNumber = order.numeric_id ?? order.orden_id;
                            const vehicleInfo = order.vehiculo ? `${order.vehiculo}${order.placas ? ' • ' + order.placas : ''}` : 'Vehículo N/D';
                            const cliente = order.cliente || 'Cliente N/D';
                            const statusBaseColor = getStatusColor(order.status);
                            const statusBgColor = hexToRgba(statusBaseColor, 0.15);

                            return `
                            <div class="flex justify-between items-center py-2 px-3 hover:bg-slate-700/50 rounded-lg transition-colors" style="background-color: ${statusBgColor};">
                                <div class="flex items-baseline gap-3 flex-1 min-w-0">
                                    ${docIcon}
                                    <span class="text-white font-medium text-sm whitespace-nowrap">Orden #${orderNumber}</span>
                                    <span class="text-slate-300 text-xs truncate" title="${cliente}">${cliente}</span>
                                    <span class="text-slate-500 text-xs">•</span>
                                    <span class="text-slate-400 text-xs truncate" title="${vehicleInfo}">${vehicleInfo}</span>
                                </div>
                                <div class="flex items-baseline gap-4 ml-3 flex-shrink-0">
                                    <span class="text-slate-400 text-xs whitespace-nowrap">${formattedDate}</span>
                                    <span class="text-emerald-400 font-bold text-sm whitespace-nowrap">${formatCurrency(order.total || 0)}</span>
                                </div>
                            </div>
                            `;
                        }).join('');
                    } else {
                        resultsList.innerHTML = '<div class="text-slate-400 text-center py-4">No se encontraron órdenes con esos criterios.</div>';
                    }

                    const summary = data.summary || { total_ordenes: 0, monto_total: 0 };
                    totalOrdenesEl.textContent = (summary.total_ordenes || 0).toLocaleString();
                    montoTotalEl.textContent = formatCurrency(summary.monto_total || 0);
                } else {
                    throw new Error(data.message || 'Error al procesar la respuesta');
                }
            } catch (error) {
                console.error('Error al consultar el reporte:', error);
                resultsList.innerHTML = `<div class="text-red-400 text-center py-4">Error: ${error.message}</div>`;
                totalOrdenesEl.textContent = '0';
                montoTotalEl.textContent = formatCurrency(0);
            }
        }

        if (statusFiltersContainer && resultsContainer && resultsList && totalOrdenesEl && montoTotalEl) {
            runOrderReport();
        }

        // --- FIN: LÓGICA DEL REPORTEADOR DE ÓRDENES ---

        // Cargar métricas
        async function loadMetrics() {
            try {
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
                } else {
                    throw new Error(data.message || 'Error desconocido');
                }
                
            } catch (error) {
                console.error('Error:', error);
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
            
            // Mayores ventas y órdenes pendientes
            renderSalesList('highest-sales-list', metrics.highest_sales, true);
            renderPendingOrdersList('pending-orders-list', metrics.pending_orders);
            
            // Distribución por estado
            renderStatusChart(metrics.status_distribution);
            
            // Facturación mensual
            renderMonthlySalesChart(metrics.monthly_sales);
            
            // Top clientes
            renderTopClientsChart(metrics.top_clients);
            
            // Estadísticas generales
            document.getElementById('total-historico').textContent = formatCurrency(metrics.general_stats.total_historico);
            document.getElementById('ordenes-historico').textContent = metrics.general_stats.total_ordenes_historico.toLocaleString();
            
            // Desglose de órdenes por estatus
            const listContainer = document.getElementById('historical-status-list');
            const statusCounts = metrics.general_stats.status_counts_historico;
            
            // Mapeo de colores consistente con historial de órdenes
            const statusColorMap = {
                'cotización': '#6B7280',
                'recibido': '#3B82F6',
                'diagnostico': '#8B5CF6',
                'autorizado en reparación': '#EAB308',
                'preparacion para entrega': '#06B6D4',
                'entregado pendiente de pago': '#EF4444',
                'en facturación': '#6366F1',
                'facturado': '#14B8A6',
                'entregado pagado': '#22C55E'
            };

            const normalizeStatus = (status) => {
                if (!status) return '';
                return status === 'En reparación' ? 'Autorizado en reparación' : status;
            };
            
            if (statusCounts && listContainer) {
                listContainer.innerHTML = statusCounts.map(item => {
                    const normalizedStatus = normalizeStatus(item.status);
                    const color = statusColorMap[normalizedStatus.toLowerCase()] || '#94a3b8';
                    
                    return `
                    <div class="flex justify-between items-center py-1 px-1 hover:bg-slate-700/30 rounded-lg transition-colors">
                        <div class="flex items-center gap-2">
                            <span class="status-dot flex-shrink-0" style="background-color: ${color};"></span>
                            <span class="text-sm text-slate-400">${normalizedStatus}</span>
                        </div>
                        
                        <span class="text-sm font-bold text-white">${item.cantidad.toLocaleString()}</span>
                    </div>
                    `;
                }).join('');
            }

            requestAnimationFrame(resizeCharts);
        }

        // Gráfico de dona financiera
        function renderFinancialDonut(percentage) {
            const ctx = document.getElementById('financial-donut');
            if (!ctx) return;
            
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
                    maintainAspectRatio: false,
                    cutout: '75%',
                    plugins: {
                        legend: { display: false },
                        tooltip: { enabled: false }
                    },
                    animation: {
                        duration: 500
                    }
                },
                plugins: [{
                    id: 'centerText',
                    afterDraw: (chart) => {
                        const ctx = chart.ctx;
                        const chartArea = chart.chartArea;
                        if (!chartArea) {
                            return;
                        }

                        const { left, right, top, bottom } = chartArea;
                        const centerX = (left + right) / 2;
                        const centerY = (top + bottom) / 2;
                        const availableSize = Math.min(right - left, bottom - top);
                        const fontSize = Math.max(14, Math.round(availableSize * 0.25));

                        ctx.save();
                        ctx.font = `bold ${fontSize}px Inter`;
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
            const ctx = document.getElementById('daily-sales-chart');
            if (!ctx) return;
            
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
                                font: { size: 9 },
                                callback: (value) => '$' + (value / 1000).toFixed(0) + 'k'
                            }
                        },
                        x: {
                            grid: { display: false },
                            ticks: { color: '#94a3b8', font: { size: 9 }, maxRotation: 45, minRotation: 30 }
                        }
                    }
                }
            });
        }

        // Lista de ventas (mayores/menores)
        function renderSalesList(containerId, sales, isHighest) {
            const container = document.getElementById(containerId);
            const color = isHighest ? 'emerald' : 'orange';

            if (!container) {
                return;
            }

            if (!Array.isArray(sales) || sales.length === 0) {
                container.innerHTML = '<div class="text-slate-400 text-center py-4">No hay registros disponibles</div>';
                return;
            }
            
            container.innerHTML = sales.map((sale, index) => {
                const customerName = sale.cliente || 'Cliente N/D';
                const orderDetails = [`Orden #${sale.orden_id ?? sale.numeric_id ?? 'N/D'}`];
                if (sale.status) {
                    orderDetails.push(sale.status);
                }
                const orderInfo = orderDetails.join(' • ');
                const vehicleParts = [];
                if (sale.vehiculo) {
                    vehicleParts.push(sale.vehiculo);
                }
                if (sale.placas) {
                    vehicleParts.push(sale.placas);
                }
                const vehicleInfo = vehicleParts.join(' • ');
                const totalAmount = typeof sale.total === 'number' ? sale.total : 0;

                return `
                <div class="flex justify-between items-center py-2 px-3 hover:bg-slate-700/40 rounded-lg transition-colors">
                    <div class="flex items-baseline gap-3 flex-1 min-w-0">
                        <span class="text-${color}-400 font-bold text-base flex-shrink-0">${index + 1}</span>
                        <span class="text-white text-sm truncate" title="${customerName}">${customerName}</span>
                        <span class="text-slate-500 text-xs">•</span>
                        <span class="text-slate-300 text-xs truncate" title="${orderInfo}">${orderInfo}</span>
                        ${vehicleInfo ? `<span class="text-slate-500 text-xs">•</span>` : ''}
                        ${vehicleInfo ? `<span class="text-slate-400 text-xs truncate" title="${vehicleInfo}">${vehicleInfo}</span>` : ''}
                    </div>
                    <span class="text-${color}-400 font-bold text-sm whitespace-nowrap">${formatCurrency(totalAmount)}</span>
                </div>
                `;
            }).join('');
        }

        // Lista de órdenes pendientes antiguas
        function renderPendingOrdersList(containerId, orders) {
            const container = document.getElementById(containerId);
            
            if (!orders || orders.length === 0) {
                container.innerHTML = '<div class="text-slate-400 text-center py-4">No hay órdenes pendientes</div>';
                return;
            }
            
            container.innerHTML = orders.map(order => {
                // Determinar color del badge según antigüedad
                const daysOld = typeof order.dias_antiguedad === 'number' ? order.dias_antiguedad : 0;
                const badgeColor = daysOld > 7 ? 'bg-red-900/30 text-red-400' : 
                                   daysOld > 3 ? 'bg-yellow-900/30 text-yellow-400' : 
                                   'bg-slate-700 text-slate-400';
                
                // SVG del icono de documento
                const docIcon = `<svg class="w-4 h-4 text-cyan-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>`;
                const customerName = order.cliente || 'Cliente N/D';
                const vehicleParts = [];
                if (order.vehiculo) {
                    vehicleParts.push(order.vehiculo);
                }
                if (order.placas) {
                    vehicleParts.push(order.placas);
                }
                const vehicleInfo = vehicleParts.join(' • ');
                const totalAmount = typeof order.total === 'number' ? order.total : 0;
                const orderId = order.orden_id ?? order.numeric_id ?? 'N/D';
                
                return `
                <div class="flex justify-between items-center py-2 px-3 hover:bg-slate-700/30 rounded-lg transition-colors">
                    <div class="flex items-baseline gap-3 flex-1 min-w-0">
                        ${docIcon}
                        <span class="text-white font-medium text-sm whitespace-nowrap">Orden #${orderId}</span>
                        <span class="text-slate-400 text-xs truncate" title="${customerName}">${customerName}</span>
                        ${vehicleInfo ? `<span class="text-slate-600 text-xs">•</span>` : ''}
                        ${vehicleInfo ? `<span class="text-slate-500 text-xs truncate" title="${vehicleInfo}">${vehicleInfo}</span>` : ''}
                    </div>
                    <div class="flex items-center gap-2 ml-3 flex-shrink-0 whitespace-nowrap text-xs">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full font-semibold ${badgeColor}">
                            ${daysOld}d atrás
                        </span>
                        <span class="text-emerald-400 font-semibold text-xs">${formatCurrency(totalAmount)}</span>
                    </div>
                </div>
                `;
            }).join('');
        }

        // Gráfico de distribución por estado
        function renderStatusChart(distribution) {
            const canvas = document.getElementById('status-distribution-chart');
            if (!canvas) return;
            
            const ctx = canvas.getContext('2d');
            if (!ctx) return;
            
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
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: { color: '#94a3b8', padding: 8, font: { size: 10 } }
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
            const canvas = document.getElementById('monthly-sales-chart');
            if (!canvas) return;
            
            const ctx = canvas.getContext('2d');
            if (!ctx) return;
            
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
                        backgroundColor: '#22D3EE',
                        hoverBackgroundColor: '#67E8F9',
                        borderRadius: { topLeft: 4, topRight: 4 },
                        borderSkipped: false,
                        barPercentage: 0.6
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
                            grid: { 
                                color: '#334155', 
                                drawBorder: false,
                                borderDash: [5, 5]
                            },
                            ticks: { 
                                color: '#94a3b8',
                                font: { size: 9 },
                                callback: (value) => '$' + (value / 1000).toFixed(0) + 'k'
                            }
                        },
                        x: {
                            grid: { display: false },
                            ticks: { color: '#94a3b8', font: { size: 9 } }
                        }
                    }
                }
            });
        }

        // Gráfico de top clientes
        function renderTopClientsChart(topClients) {
            const canvas = document.getElementById('top-clients-chart');
            if (!canvas) return;
            
            const ctx = canvas.getContext('2d');
            if (!ctx) return;
            
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
                                font: { size: 9 },
                                callback: (value) => '$' + (value / 1000).toFixed(0) + 'k'
                            }
                        },
                        y: {
                            grid: { display: false },
                            ticks: { color: '#94a3b8', font: { size: 9 } }
                        }
                    }
                }
            });
        }

        // Inicializar y configurar auto-actualización
        initGridLayout();
        loadMetrics();
        setInterval(loadMetrics, AUTO_UPDATE_INTERVAL);
        window.addEventListener('resize', () => requestAnimationFrame(resizeCharts));
    </script>
</body>
</html>
