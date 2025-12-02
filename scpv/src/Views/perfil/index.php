<div class="mb-4">
    <h1 class="h3 mb-0">
        <i class="fas fa-user-circle text-primary me-2"></i>
        Mi Perfil
    </h1>
    <p class="text-muted">Información de tu cuenta y configuración personal</p>
</div>

<div class="row">
    <!-- Información del Perfil -->
    <div class="col-lg-8">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-id-card me-2"></i>
                    Información Personal
                </h5>
            </div>
            <div class="card-body">
                <form method="POST" action="/perfil/update">
                    <input type="hidden" name="_token" value="<?= $csrf_token ?>">
                    
                    <div class="mb-3">
                        <label for="nombre" class="form-label">Nombre Completo</label>
                        <input type="text" 
                               class="form-control" 
                               id="nombre" 
                               name="nombre" 
                               value="<?= htmlspecialchars($user['nombre']) ?>"
                               required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" 
                               class="form-control" 
                               id="email" 
                               name="email" 
                               value="<?= htmlspecialchars($user['email']) ?>"
                               required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Rol</label>
                        <input type="text" 
                               class="form-control" 
                               value="<?= ucfirst($user['rol']) ?>" 
                               disabled>
                        <small class="text-muted">El rol no puede ser modificado por ti mismo</small>
                    </div>
                    
                    <div class="d-flex justify-content-end gap-2">
                        <a href="/dashboard" class="btn btn-secondary">
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
        
        <!-- Cambiar Contraseña -->
        <div class="card">
            <div class="card-header bg-warning text-dark">
                <h5 class="mb-0">
                    <i class="fas fa-key me-2"></i>
                    Seguridad
                </h5>
            </div>
            <div class="card-body">
                <p class="mb-3">
                    <i class="fas fa-shield-alt me-2 text-warning"></i>
                    Mantén tu cuenta segura cambiando tu contraseña regularmente
                </p>
                <a href="/perfil/change-password" class="btn btn-warning">
                    <i class="fas fa-key me-2"></i>
                    Cambiar Contraseña
                </a>
            </div>
        </div>
    </div>
    
    <!-- Panel Lateral -->
    <div class="col-lg-4">
        <!-- Información de la Cuenta -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h6 class="mb-0">
                    <i class="fas fa-info-circle me-2"></i>
                    Información de la Cuenta
                </h6>
            </div>
            <div class="card-body">
                <div class="text-center mb-3">
                    <div class="avatar-circle bg-primary text-white mb-3" style="width: 80px; height: 80px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto; font-size: 2rem;">
                        <?= strtoupper(substr($user['nombre'], 0, 1)) ?>
                    </div>
                    <h5 class="mb-1"><?= htmlspecialchars($user['nombre']) ?></h5>
                    <span class="badge bg-<?= $user['rol'] === 'admin' ? 'danger' : ($user['rol'] === 'comprador' ? 'primary' : 'info') ?>">
                        <?= ucfirst($user['rol']) ?>
                    </span>
                </div>
                
                <hr>
                
                <div class="small">
                    <p class="mb-2">
                        <i class="fas fa-envelope me-2 text-muted"></i>
                        <?= htmlspecialchars($user['email']) ?>
                    </p>
                    <?php if (isset($user['ultimo_acceso'])): ?>
                        <p class="mb-2">
                            <i class="fas fa-clock me-2 text-muted"></i>
                            Último acceso: <?= date('d/m/Y H:i', strtotime($user['ultimo_acceso'])) ?>
                        </p>
                    <?php endif; ?>
                    <p class="mb-0">
                        <i class="fas fa-user-check me-2 text-success"></i>
                        Cuenta activa
                    </p>
                </div>
            </div>
        </div>
        
        <!-- Estadísticas del Usuario -->
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="fas fa-chart-line me-2"></i>
                    Tu Actividad
                </h6>
            </div>
            <div class="card-body text-center">
                <p class="text-muted">
                    <i class="fas fa-info-circle"></i><br>
                    Estadísticas de usuario disponibles próximamente
                </p>
            </div>
        </div>
    </div>
</div>
