<div class="mb-4">
    <h1 class="h3 mb-0">
        <i class="fas fa-cog text-primary me-2"></i>
        Configuración del Sistema
    </h1>
    <p class="text-muted">Administración y configuración general</p>
</div>

<div class="row">
    <!-- Información del Sistema -->
    <div class="col-lg-6 mb-4">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">
                    <i class="fas fa-info-circle me-2"></i>
                    Información del Sistema
                </h5>
            </div>
            <div class="card-body">
                <table class="table table-borderless">
                    <tr>
                        <td><strong>Nombre del Sistema:</strong></td>
                        <td><?= APP_NAME ?></td>
                    </tr>
                    <tr>
                        <td><strong>Versión:</strong></td>
                        <td><?= APP_VERSION ?></td>
                    </tr>
                    <tr>
                        <td><strong>Entorno:</strong></td>
                        <td>
                            <span class="badge bg-<?= APP_ENV === 'production' ? 'success' : 'warning' ?>">
                                <?= strtoupper(APP_ENV) ?>
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <td><strong>PHP Version:</strong></td>
                        <td><?= phpversion() ?></td>
                    </tr>
                    <tr>
                        <td><strong>Servidor:</strong></td>
                        <td><?= $_SERVER['SERVER_SOFTWARE'] ?? 'N/A' ?></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Configuración de Sesión -->
    <div class="col-lg-6 mb-4">
        <div class="card">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0">
                    <i class="fas fa-clock me-2"></i>
                    Configuración de Sesión
                </h5>
            </div>
            <div class="card-body">
                <table class="table table-borderless">
                    <tr>
                        <td><strong>Timeout de Sesión:</strong></td>
                        <td><?= SESSION_TIMEOUT / 60 ?> minutos</td>
                    </tr>
                    <tr>
                        <td><strong>Base de Datos:</strong></td>
                        <td><?= DB_NAME ?></td>
                    </tr>
                    <tr>
                        <td><strong>Host DB:</strong></td>
                        <td><?= DB_HOST ?>:<?= DB_PORT ?></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Mantenimiento -->
<div class="row">
    <div class="col-lg-12 mb-4">
        <div class="card">
            <div class="card-header bg-warning text-dark">
                <h5 class="mb-0">
                    <i class="fas fa-tools me-2"></i>
                    Herramientas de Mantenimiento
                </h5>
            </div>
            <div class="card-body">
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Atención:</strong> Estas acciones son sensibles y pueden afectar el funcionamiento del sistema.
                </div>
                
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <div class="card">
                            <div class="card-body text-center">
                                <i class="fas fa-database fa-3x text-primary mb-3"></i>
                                <h6>Optimizar Base de Datos</h6>
                                <p class="text-muted small">Optimiza y limpia la base de datos</p>
                                <button class="btn btn-sm btn-outline-primary" disabled>
                                    Optimizar
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <div class="card">
                            <div class="card-body text-center">
                                <i class="fas fa-trash-alt fa-3x text-danger mb-3"></i>
                                <h6>Limpiar Logs</h6>
                                <p class="text-muted small">Elimina logs antiguos del sistema</p>
                                <button class="btn btn-sm btn-outline-danger" disabled>
                                    Limpiar
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <div class="card">
                            <div class="card-body text-center">
                                <i class="fas fa-download fa-3x text-success mb-3"></i>
                                <h6>Backup Completo</h6>
                                <p class="text-muted small">Genera backup de la base de datos</p>
                                <button class="btn btn-sm btn-outline-success" disabled>
                                    Descargar
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <p class="text-muted mb-0 mt-3">
                    <i class="fas fa-info-circle me-1"></i>
                    <small>Funcionalidades de mantenimiento disponibles próximamente</small>
                </p>
            </div>
        </div>
    </div>
</div>

<!-- Configuración General (Placeholder) -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="fas fa-sliders-h me-2"></i>
            Configuración General
        </h5>
    </div>
    <div class="card-body">
        <form>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Nombre de la Empresa</label>
                    <input type="text" class="form-control" value="<?= APP_NAME ?>" disabled>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label class="form-label">Email de Contacto</label>
                    <input type="email" class="form-control" placeholder="contacto@empresa.com" disabled>
                </div>
            </div>
            
            <div class="alert alert-info mb-0">
                <i class="fas fa-info-circle me-2"></i>
                Panel de configuración avanzada en desarrollo
            </div>
        </form>
    </div>
</div>
