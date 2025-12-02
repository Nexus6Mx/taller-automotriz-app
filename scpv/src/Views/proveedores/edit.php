<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">
        <i class="fas fa-edit text-primary me-2"></i>
        Editar Proveedor
    </h1>
    <a href="/proveedores" class="btn btn-secondary">
        <i class="fas fa-arrow-left me-2"></i>
        Volver
    </a>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="/proveedores/<?= $proveedor['id'] ?>/update">
            <input type="hidden" name="_token" value="<?= $csrf_token ?>">
            
            <h5 class="mb-3">
                <i class="fas fa-building me-2 text-primary"></i>
                Información de la Empresa
            </h5>
            
            <div class="row">
                <div class="col-md-8 mb-3">
                    <label for="razon_social" class="form-label">Razón Social *</label>
                    <input type="text" 
                           class="form-control" 
                           id="razon_social" 
                           name="razon_social" 
                           value="<?= htmlspecialchars($proveedor['razon_social']) ?>"
                           required>
                </div>
                
                <div class="col-md-4 mb-3">
                    <label for="rfc" class="form-label">RFC *</label>
                    <input type="text" 
                           class="form-control" 
                           id="rfc" 
                           name="rfc"
                           value="<?= htmlspecialchars($proveedor['rfc']) ?>"
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
                           value="<?= htmlspecialchars($proveedor['email']) ?>"
                           required>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label for="telefono" class="form-label">Teléfono</label>
                    <input type="tel" 
                           class="form-control" 
                           id="telefono" 
                           name="telefono"
                           value="<?= htmlspecialchars($proveedor['telefono'] ?? '') ?>">
                </div>
            </div>
            
            <div class="mb-3">
                <label for="direccion" class="form-label">Dirección</label>
                <textarea class="form-control" 
                          id="direccion" 
                          name="direccion" 
                          rows="2"><?= htmlspecialchars($proveedor['direccion'] ?? '') ?></textarea>
            </div>
            
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label for="ciudad" class="form-label">Ciudad</label>
                    <input type="text" 
                           class="form-control" 
                           id="ciudad" 
                           name="ciudad"
                           value="<?= htmlspecialchars($proveedor['ciudad'] ?? '') ?>">
                </div>
                
                <div class="col-md-4 mb-3">
                    <label for="estado" class="form-label">Estado</label>
                    <input type="text" 
                           class="form-control" 
                           id="estado" 
                           name="estado"
                           value="<?= htmlspecialchars($proveedor['estado'] ?? '') ?>">
                </div>
                
                <div class="col-md-4 mb-3">
                    <label for="codigo_postal" class="form-label">Código Postal</label>
                    <input type="text" 
                           class="form-control" 
                           id="codigo_postal" 
                           name="codigo_postal"
                           value="<?= htmlspecialchars($proveedor['codigo_postal'] ?? '') ?>"
                           maxlength="10">
                </div>
            </div>
            
            <hr class="my-4">
            
            <h5 class="mb-3">
                <i class="fas fa-user-tie me-2 text-primary"></i>
                Contacto Principal
            </h5>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="contacto_nombre" class="form-label">Nombre del Contacto</label>
                    <input type="text" 
                           class="form-control" 
                           id="contacto_nombre" 
                           name="contacto_nombre"
                           value="<?= htmlspecialchars($proveedor['contacto_nombre'] ?? '') ?>">
                </div>
                
                <div class="col-md-3 mb-3">
                    <label for="contacto_telefono" class="form-label">Teléfono</label>
                    <input type="tel" 
                           class="form-control" 
                           id="contacto_telefono" 
                           name="contacto_telefono"
                           value="<?= htmlspecialchars($proveedor['contacto_telefono'] ?? '') ?>">
                </div>
                
                <div class="col-md-3 mb-3">
                    <label for="contacto_email" class="form-label">Email</label>
                    <input type="email" 
                           class="form-control" 
                           id="contacto_email" 
                           name="contacto_email"
                           value="<?= htmlspecialchars($proveedor['contacto_email'] ?? '') ?>">
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
                    Guardar Cambios
                </button>
            </div>
        </form>
    </div>
</div>
