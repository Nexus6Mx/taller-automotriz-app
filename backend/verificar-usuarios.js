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

async function getUsers() {
  console.log('Conectando a la base de datos para verificar usuarios...');
  const client = await pool.connect();
  try {
    const result = await client.query('SELECT id, name, email, role_id, is_active FROM users');

    if (result.rows.length === 0) {
      console.log('La tabla de usuarios está vacía.');
    } else {
      console.log('¡Usuario(s) encontrado(s) en la base de datos! ✅');
      // console.table muestra los resultados en una tabla bonita
      console.table(result.rows);
    }
  } catch (error) {
    console.error('Error al consultar la base de datos:', error);
  } finally {
    // Cerramos la conexión para no dejarla abierta
    client.release();
    pool.end();
  }
}

getUsers();