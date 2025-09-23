exports.up = pgm => {
  // 1. Tabla de Clientes
  pgm.createTable('clients', {
    id: 'id',
    name: { type: 'varchar(255)', notNull: true },
    phone: { type: 'varchar(50)' },
    email: { type: 'varchar(255)' },
    billing_name: { type: 'varchar(255)' }, // Razón Social
    billing_rfc: { type: 'varchar(20)' },    // RFC
    billing_tax_regime: { type: 'varchar(255)' }, // Régimen Fiscal
    billing_address: { type: 'text' },       // Domicilio Fiscal
    created_at: {
      type: 'timestamp',
      notNull: true,
      default: pgm.func('current_timestamp'),
    },
  });

  // 2. Tabla de Vehículos
  pgm.createTable('vehicles', {
    id: 'id',
    client_id: {
      type: 'integer',
      notNull: true,
      references: '"clients"',
      onDelete: 'cascade', // Si se borra un cliente, se borran sus vehículos
    },
    brand: { type: 'varchar(100)' },
    model: { type: 'varchar(100)' },
    year: { type: 'integer' },
    license_plate: { type: 'varchar(20)', unique: true },
    vin: { type: 'varchar(100)', unique: true },
    created_at: {
      type: 'timestamp',
      notNull: true,
      default: pgm.func('current_timestamp'),
    },
  });
};

exports.down = pgm => {
  pgm.dropTable('vehicles');
  pgm.dropTable('clients');
};
