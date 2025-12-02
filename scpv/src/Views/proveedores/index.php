<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">
        <i class="fas fa-handshake text-primary me-2"></i>
        Proveedores
    </h1>
    <a href="/proveedores/create" class="btn btn-primary">
        <i class="fas fa-plus me-2"></i>
        Nuevo Proveedor
    </a>
</div>

<!-- Filtros -->
<div class="card mb-4">
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <input type="text" class="form-control" placeholder="Buscar por nombre, RFC o email...">
            </div>
            <div class="col-md-3">
                <select class="form-select">
                    <option value="">Todos</option>
                    <option value="1">Activos</option>
                    <option value="0">Inactivos</option>
                </select>
            </div>
            <div class="col-md-3">
                <button class="btn btn-secondary w-100">
                    <i class="fas fa-search me-2"></i>
                    Buscar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Lista de Proveedores -->
<div class="row">
    <?php if (empty($proveedores)): ?>
        <div class="col-12">
            <div class="card">
                <div class="card-body text-center py-5">
                    <i class="fas fa-store-slash fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">No hay proveedores registrados</h5>
                    <p class="text-muted">Comienza agregando tu primer proveedor</p>
                    <a href="/proveedores/create" class="btn btn-primary mt-3">
                        <i class="fas fa-plus me-2"></i>
                        Nuevo Proveedor
                    </a>
                </div>
            </div>
        </div>
    <?php else: ?>
        <?php foreach ($proveedores as $proveedor): ?>
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card h-100 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-building text-primary me-2"></i>
                                <?= htmlspecialchars($proveedor['nombre']) ?>
                            </h5>
                            <?php if ($proveedor['activo']): ?>
                                <span class="badge bg-success">Activo</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Inactivo</span>
                            <?php endif; ?>
                        </div>
                        
                        <p class="card-text">
                            <small class="text-muted">
                                <i class="fas fa-id-card me-1"></i>
                                RFC: <?= htmlspecialchars($proveedor['rfc']) ?>
                            </small>
                        </p>
                        
                        <p class="card-text">
                            <small>
                                <i class="fas fa-envelope me-1 text-muted"></i>
                                <?= htmlspecialchars($proveedor['email']) ?>
                            </small>
                        </p>
                        
                        <?php if (!empty($proveedor['telefono'])): ?>
                            <p class="card-text">
                                <small>
                                    <i class="fas fa-phone me-1 text-muted"></i>
                                    <?= htmlspecialchars($proveedor['telefono']) ?>
                                </small>
                            </p>
                        <?php endif; ?>
                        
                        <div class="mb-3">
                            <i class="fas fa-star text-warning me-1"></i>
                            <strong><?= number_format($proveedor['calificacion'] ?? 0, 1) ?></strong>
                            <small class="text-muted">/ 5.0</small>
                        </div>
                        
                        <div class="d-flex gap-2">
                            <a href="/proveedores/<?= $proveedor['id'] ?>" class="btn btn-sm btn-outline-primary flex-fill">
                                <i class="fas fa-eye"></i> Ver
                            </a>
                            <a href="/proveedores/<?= $proveedor['id'] ?>/edit" class="btn btn-sm btn-outline-secondary flex-fill">
                                <i class="fas fa-edit"></i> Editar
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
