exports.up = pgm => {
  // 1. Tabla de Permisos
  pgm.createTable('permissions', {
    id: 'id',
    action: { type: 'varchar(255)', notNull: true },
    subject: { type: 'varchar(255)', notNull: true },
    created_at: {
      type: 'timestamp',
      notNull: true,
      default: pgm.func('current_timestamp'),
    },
  });
  pgm.addConstraint('permissions', 'permissions_unique_action_subject', {
    unique: ['action', 'subject']
  });

  // 2. Tabla de Roles
  pgm.createTable('roles', {
    id: 'id',
    name: { type: 'varchar(255)', notNull: true, unique: true },
    description: { type: 'text' },
    created_at: {
      type: 'timestamp',
      notNull: true,
      default: pgm.func('current_timestamp'),
    },
  });

  // 3. Tabla de Usuarios
  pgm.createTable('users', {
    id: 'id',
    name: { type: 'varchar(255)', notNull: true },
    email: { type: 'varchar(255)', notNull: true, unique: true },
    password_hash: { type: 'varchar(255)', notNull: true },
    role_id: {
      type: 'integer',
      notNull: true,
      references: '"roles"',
      onDelete: 'cascade',
    },
    is_active: { type: 'boolean', default: true, notNull: true },
    created_at: {
      type: 'timestamp',
      notNull: true,
      default: pgm.func('current_timestamp'),
    },
  });

  // 4. Tabla Pivote para Roles y Permisos (Relación Muchos a Muchos)
  pgm.createTable('role_permissions', {
    role_id: {
      type: 'integer',
      notNull: true,
      references: '"roles"',
      onDelete: 'cascade',
    },
    permission_id: {
      type: 'integer',
      notNull: true,
      references: '"permissions"',
      onDelete: 'cascade',
    },
  });
  pgm.addConstraint('role_permissions', 'role_permissions_pkey', {
    primaryKey: ['role_id', 'permission_id']
  });
};

exports.down = pgm => {
  pgm.dropTable('role_permissions');
  pgm.dropTable('users');
  pgm.dropTable('roles');
  pgm.dropTable('permissions');
};