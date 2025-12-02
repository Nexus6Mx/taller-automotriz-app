<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">
        <i class="fas fa-tachometer-alt text-primary me-2"></i>
        Dashboard
    </h1>
    <div class="text-muted">
        <i class="fas fa-calendar-alt me-1"></i>
        <?= date('d/m/Y H:i') ?>
    </div>
</div>

<!-- Estadísticas -->
<div class="row mb-4">
    <?php if ($user['rol'] === 'admin' || $user['rol'] === 'comprador'): ?>
    
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card stat-card bg-primary text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <div class="h4 mb-0"><?= $stats['cotizaciones_activas'] ?></div>
                        <div class="small">Cotizaciones Activas</div>
                    </div>
                    <div class="fa-2x">
                        <i class="fas fa-file-alt"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card stat-card bg-success text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <div class="h4 mb-0"><?= $stats['proveedores_activos'] ?></div>
                        <div class="small">Proveedores Activos</div>
                    </div>
                    <div class="fa-2x">
                        <i class="fas fa-building"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card stat-card bg-info text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <div class="h4 mb-0"><?= $stats['total_cotizaciones_mes'] ?></div>
                        <div class="small">Este Mes</div>
                    </div>
                    <div class="fa-2x">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card stat-card bg-warning text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <div class="h4 mb-0">$<?= number_format($stats['valor_promedio_cotizaciones'], 0) ?></div>
                        <div class="small">Valor Promedio</div>
                    </div>
                    <div class="fa-2x">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php elseif ($user['rol'] === 'proveedor'): ?>
    
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card stat-card bg-primary text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <div class="h4 mb-0"><?= $stats['invitaciones_pendientes'] ?></div>
                        <div class="small">Invitaciones Pendientes</div>
                    </div>
                    <div class="fa-2x">
                        <i class="fas fa-envelope"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card stat-card bg-info text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <div class="h4 mb-0"><?= $stats['ofertas_enviadas'] ?></div>
                        <div class="small">Ofertas Enviadas</div>
                    </div>
                    <div class="fa-2x">
                        <i class="fas fa-paper-plane"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card stat-card bg-success text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <div class="h4 mb-0"><?= $stats['ofertas_aceptadas'] ?></div>
                        <div class="small">Ofertas Aceptadas</div>
                    </div>
                    <div class="fa-2x">
                        <i class="fas fa-check-circle"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card stat-card bg-warning text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <div class="h4 mb-0"><?= $stats['calificacion_promedio'] ?></div>
                        <div class="small">Calificación Promedio</div>
                    </div>
                    <div class="fa-2x">
                        <i class="fas fa-star"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php else: ?>
    
    <div class="col-xl-6 col-md-6 mb-4">
        <div class="card stat-card bg-primary text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <div class="h4 mb-0"><?= $stats['cotizaciones_publicas'] ?></div>
                        <div class="small">Cotizaciones Públicas</div>
                    </div>
                    <div class="fa-2x">
                        <i class="fas fa-eye"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-6 col-md-6 mb-4">
        <div class="card stat-card bg-info text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <div class="h4 mb-0"><?= $stats['total_proveedores'] ?></div>
                        <div class="small">Proveedores Registrados</div>
                    </div>
                    <div class="fa-2x">
                        <i class="fas fa-building"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php endif; ?>
</div>

<div class="row">
    <!-- Actividad Reciente -->
    <div class="col-lg-8 mb-4">
        <div class="card">
            <div class="card-header bg-white">
                <h5 class="mb-0">
                    <i class="fas fa-clock text-primary me-2"></i>
                    Actividad Reciente
                </h5>
            </div>
            <div class="card-body">
                <?php if (!empty($recent_activity)): ?>
                <div class="list-group list-group-flush">
                    <?php foreach ($recent_activity as $item): ?>
                    <div class="list-group-item border-0 px-0">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="mb-1"><?= htmlspecialchars($item['titulo'] ?? $item['folio']) ?></h6>
                                <?php if (isset($item['solicitante'])): ?>
                                <small class="text-muted">
                                    Solicitante: <?= htmlspecialchars($item['solicitante']) ?>
                                </small>
                                <?php endif; ?>
                            </div>
                            <small class="text-muted">
                                <?= date('d/m/Y', strtotime($item['created_at'])) ?>
                            </small>
                        </div>
                        <?php if (isset($item['fecha_limite'])): ?>
                        <small class="text-warning">
                            <i class="fas fa-clock me-1"></i>
                            Límite: <?= date('d/m/Y', strtotime($item['fecha_limite'])) ?>
                        </small>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <div class="text-center py-4">
                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                    <p class="text-muted">No hay actividad reciente</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Accesos Rápidos -->
    <div class="col-lg-4 mb-4">
        <div class="card">
            <div class="card-header bg-white">
                <h5 class="mb-0">
                    <i class="fas fa-rocket text-primary me-2"></i>
                    Accesos Rápidos
                </h5>
            </div>
            <div class="card-body">
                <?php if ($user['rol'] === 'admin' || $user['rol'] === 'comprador'): ?>
                <div class="d-grid gap-2">
                    <a href="/cotizaciones/create" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>
                        Nueva Cotización
                    </a>
                    <a href="/proveedores/create" class="btn btn-outline-primary">
                        <i class="fas fa-building me-2"></i>
                        Nuevo Proveedor
                    </a>
                    <a href="/cotizaciones" class="btn btn-outline-secondary">
                        <i class="fas fa-list me-2"></i>
                        Ver Cotizaciones
                    </a>
                </div>
                <?php else: ?>
                <div class="d-grid gap-2">
                    <a href="/cotizaciones" class="btn btn-primary">
                        <i class="fas fa-eye me-2"></i>
                        Ver Cotizaciones
                    </a>
                    <a href="/proveedores" class="btn btn-outline-primary">
                        <i class="fas fa-building me-2"></i>
                        Ver Proveedores
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>