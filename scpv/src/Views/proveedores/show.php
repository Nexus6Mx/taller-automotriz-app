<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">
        <i class="fas fa-building text-primary me-2"></i>
        Detalles del Proveedor
    </h1>
    <div>
        <a href="/proveedores/<?= $proveedor['id'] ?>/edit" class="btn btn-primary">
            <i class="fas fa-edit me-2"></i>
            Editar
        </a>
        <a href="/proveedores" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-2"></i>
            Volver
        </a>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-info-circle me-2"></i>
                    Información General
                </h5>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-8">
                        <strong>Razón Social:</strong><br>
                        <?= htmlspecialchars($proveedor['razon_social']) ?>
                    </div>
                    <div class="col-md-4">
                        <strong>RFC:</strong><br>
                        <?= htmlspecialchars($proveedor['rfc']) ?>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <strong>Email:</strong><br>
                        <a href="mailto:<?= htmlspecialchars($proveedor['email']) ?>">
                            <?= htmlspecialchars($proveedor['email']) ?>
                        </a>
                    </div>
                    <div class="col-md-6">
                        <strong>Teléfono:</strong><br>
                        <?= htmlspecialchars($proveedor['telefono'] ?? 'No especificado') ?>
                    </div>
                </div>
                
                <div class="mb-3">
                    <strong>Dirección:</strong><br>
                    <?= htmlspecialchars($proveedor['direccion'] ?? 'No especificada') ?>
                </div>
                
                <div class="row">
                    <div class="col-md-4">
                        <strong>Ciudad:</strong><br>
                        <?= htmlspecialchars($proveedor['ciudad'] ?? 'No especificada') ?>
                    </div>
                    <div class="col-md-4">
                        <strong>Estado:</strong><br>
                        <?= htmlspecialchars($proveedor['estado'] ?? 'No especificado') ?>
                    </div>
                    <div class="col-md-4">
                        <strong>C.P.:</strong><br>
                        <?= htmlspecialchars($proveedor['codigo_postal'] ?? 'No especificado') ?>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-user-tie me-2"></i>
                    Contacto Principal
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <strong>Nombre:</strong><br>
                        <?= htmlspecialchars($proveedor['contacto_nombre'] ?? 'No especificado') ?>
                    </div>
                    <div class="col-md-3 mb-3">
                        <strong>Teléfono:</strong><br>
                        <?= htmlspecialchars($proveedor['contacto_telefono'] ?? 'No especificado') ?>
                    </div>
                    <div class="col-md-3 mb-3">
                        <strong>Email:</strong><br>
                        <?php if (!empty($proveedor['contacto_email'])): ?>
                            <a href="mailto:<?= htmlspecialchars($proveedor['contacto_email']) ?>">
                                <?= htmlspecialchars($proveedor['contacto_email']) ?>
                            </a>
                        <?php else: ?>
                            No especificado
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-star me-2"></i>
                    Calificación
                </h5>
            </div>
            <div class="card-body text-center">
                <div class="display-4 mb-2">
                    <?php 
                    $calificacion = $proveedor['calificacion'] ?? 0;
                    echo number_format($calificacion, 1);
                    ?>
                </div>
                <div class="text-warning">
                    <?php for($i = 1; $i <= 5; $i++): ?>
                        <i class="fas fa-star<?= $i <= $calificacion ? '' : '-o' ?>"></i>
                    <?php endfor; ?>
                </div>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-info-circle me-2"></i>
                    Estado
                </h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <strong>Estado:</strong><br>
                    <span class="badge <?= $proveedor['activo'] ? 'bg-success' : 'bg-secondary' ?>">
                        <?= $proveedor['activo'] ? 'Activo' : 'Inactivo' ?>
                    </span>
                </div>
                <div class="mb-3">
                    <strong>Fecha de registro:</strong><br>
                    <?= date('d/m/Y', strtotime($proveedor['created_at'])) ?>
                </div>
                <div>
                    <strong>Última actualización:</strong><br>
                    <?= date('d/m/Y H:i', strtotime($proveedor['updated_at'])) ?>
                </div>
            </div>
        </div>
    </div>
</div>
