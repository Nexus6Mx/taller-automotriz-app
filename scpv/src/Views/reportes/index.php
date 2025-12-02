<div class="mb-4">
    <h1 class="h3 mb-0">
        <i class="fas fa-chart-bar text-primary me-2"></i>
        Reportes y Estadísticas
    </h1>
    <p class="text-muted">Panel de análisis y métricas del sistema</p>
</div>

<!-- Estadísticas Generales -->
<div class="row mb-4">
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="mb-0"><?= $stats['total_cotizaciones'] ?></h4>
                        <small>Total Cotizaciones</small>
                    </div>
                    <div class="fa-3x opacity-50">
                        <i class="fas fa-clipboard-list"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card bg-success text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="mb-0"><?= $stats['total_proveedores'] ?></h4>
                        <small>Total Proveedores</small>
                    </div>
                    <div class="fa-3x opacity-50">
                        <i class="fas fa-handshake"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card bg-info text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="mb-0"><?= $stats['total_usuarios'] ?></h4>
                        <small>Usuarios Activos</small>
                    </div>
                    <div class="fa-3x opacity-50">
                        <i class="fas fa-users"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card bg-warning text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="mb-0"><?= $stats['cotizaciones_mes'] ?></h4>
                        <small>Cotizaciones Este Mes</small>
                    </div>
                    <div class="fa-3x opacity-50">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Gráficos y Detalles -->
<div class="row">
    <!-- Cotizaciones por Estado -->
    <div class="col-lg-6 mb-4">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">
                    <i class="fas fa-chart-pie me-2"></i>
                    Cotizaciones por Estado
                </h5>
            </div>
            <div class="card-body">
                <?php if (!empty($stats['cotizaciones_por_estado'])): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Estado</th>
                                    <th>Cantidad</th>
                                    <th>Porcentaje</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $total = $stats['total_cotizaciones'];
                                foreach ($stats['cotizaciones_por_estado'] as $item): 
                                    $porcentaje = $total > 0 ? ($item['cantidad'] / $total) * 100 : 0;
                                ?>
                                    <tr>
                                        <td>
                                            <?php
                                            $badgeClass = [
                                                'borrador' => 'secondary',
                                                'publicada' => 'info',
                                                'en_proceso' => 'warning',
                                                'finalizada' => 'success',
                                                'cancelada' => 'danger'
                                            ];
                                            $class = $badgeClass[$item['estado']] ?? 'secondary';
                                            ?>
                                            <span class="badge bg-<?= $class ?>">
                                                <?= ucfirst($item['estado']) ?>
                                            </span>
                                        </td>
                                        <td><strong><?= $item['cantidad'] ?></strong></td>
                                        <td>
                                            <div class="progress" style="height: 20px;">
                                                <div class="progress-bar bg-<?= $class ?>" 
                                                     style="width: <?= $porcentaje ?>%">
                                                    <?= number_format($porcentaje, 1) ?>%
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4 text-muted">
                        <i class="fas fa-chart-pie fa-3x mb-3"></i>
                        <p>No hay datos disponibles</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Resumen Financiero -->
    <div class="col-lg-6 mb-4">
        <div class="card">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0">
                    <i class="fas fa-dollar-sign me-2"></i>
                    Resumen Financiero
                </h5>
            </div>
            <div class="card-body">
                <div class="mb-4">
                    <h6 class="text-muted">Valor Total Este Mes</h6>
                    <h2 class="text-success mb-0">
                        $<?= number_format($stats['valor_total_mes'], 2) ?>
                    </h2>
                </div>
                
                <hr>
                
                <div class="row text-center">
                    <div class="col-6 mb-3">
                        <i class="fas fa-building fa-2x text-info mb-2"></i>
                        <h4 class="mb-0"><?= $stats['proveedores_activos'] ?></h4>
                        <small class="text-muted">Proveedores Activos</small>
                    </div>
                    <div class="col-6 mb-3">
                        <i class="fas fa-file-invoice-dollar fa-2x text-warning mb-2"></i>
                        <h4 class="mb-0"><?= $stats['cotizaciones_mes'] ?></h4>
                        <small class="text-muted">Cotizaciones del Mes</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Acciones de Exportación -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="fas fa-download me-2"></i>
            Exportar Reportes
        </h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-4 mb-3">
                <button class="btn btn-outline-primary w-100">
                    <i class="fas fa-file-pdf me-2"></i>
                    Exportar a PDF
                </button>
            </div>
            <div class="col-md-4 mb-3">
                <button class="btn btn-outline-success w-100">
                    <i class="fas fa-file-excel me-2"></i>
                    Exportar a Excel
                </button>
            </div>
            <div class="col-md-4 mb-3">
                <button class="btn btn-outline-info w-100">
                    <i class="fas fa-file-csv me-2"></i>
                    Exportar a CSV
                </button>
            </div>
        </div>
        <p class="text-muted mb-0 mt-2">
            <i class="fas fa-info-circle me-1"></i>
            <small>Las funciones de exportación estarán disponibles próximamente</small>
        </p>
    </div>
</div>
