require('dotenv').config();
const express = require('express');
const { Pool } = require('pg');
const cors = require('cors');
const bcrypt = require('bcryptjs');
const jwt = require('jsonwebtoken');
const authMiddleware = require('./middleware/auth');

const app = express();
const PORT = process.env.PORT || 3001;

app.use(cors()); 
app.use(express.json()); 

const pool = new Pool({
  connectionString: process.env.DATABASE_URL,
  ssl: {
    rejectUnauthorized: false
  }
});

// --- RUTAS PÚBLICAS ---
app.post('/api/auth/login', async (req, res) => {
    const { email, password } = req.body;
    let client;
    try {
        client = await pool.connect();
        const userResult = await client.query('SELECT * FROM users WHERE email = $1', [email]);
        if (userResult.rows.length === 0) {
            return res.status(401).json({ message: 'Credenciales inválidas' });
        }
        const user = userResult.rows[0];
        const isMatch = await bcrypt.compare(password, user.password_hash);
        if (!isMatch) {
            return res.status(401).json({ message: 'Credenciales inválidas' });
        }
        const payload = { userId: user.id, roleId: user.role_id, name: user.name };
        const token = jwt.sign(payload, process.env.JWT_SECRET, { expiresIn: '8h' });
        res.json({ 
            message: `Bienvenido de vuelta, ${user.name}!`,
            token: token,
            user: { id: user.id, name: user.name, email: user.email }
        });
    } catch (error) {
        console.error('Error en el login:', error);
        res.status(500).json({ message: 'Error en el servidor' });
    } finally {
        if (client) client.release();
    }
});

// --- RUTAS PROTEGIDAS ---

// Ruta de prueba
app.get('/api/test-protegido', authMiddleware, (req, res) => {
    res.json({ 
      message: `¡Hola, ${req.user.name}! Has accedido a una ruta protegida.`,
      userData: req.user 
    });
});

// --- NUEVO: RUTAS PARA CLIENTES ---

// OBTENER TODOS LOS CLIENTES
app.get('/api/clients', authMiddleware, async (req, res) => {
    let client;
    try {
        client = await pool.connect();
        const result = await client.query('SELECT * FROM clients ORDER BY name');
        res.json(result.rows);
    } catch (error) {
        console.error('Error al obtener clientes:', error);
        res.status(500).json({ message: 'Error en el servidor' });
    } finally {
        if (client) client.release();
    }
});

// CREAR UN NUEVO CLIENTE
app.post('/api/clients', authMiddleware, async (req, res) => {
    // Solo tomamos los campos del formulario de contacto por ahora
    const { name, phone, email } = req.body; 
    let dbClient;
    try {
        dbClient = await pool.connect();
        const query = `
            INSERT INTO clients (name, phone, email)
            VALUES ($1, $2, $3)
            RETURNING *;
        `;
        const values = [name, phone, email];
        const result = await dbClient.query(query, values);
        res.status(201).json(result.rows[0]);
    } catch (error) {
        console.error('Error al crear cliente:', error);
        res.status(500).json({ message: 'Error en el servidor' });
    } finally {
        if (dbClient) dbClient.release();
    }
});


app.listen(PORT, () => {
  console.log(`Servidor iniciado y escuchando en el puerto ${PORT}`);
});