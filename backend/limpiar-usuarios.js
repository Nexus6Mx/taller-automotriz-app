require('dotenv').config();
const { Pool } = require('pg');

const pool = new Pool({
  connectionString: process.env.DATABASE_URL,
  ssl: {
    rejectUnauthorized: false
  }
});

async function clearUsers() {
  console.log('Conectando a la base de datos para limpiar la tabla de usuarios...');
  const client = await pool.connect();
  try {
    await client.query('DELETE FROM users');
    console.log('✅ Tabla de usuarios limpiada exitosamente.');
  } catch (error) {
    console.error('Error al limpiar la tabla:', error);
  } finally {
    client.release();
    pool.end();
  }
}

clearUsers();