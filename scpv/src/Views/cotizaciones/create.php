<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">
        <i class="fas fa-plus-circle text-primary me-2"></i>
        Nueva Cotización
    </h1>
    <a href="/cotizaciones" class="btn btn-secondary">
        <i class="fas fa-arrow-left me-2"></i>
        Volver
    </a>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="/cotizaciones">
            <input type="hidden" name="_token" value="<?= $csrf_token ?>">
            
            <div class="row">
                <div class="col-md-8 mb-3">
                    <label for="titulo" class="form-label">Título de la Cotización *</label>
                    <input type="text" 
                           class="form-control" 
                           id="titulo" 
                           name="titulo" 
                           placeholder="Ej: Compra de equipos de cómputo"
                           required>
                </div>
                
                <div class="col-md-4 mb-3">
                    <label for="fecha_limite" class="form-label">Fecha Límite *</label>
                    <input type="date" 
                           class="form-control" 
                           id="fecha_limite" 
                           name="fecha_limite"
                           min="<?= date('Y-m-d') ?>"
                           required>
                </div>
            </div>
            
            <div class="mb-3">
                <label for="descripcion" class="form-label">Descripción *</label>
                <textarea class="form-control" 
                          id="descripcion" 
                          name="descripcion" 
                          rows="4"
                          placeholder="Describe detalladamente los productos o servicios requeridos..."
                          required></textarea>
            </div>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="presupuesto_estimado" class="form-label">Presupuesto Estimado</label>
                    <div class="input-group">
                        <span class="input-group-text">$</span>
                        <input type="number" 
                               class="form-control" 
                               id="presupuesto_estimado" 
                               name="presupuesto_estimado"
                               step="0.01"
                               min="0"
                               placeholder="0.00">
                    </div>
                    <small class="text-muted">Opcional: Indica el presupuesto aproximado</small>
                </div>
            </div>
            
            <hr class="my-4">
            
            <div class="d-flex justify-content-end gap-2">
                <a href="/cotizaciones" class="btn btn-secondary">
                    <i class="fas fa-times me-2"></i>
                    Cancelar
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-2"></i>
                    Guardar Cotización
                </button>
            </div>
        </form>
    </div>
</div>

<style>
    .form-label {
        font-weight: 600;
        color: #2c3e50;
    }
    
    .form-control:focus {
        border-color: #3498db;
        box-shadow: 0 0 0 0.2rem rgba(52, 152, 219, 0.25);
    }
</style>
