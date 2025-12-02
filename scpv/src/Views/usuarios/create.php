<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">
        <i class="fas fa-user-plus text-primary me-2"></i>
        Nuevo Usuario
    </h1>
    <a href="/usuarios" class="btn btn-secondary">
        <i class="fas fa-arrow-left me-2"></i>
        Volver
    </a>
</div>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body">
                <form method="POST" action="/usuarios">
                    <input type="hidden" name="_token" value="<?= $csrf_token ?>">
                    
                    <div class="mb-3">
                        <label for="nombre" class="form-label">Nombre Completo *</label>
                        <input type="text" 
                               class="form-control" 
                               id="nombre" 
                               name="nombre" 
                               placeholder="Ej: Juan Pérez García"
                               required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">Email *</label>
                        <input type="email" 
                               class="form-control" 
                               id="email" 
                               name="email"
                               placeholder="usuario@empresa.com"
                               required>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="password" class="form-label">Contraseña *</label>
                            <input type="password" 
                                   class="form-control" 
                                   id="password" 
                                   name="password"
                                   minlength="6"
                                   required>
                            <small class="text-muted">Mínimo 6 caracteres</small>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="rol" class="form-label">Rol *</label>
                            <select class="form-select" id="rol" name="rol" required>
                                <option value="">Selecciona un rol</option>
                                <option value="admin">Administrador</option>
                                <option value="comprador">Comprador</option>
                                <option value="proveedor">Proveedor</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Roles:</strong>
                        <ul class="mb-0 mt-2">
                            <li><strong>Administrador:</strong> Acceso completo al sistema</li>
                            <li><strong>Comprador:</strong> Gestión de cotizaciones y proveedores</li>
                            <li><strong>Proveedor:</strong> Ver y responder cotizaciones</li>
                        </ul>
                    </div>
                    
                    <hr class="my-4">
                    
                    <div class="d-flex justify-content-end gap-2">
                        <a href="/usuarios" class="btn btn-secondary">
                            <i class="fas fa-times me-2"></i>
                            Cancelar
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>
                            Crear Usuario
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
