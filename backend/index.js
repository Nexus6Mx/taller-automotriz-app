// Carga las variables de nuestro archivo .env
require('dotenv').config();

// Importa las herramientas que instalamos
const express = require('express');
const { Pool } = require('pg');
const cors = require('cors'); // NUEVO: Importamos la herramienta del "portero"

// Crea nuestra aplicación (el servidor)
const app = express();
const PORT = process.env.PORT || 3001;

// --- CONFIGURACIÓN NUEVA ---
// NUEVO: Le decimos a nuestro servidor que use el "portero" (cors)
app.use(cors()); 
// NUEVO: Le decimos al servidor que entienda el formato JSON, que es como se comunicará con el frontend
app.use(express.json()); 

// Crea un "administrador de conexiones" para hablar con la base de datos
const pool = new Pool({
  connectionString: process.env.DATABASE_URL,
  ssl: {
    rejectUnauthorized: false
  }
});

// Creamos una ruta de prueba para verificar que todo funciona
app.get('/api/health-check', async (req, res) => {
  try {
    const client = await pool.connect();
    res.status(200).json({ message: '¡Éxito! Conexión con la base de datos funcionando.' });
    client.release();
  } catch (error) {
    console.error('Falló la conexión a la base de datos:', error);
    res.status(500).json({ message: 'No se pudo conectar a la base de datos.' });
  }
});
// --- LÓGICA PARA USUARIOS ---

// Importamos la herramienta para encriptar
const bcrypt = require('bcryptjs');

// Endpoint para registrar un usuario nuevo
app.post('/api/users/register', async (req, res) => {
  // req.body es la información que nos enviará el formulario de registro
  const { name, email, password } = req.body;

  try {
    // Paso 1: Encriptar la contraseña
    // Generamos un "ingrediente secreto" para hacer la encriptación más fuerte
    const salt = await bcrypt.genSalt(10);
    // Revolvemos la contraseña del usuario con el ingrediente secreto
    const passwordHash = await bcrypt.hash(password, salt);

    // Paso 2: Guardar el usuario en la base de datos
    // OJO: Todavía no hemos creado la tabla 'users'. Esto es solo para tener la lógica lista.
    // En la próxima fase crearemos la tabla para que este código funcione.

    // Simulación de guardado:
    console.log('Usuario a registrar:', { name, email });
    console.log('Contraseña encriptada:', passwordHash);

    // Cuando tengamos la tabla, aquí irá el código para guardarlo.
    // Por ahora, solo respondemos con un mensaje de éxito.
    res.status(201).json({ 
        message: 'Usuario registrado exitosamente (simulación)',
        user: { name, email }
    });

  } catch (error) {
    console.error('Error al registrar usuario:', error);
    res.status(500).json({ message: 'Error en el servidor' });
  }
});
// Ponemos a "escuchar" nuestro servidor para que atienda peticiones
app.listen(PORT, () => {
  console.log(`Servidor iniciado y escuchando en el puerto ${PORT}`);
});