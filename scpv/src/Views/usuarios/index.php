<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">
        <i class="fas fa-users text-primary me-2"></i>
        Gestión de Usuarios
    </h1>
    <a href="/usuarios/create" class="btn btn-primary">
        <i class="fas fa-user-plus me-2"></i>
        Nuevo Usuario
    </a>
</div>

<!-- Lista de Usuarios -->
<div class="card">
    <div class="card-body">
        <?php if (empty($usuarios)): ?>
            <div class="text-center py-5">
                <i class="fas fa-user-slash fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">No hay usuarios registrados</h5>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Email</th>
                            <th>Rol</th>
                            <th>Estado</th>
                            <th>Último Acceso</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($usuarios as $usuario): ?>
                            <tr>
                                <td><?= $usuario['id'] ?></td>
                                <td>
                                    <i class="fas fa-user-circle me-2 text-primary"></i>
                                    <strong><?= htmlspecialchars($usuario['nombre']) ?></strong>
                                </td>
                                <td><?= htmlspecialchars($usuario['email']) ?></td>
                                <td>
                                    <?php
                                    $roleBadges = [
                                        'admin' => 'danger',
                                        'comprador' => 'primary',
                                        'proveedor' => 'info'
                                    ];
                                    $badge = $roleBadges[$usuario['rol']] ?? 'secondary';
                                    ?>
                                    <span class="badge bg-<?= $badge ?>">
                                        <?= ucfirst($usuario['rol']) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($usuario['activo']): ?>
                                        <span class="badge bg-success">
                                            <i class="fas fa-check-circle"></i> Activo
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">
                                            <i class="fas fa-times-circle"></i> Inactivo
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($usuario['ultimo_acceso']): ?>
                                        <small><?= date('d/m/Y H:i', strtotime($usuario['ultimo_acceso'])) ?></small>
                                    <?php else: ?>
                                        <small class="text-muted">Nunca</small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="/usuarios/<?= $usuario['id'] ?>/edit" 
                                           class="btn btn-outline-primary"
                                           title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <?php if ($usuario['id'] != $user['id']): ?>
                                            <button type="button" 
                                                    class="btn btn-outline-danger"
                                                    onclick="confirmarEliminar(<?= $usuario['id'] ?>)"
                                                    title="Desactivar">
                                                <i class="fas fa-user-times"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function confirmarEliminar(id) {
    if (confirm('¿Estás seguro de desactivar este usuario?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '/usuarios/' + id + '/delete';
        document.body.appendChild(form);
        form.submit();
    }
}
</script>
