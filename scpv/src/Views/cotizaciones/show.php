<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">
        <i class="fas fa-file-alt text-primary me-2"></i>
        Cotización: <?= htmlspecialchars($cotizacion['folio']) ?>
    </h1>
    <div>
        <a href="/cotizaciones" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-2"></i>
            Volver
        </a>
        <button class="btn btn-primary">
            <i class="fas fa-edit me-2"></i>
            Editar
        </button>
    </div>
</div>

<div class="row">
    <!-- Información Principal -->
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">
                    <i class="fas fa-info-circle me-2"></i>
                    Información General
                </h5>
            </div>
            <div class="card-body">
                <h4 class="mb-3"><?= htmlspecialchars($cotizacion['titulo']) ?></h4>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <strong><i class="fas fa-calendar me-2 text-muted"></i>Fecha Límite:</strong>
                        <p><?= date('d/m/Y', strtotime($cotizacion['fecha_limite'])) ?></p>
                    </div>
                    <div class="col-md-6">
                        <strong><i class="fas fa-tag me-2 text-muted"></i>Estado:</strong>
                        <p>
                            <?php
                            $badgeClass = [
                                'borrador' => 'secondary',
                                'publicada' => 'info',
                                'en_proceso' => 'warning',
                                'finalizada' => 'success',
                                'cancelada' => 'danger'
                            ];
                            $class = $badgeClass[$cotizacion['estado']] ?? 'secondary';
                            ?>
                            <span class="badge bg-<?= $class ?>">
                                <?= ucfirst($cotizacion['estado']) ?>
                            </span>
                        </p>
                    </div>
                </div>
                
                <div class="mb-3">
                    <strong><i class="fas fa-align-left me-2 text-muted"></i>Descripción:</strong>
                    <p class="text-muted"><?= nl2br(htmlspecialchars($cotizacion['descripcion'])) ?></p>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <strong><i class="fas fa-dollar-sign me-2 text-muted"></i>Presupuesto Estimado:</strong>
                        <p class="h5 text-primary">$<?= number_format($cotizacion['presupuesto_estimado'] ?? 0, 2) ?></p>
                    </div>
                    <div class="col-md-6">
                        <strong><i class="fas fa-calculator me-2 text-muted"></i>Total Estimado:</strong>
                        <p class="h5 text-success">$<?= number_format($cotizacion['total_estimado'] ?? 0, 2) ?></p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Ofertas Recibidas -->
        <div class="card">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0">
                    <i class="fas fa-handshake me-2"></i>
                    Ofertas Recibidas (0)
                </h5>
            </div>
            <div class="card-body">
                <div class="text-center py-4">
                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                    <p class="text-muted">No se han recibido ofertas aún</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Panel Lateral -->
    <div class="col-md-4">
        <!-- Acciones Rápidas -->
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="fas fa-bolt me-2"></i>
                    Acciones Rápidas
                </h6>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <button class="btn btn-outline-primary">
                        <i class="fas fa-paper-plane me-2"></i>
                        Publicar Cotización
                    </button>
                    <button class="btn btn-outline-secondary">
                        <i class="fas fa-users me-2"></i>
                        Invitar Proveedores
                    </button>
                    <button class="btn btn-outline-info">
                        <i class="fas fa-file-pdf me-2"></i>
                        Generar PDF
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Información Adicional -->
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="fas fa-clock me-2"></i>
                    Información del Sistema
                </h6>
            </div>
            <div class="card-body">
                <small class="text-muted">
                    <strong>Folio:</strong> <?= htmlspecialchars($cotizacion['folio']) ?><br>
                    <strong>Creado:</strong> <?= date('d/m/Y H:i', strtotime($cotizacion['created_at'])) ?><br>
                    <strong>Actualizado:</strong> <?= date('d/m/Y H:i', strtotime($cotizacion['updated_at'])) ?>
                </small>
            </div>
        </div>
    </div>
</div>
