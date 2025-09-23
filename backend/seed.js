// Importamos las herramientas necesarias
require('dotenv').config();
const { Pool } = require('pg');

// Creamos la conexión a la base de datos
const pool = new Pool({
  connectionString: process.env.DATABASE_URL,
  ssl: {
    rejectUnauthorized: false
  }
});

// --- DATOS INICIALES BASADOS EN TUS REQUERIMIENTOS ---

// Lista de roles iniciales [cite: 6]
const roles = [
  { name: 'Administrador', description: 'Acceso total al sistema' },
  { name: 'Asesor', description: 'Acceso a la gestión de clientes y órdenes de servicio' },
  { name: 'Técnico', description: 'Acceso para ver y actualizar el estado de las reparaciones' },
];

// Lista de permisos iniciales [cite: 11-16]
const permissions = [
  // Órdenes de Servicio
  { action: 'Ver Todas', subject: 'Órdenes de Servicio' },
  { action: 'Crear', subject: 'Órdenes de Servicio' },
  { action: 'Editar', subject: 'Órdenes de Servicio' },
  // Clientes y Vehículos
  { action: 'Ver', subject: 'Clientes y Vehículos' },
  { action: 'Crear', subject: 'Clientes y Vehículos' },
  { action: 'Editar', subject: 'Clientes y Vehículos' },
  // Facturación
  { action: 'Generar Factura', subject: 'Facturación' },
  { action: 'Registrar Pagos', subject: 'Facturación' },
  // ... puedes añadir todos los demás permisos de tu documento aquí
];


// --- FUNCIÓN PRINCIPAL PARA SEMBRAR LA BASE DE DATOS ---
async function seedDatabase() {
  const client = await pool.connect();
  try {
    console.log('Iniciando siembra de datos...');

    // Insertar los roles
    await client.query('BEGIN'); // Iniciar transacción
    for (const role of roles) {
      await client.query('INSERT INTO roles (name, description) VALUES ($1, $2)', [role.name, role.description]);
    }
    console.log('✅ Roles creados exitosamente.');

    // Insertar los permisos
    for (const perm of permissions) {
      await client.query('INSERT INTO permissions (action, subject) VALUES ($1, $2)', [perm.action, perm.subject]);
    }
    console.log('✅ Permisos creados exitosamente.');

    // --- Asignar TODOS los permisos al rol de Administrador ---
    // Obtenemos el ID del rol "Administrador"
    const adminRole = await client.query("SELECT id FROM roles WHERE name = 'Administrador'");
    const adminRoleId = adminRole.rows[0].id;

    // Obtenemos los IDs de TODOS los permisos
    const allPermissions = await client.query("SELECT id FROM permissions");

    for (const perm of allPermissions.rows) {
      await client.query('INSERT INTO role_permissions (role_id, permission_id) VALUES ($1, $2)', [adminRoleId, perm.id]);
    }
    console.log('✅ Todos los permisos asignados al rol de Administrador.');


    await client.query('COMMIT'); // Finalizar transacción
    console.log('\n¡Siembra de datos completada exitosamente! 🌱');

  } catch (error) {
    await client.query('ROLLBACK'); // Revertir todo si algo falla
    console.error('Error durante la siembra de datos:', error);
  } finally {
    client.release();
    pool.end();
  }
}

// Ejecutar la función
seedDatabase();