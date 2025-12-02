<div class="text-center py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="mb-5">
                    <i class="fas fa-clipboard-list fa-5x text-primary mb-4"></i>
                    <h1 class="display-4 fw-bold text-primary mb-4">
                        <?= APP_NAME ?>
                    </h1>
                    <p class="lead text-muted mb-4">
                        Sistema integral para la gestión de cotizaciones con proveedores.
                        Optimiza tus procesos de compras y obtén las mejores ofertas del mercado.
                    </p>
                    <p class="h5 text-secondary mb-5">Versión <?= $version ?></p>
                </div>
                
                <div class="row mb-5">
                    <div class="col-md-4 mb-4">
                        <div class="card h-100 border-0 shadow-sm">
                            <div class="card-body text-center p-4">
                                <i class="fas fa-search-dollar fa-3x text-primary mb-3"></i>
                                <h5 class="card-title">Gestión de Cotizaciones</h5>
                                <p class="card-text">Crea, gestiona y evalúa cotizaciones de manera eficiente.</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-4">
                        <div class="card h-100 border-0 shadow-sm">
                            <div class="card-body text-center p-4">
                                <i class="fas fa-handshake fa-3x text-success mb-3"></i>
                                <h5 class="card-title">Red de Proveedores</h5>
                                <p class="card-text">Administra tu base de proveedores y sus capacidades.</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-4">
                        <div class="card h-100 border-0 shadow-sm">
                            <div class="card-body text-center p-4">
                                <i class="fas fa-chart-line fa-3x text-info mb-3"></i>
                                <h5 class="card-title">Análisis y Reportes</h5>
                                <p class="card-text">Obtén insights valiosos sobre tu proceso de compras.</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="d-grid gap-2 d-md-flex justify-content-md-center">
                    <a href="/login" class="btn btn-primary btn-lg px-5 me-md-2">
                        <i class="fas fa-sign-in-alt me-2"></i>
                        Iniciar Sesión
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    body {
        background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        min-height: 100vh;
    }
    
    .card {
        transition: transform 0.3s ease;
    }
    
    .card:hover {
        transform: translateY(-5px);
    }
    
    .btn-primary {
        background: linear-gradient(45deg, #667eea, #764ba2);
        border: none;
        border-radius: 50px;
        font-weight: 600;
        transition: all 0.3s ease;
    }
    
    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(0,0,0,0.2);
    }
</style>