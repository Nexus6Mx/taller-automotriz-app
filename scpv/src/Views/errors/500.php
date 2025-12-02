<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error interno - <?= APP_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container">
        <div class="row justify-content-center align-items-center" style="min-height: 100vh;">
            <div class="col-md-6 text-center">
                <div class="mb-4">
                    <i class="fas fa-times-circle fa-5x text-danger mb-4"></i>
                    <h1 class="display-1 fw-bold text-muted">500</h1>
                    <h2 class="mb-4">Error interno del servidor</h2>
                    <p class="lead text-muted mb-4">
                        Lo sentimos, ha ocurrido un error interno. 
                        Nuestro equipo técnico ha sido notificado.
                    </p>
                    <div class="d-grid gap-2 d-md-flex justify-content-md-center">
                        <a href="/" class="btn btn-primary me-md-2">
                            <i class="fas fa-home me-2"></i>
                            Ir al Inicio
                        </a>
                        <button onclick="location.reload()" class="btn btn-outline-secondary">
                            <i class="fas fa-redo me-2"></i>
                            Intentar de nuevo
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>