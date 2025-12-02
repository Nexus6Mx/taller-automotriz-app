<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">
        <i class="fas fa-clipboard-list text-primary me-2"></i>
        Cotizaciones
    </h1>
    <a href="/cotizaciones/create" class="btn btn-primary">
        <i class="fas fa-plus me-2"></i>
        Nueva Cotización
    </a>
</div>

<!-- Filtros -->
<div class="card mb-4">
    <div class="card-body">
        <div class="row">
            <div class="col-md-4">
                <input type="text" class="form-control" placeholder="Buscar por folio o título...">
            </div>
            <div class="col-md-3">
                <select class="form-select">
                    <option value="">Todos los estados</option>
                    <option value="borrador">Borrador</option>
                    <option value="publicada">Publicada</option>
                    <option value="en_proceso">En Proceso</option>
                    <option value="finalizada">Finalizada</option>
                </select>
            </div>
            <div class="col-md-3">
                <input type="date" class="form-control" placeholder="Fecha límite">
            </div>
            <div class="col-md-2">
                <button class="btn btn-secondary w-100">
                    <i class="fas fa-search me-2"></i>
                    Buscar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Lista de Cotizaciones -->
<div class="card">
    <div class="card-body">
        <?php if (empty($cotizaciones)): ?>
            <div class="text-center py-5">
                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">No hay cotizaciones registradas</h5>
                <p class="text-muted">Comienza creando tu primera cotización</p>
                <a href="/cotizaciones/create" class="btn btn-primary mt-3">
                    <i class="fas fa-plus me-2"></i>
                    Nueva Cotización
                </a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Folio</th>
                            <th>Título</th>
                            <th>Fecha Límite</th>
                            <th>Estado</th>
                            <th>Presupuesto</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cotizaciones as $cotizacion): ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($cotizacion['folio']) ?></strong>
                                </td>
                                <td><?= htmlspecialchars($cotizacion['titulo']) ?></td>
                                <td>
                                    <i class="fas fa-calendar me-1"></i>
                                    <?= date('d/m/Y', strtotime($cotizacion['fecha_limite'])) ?>
                                </td>
                                <td>
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
                                </td>
                                <td>
                                    $<?= number_format($cotizacion['presupuesto_estimado'] ?? 0, 2) ?>
                                </td>
                                <td>
                                    <a href="/cotizaciones/<?= $cotizacion['id'] ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="/cotizaciones/<?= $cotizacion['id'] ?>/edit" class="btn btn-sm btn-outline-secondary">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
