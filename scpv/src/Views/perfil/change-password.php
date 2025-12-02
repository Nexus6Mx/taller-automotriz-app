<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="mb-4">
            <h1 class="h3 mb-0">
                <i class="fas fa-key text-warning me-2"></i>
                Cambiar Contraseña
            </h1>
            <p class="text-muted">Actualiza tu contraseña para mantener tu cuenta segura</p>
        </div>
        
        <div class="card">
            <div class="card-body">
                <div class="alert alert-info">
                    <i class="fas fa-shield-alt me-2"></i>
                    <strong>Recomendaciones:</strong>
                    <ul class="mb-0 mt-2">
                        <li>Usa al menos 6 caracteres</li>
                        <li>Combina letras mayúsculas y minúsculas</li>
                        <li>Incluye números y caracteres especiales</li>
                        <li>No uses contraseñas obvias o fáciles de adivinar</li>
                    </ul>
                </div>
                
                <form method="POST" action="/perfil/change-password">
                    <input type="hidden" name="_token" value="<?= $csrf_token ?>">
                    
                    <div class="mb-3">
                        <label for="current_password" class="form-label">Contraseña Actual *</label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="fas fa-lock"></i>
                            </span>
                            <input type="password" 
                                   class="form-control" 
                                   id="current_password" 
                                   name="current_password"
                                   placeholder="Ingresa tu contraseña actual"
                                   required>
                        </div>
                    </div>
                    
                    <hr class="my-4">
                    
                    <div class="mb-3">
                        <label for="new_password" class="form-label">Nueva Contraseña *</label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="fas fa-key"></i>
                            </span>
                            <input type="password" 
                                   class="form-control" 
                                   id="new_password" 
                                   name="new_password"
                                   placeholder="Ingresa tu nueva contraseña"
                                   minlength="6"
                                   required>
                        </div>
                        <small class="text-muted">Mínimo 6 caracteres</small>
                    </div>
                    
                    <div class="mb-4">
                        <label for="confirm_password" class="form-label">Confirmar Nueva Contraseña *</label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="fas fa-check-circle"></i>
                            </span>
                            <input type="password" 
                                   class="form-control" 
                                   id="confirm_password" 
                                   name="confirm_password"
                                   placeholder="Confirma tu nueva contraseña"
                                   minlength="6"
                                   required>
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-end gap-2">
                        <a href="/perfil" class="btn btn-secondary">
                            <i class="fas fa-times me-2"></i>
                            Cancelar
                        </a>
                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-save me-2"></i>
                            Cambiar Contraseña
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Información Adicional -->
        <div class="card mt-4">
            <div class="card-body">
                <h6 class="card-title">
                    <i class="fas fa-question-circle me-2 text-info"></i>
                    ¿Olvidaste tu contraseña?
                </h6>
                <p class="card-text text-muted mb-0">
                    Si no recuerdas tu contraseña actual, contacta con un administrador del sistema para que pueda restablecerla.
                </p>
            </div>
        </div>
    </div>
</div>

<script>
// Validar que las contraseñas coincidan
document.querySelector('form').addEventListener('submit', function(e) {
    const newPass = document.getElementById('new_password').value;
    const confirmPass = document.getElementById('confirm_password').value;
    
    if (newPass !== confirmPass) {
        e.preventDefault();
        alert('Las contraseñas no coinciden');
        document.getElementById('confirm_password').focus();
    }
});
</script>
