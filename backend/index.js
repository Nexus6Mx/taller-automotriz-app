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

// --- RUTAS DE AUTENTICACIÓN Y USUARIOS ---
app.post('/api/users/register', async (req, res) => {
    // ... (código de registro existente)
});

app.post('/api/auth/login', async (req, res) => {
    // ... (código de login existente)
});

// --- RUTAS DE CLIENTES ---
app.get('/api/clients', authMiddleware, async (req, res) => {
    // ... (código existente)
});
app.post('/api/clients', authMiddleware, async (req, res) => {
    // ... (código existente)
});
app.put('/api/clients/:id', authMiddleware, async (req, res) => {
    // ... (código existente)
});
app.delete('/api/clients/:id', authMiddleware, async (req, res) => {
    // ... (código existente)
});
app.get('/api/clients/:id', authMiddleware, async (req, res) => {
    // ... (código existente)
});

// --- RUTAS DE VEHÍCULOS ---
app.get('/api/clients/:clientId/vehicles', authMiddleware, async (req, res) => {
    // ... (código existente)
});
app.post('/api/clients/:clientId/vehicles', authMiddleware, async (req, res) => {
    // ... (código existente)
});
app.put('/api/vehicles/:id', authMiddleware, async (req, res) => {
    // ... (código existente)
});
app.delete('/api/vehicles/:id', authMiddleware, async (req, res) => {
    // ... (código existente)
});

// --- RUTAS DE CATÁLOGO ---
app.get('/api/catalog-items', authMiddleware, async (req, res) => {
    // ... (código existente)
});
app.post('/api/catalog-items', authMiddleware, async (req, res) => {
    // ... (código existente)
});
app.put('/api/catalog-items/:id', authMiddleware, async (req, res) => {
    // ... (código existente)
});
app.delete('/api/catalog-items/:id', authMiddleware, async (req, res) => {
    // ... (código existente)
});

// --- NUEVO: RUTAS DE ÓRDENES DE SERVICIO ---
// GET: Obtener todas las órdenes de servicio
app.get('/api/service-orders', authMiddleware, async (req, res) => {
    let client;
    try {
        client = await pool.connect();
        // Unimos tablas para obtener nombres en lugar de solo IDs
        const query = `
            SELECT 
                so.id, so.folio, so.status, so.created_at,
                c.name as client_name,
                v.brand, v.model, v.license_plate
            FROM service_orders so
            JOIN clients c ON so.client_id = c.id
            JOIN vehicles v ON so.vehicle_id = v.id
            ORDER BY so.created_at DESC;
        `;
        const result = await client.query(query);
        res.json(result.rows);
    } catch (error) {
        console.error('Error al obtener órdenes de servicio:', error);
        res.status(500).json({ message: 'Error en el servidor' });
    } finally {
        if (client) client.release();
    }
});

// POST: Crear una nueva orden de servicio (versión inicial)
app.post('/api/service-orders', authMiddleware, async (req, res) => {
    const { client_id, vehicle_id, customer_reported_fault } = req.body;
    if (!client_id || !vehicle_id || !customer_reported_fault) {
        return res.status(400).json({ message: 'Cliente, vehículo y falla reportada son requeridos.' });
    }

    let client;
    try {
        client = await pool.connect();
        // Generar un folio único (ej. OS-timestamp)
        const folio = `OS-${Date.now()}`;
        const query = `
            INSERT INTO service_orders (folio, client_id, vehicle_id, customer_reported_fault, status)
            VALUES ($1, $2, $3, $4, 'Recibido')
            RETURNING *;
        `;
        const values = [folio, client_id, vehicle_id, customer_reported_fault];
        const result = await client.query(query, values);
        res.status(201).json(result.rows[0]);
    } catch (error) {
        console.error('Error al crear orden de servicio:', error);
        res.status(500).json({ message: 'Error en el servidor' });
    } finally {
        if (client) client.release();
    }
});


app.listen(PORT, () => {
  console.log(`Servidor iniciado y escuchando en el puerto ${PORT}`);
});