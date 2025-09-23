exports.up = pgm => {
  // 1. Tabla principal para Insumos y Servicios
  pgm.createTable('catalog_items', {
    id: 'id',
    type: { type: 'varchar(50)', notNull: true }, // 'PRODUCT' o 'SERVICE'
    sku: { type: 'varchar(100)', unique: true },
    name: { type: 'varchar(255)', notNull: true },
    description: { type: 'text' },
    cost: { type: 'decimal(10, 2)', notNull: true, default: 0 },
    price: { type: 'decimal(10, 2)', notNull: true, default: 0 },
    stock_quantity: { type: 'integer', notNull: true, default: 0 },
    is_active: { type: 'boolean', default: true, notNull: true },
    created_at: {
      type: 'timestamp',
      notNull: true,
      default: pgm.func('current_timestamp'),
    },
  });

  // 2. Tabla para registrar ajustes de inventario
  pgm.createTable('stock_adjustments', {
    id: 'id',
    item_id: {
      type: 'integer',
      notNull: true,
      references: '"catalog_items"',
      onDelete: 'cascade',
    },
    user_id: { // Para saber quién hizo el ajuste
      type: 'integer',
      notNull: true,
      references: '"users"',
      onDelete: 'set null',
    },
    quantity_before: { type: 'integer', notNull: true },
    quantity_after: { type: 'integer', notNull: true },
    reason: { type: 'text', notNull: true },
    created_at: {
      type: 'timestamp',
      notNull: true,
      default: pgm.func('current_timestamp'),
    },
  });
};

exports.down = pgm => {
  pgm.dropTable('stock_adjustments');
  pgm.dropTable('catalog_items');
};