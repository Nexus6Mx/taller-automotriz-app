<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">
        <i class="fas fa-plus-circle text-primary me-2"></i>
        Nuevo Proveedor
    </h1>
    <a href="/proveedores" class="btn btn-secondary">
        <i class="fas fa-arrow-left me-2"></i>
        Volver
    </a>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="/proveedores">
            <input type="hidden" name="_token" value="<?= $csrf_token ?>">
            
            <h5 class="mb-3">
                <i class="fas fa-building me-2 text-primary"></i>
                Información de la Empresa
            </h5>
            
            <div class="row">
                <div class="col-md-8 mb-3">
                    <label for="nombre" class="form-label">Nombre de la Empresa *</label>
                    <input type="text" 
                           class="form-control" 
                           id="nombre" 
                           name="nombre" 
                           placeholder="Ej: Suministros Corporativos S.A."
                           required>
                </div>
                
                <div class="col-md-4 mb-3">
                    <label for="rfc" class="form-label">RFC *</label>
                    <input type="text" 
                           class="form-control" 
                           id="rfc" 
                           name="rfc"
                           placeholder="ABC123456XYZ"
                           maxlength="13"
                           required>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="email" class="form-label">Email *</label>
                    <input type="email" 
                           class="form-control" 
                           id="email" 
                           name="email"
                           placeholder="contacto@empresa.com"
                           required>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label for="telefono" class="form-label">Teléfono</label>
                    <input type="tel" 
                           class="form-control" 
                           id="telefono" 
                           name="telefono"
                           placeholder="555-1234-567">
                </div>
            </div>
            
            <div class="mb-3">
                <label for="direccion" class="form-label">Dirección</label>
                <textarea class="form-control" 
                          id="direccion" 
                          name="direccion" 
                          rows="2"
                          placeholder="Calle, número, colonia, ciudad, código postal"></textarea>
            </div>
            
            <hr class="my-4">
            
            <h5 class="mb-3">
                <i class="fas fa-user-tie me-2 text-primary"></i>
                Contacto Principal
            </h5>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="contacto_principal" class="form-label">Nombre del Contacto</label>
                    <input type="text" 
                           class="form-control" 
                           id="contacto_principal" 
                           name="contacto_principal"
                           placeholder="Nombre completo del contacto">
                </div>
            </div>
            
            <hr class="my-4">
            
            <div class="d-flex justify-content-end gap-2">
                <a href="/proveedores" class="btn btn-secondary">
                    <i class="fas fa-times me-2"></i>
                    Cancelar
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-2"></i>
                    Guardar Proveedor
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
