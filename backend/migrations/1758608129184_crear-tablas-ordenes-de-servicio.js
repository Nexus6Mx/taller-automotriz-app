exports.up = pgm => {
  // 1. Tabla Principal de Órdenes de Servicio
  pgm.createTable('service_orders', {
    id: 'id',
    folio: { type: 'varchar(255)', notNull: true, unique: true },
    client_id: { type: 'integer', notNull: true, references: '"clients"', onDelete: 'cascade' },
    vehicle_id: { type: 'integer', notNull: true, references: '"vehicles"', onDelete: 'cascade' },
    status: { type: 'varchar(50)', notNull: true, default: 'Recibido' },
    customer_reported_fault: { type: 'text', notNull: true },
    technical_diagnosis: { type: 'text' },
    created_at: {
      type: 'timestamp',
      notNull: true,
      default: pgm.func('current_timestamp'),
    },
    updated_at: {
      type: 'timestamp',
      notNull: true,
      default: pgm.func('current_timestamp'),
    }
  });

  // 2. Tabla para los Ítems de cada Orden
  pgm.createTable('order_items', {
    id: 'id',
    order_id: { type: 'integer', notNull: true, references: '"service_orders"', onDelete: 'cascade' },
    item_id: { type: 'integer', notNull: true, references: '"catalog_items"', onDelete: 'cascade' },
    description: { type: 'varchar(255)', notNull: true },
    quantity: { type: 'decimal(10, 2)', notNull: true },
    unit_price: { type: 'decimal(10, 2)', notNull: true },
  });
};

exports.down = pgm => {
  pgm.dropTable('order_items');
  pgm.dropTable('service_orders');
};

