<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">
        <i class="fas fa-user-edit text-primary me-2"></i>
        Editar Usuario
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
                <form method="POST" action="/usuarios/<?= $usuario['id'] ?>/update">
                    <input type="hidden" name="_token" value="<?= $csrf_token ?>">
                    
                    <div class="mb-3">
                        <label for="nombre" class="form-label">Nombre Completo *</label>
                        <input type="text" 
                               class="form-control" 
                               id="nombre" 
                               name="nombre" 
                               value="<?= htmlspecialchars($usuario['nombre']) ?>"
                               required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">Email *</label>
                        <input type="email" 
                               class="form-control" 
                               id="email" 
                               name="email"
                               value="<?= htmlspecialchars($usuario['email']) ?>"
                               required>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="password" class="form-label">Nueva Contraseña</label>
                            <input type="password" 
                                   class="form-control" 
                                   id="password" 
                                   name="password"
                                   minlength="6">
                            <small class="text-muted">Dejar en blanco para mantener la actual</small>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="rol" class="form-label">Rol *</label>
                            <select class="form-select" id="rol" name="rol" required>
                                <option value="admin" <?= $usuario['rol'] === 'admin' ? 'selected' : '' ?>>Administrador</option>
                                <option value="comprador" <?= $usuario['rol'] === 'comprador' ? 'selected' : '' ?>>Comprador</option>
                                <option value="proveedor" <?= $usuario['rol'] === 'proveedor' ? 'selected' : '' ?>>Proveedor</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" 
                                   type="checkbox" 
                                   id="activo" 
                                   name="activo" 
                                   value="1"
                                   <?= $usuario['activo'] ? 'checked' : '' ?>>
                            <label class="form-check-label" for="activo">
                                Usuario Activo
                            </label>
                        </div>
                    </div>
                    
                    <hr class="my-4">
                    
                    <div class="d-flex justify-content-end gap-2">
                        <a href="/usuarios" class="btn btn-secondary">
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
    </div>
</div>
