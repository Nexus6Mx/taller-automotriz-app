// --- CONFIGURACIÓN DE LA API ---
// Se cambia a una URL relativa para que funcione tanto en desarrollo local como en producción.
const API_URL = '/api';
// -----------------------------

let authToken = localStorage.getItem('authToken');

/**
 * Clase para manejar todas las llamadas a la API de forma centralizada.
 */
class APIService {
    constructor() {
        this.baseURL = API_URL;
    }

    /**
     * Realiza una solicitud a la API.
     * @param {string} endpoint - La ruta del endpoint (ej. '/auth/login.php').
     * @param {object} options - Opciones para la solicitud fetch (method, body, etc.).
     * @returns {Promise<any>} - La respuesta JSON de la API.
     */
    async request(endpoint, options = {}) {
        const url = `${this.baseURL}${endpoint}`;
        
        const config = {
            ...options,
            headers: {
                'Content-Type': 'application/json',
                ...options.headers,
            }
        };

        // Si tenemos un token de autenticación, lo añadimos a la cabecera.
        if (authToken) {
            config.headers['Authorization'] = `Bearer ${authToken}`;
        }

        try {
            const response = await fetch(url, config);
            const contentType = response.headers.get('content-type') || '';
            // Read body ONCE to avoid "body stream already read" errors
            const text = await response.text();

            let data = null;
            if (contentType.includes('application/json')) {
                try {
                    data = text ? JSON.parse(text) : {};
                } catch (e) {
                    // Keep raw text for error reporting
                    data = null;
                }
            } else {
                // Try parsing anyway in case server sends JSON with wrong header
                try {
                    data = text ? JSON.parse(text) : {};
                } catch (e) {
                    data = null;
                }
            }

            if (!response.ok) {
                const message = (data && data.message) ? data.message : (text || `Error HTTP ${response.status}`);
                const err = new Error(message);
                err.status = response.status;
                err.body = text;
                throw err;
            }

            // Prefer parsed JSON; fallback to raw text in an object
            return (data !== null ? data : (text ? { message: text } : {}));
        } catch (error) {
            console.error('Error en APIService:', error);
            throw error;
        }
    }

    // --- Métodos de Autenticación ---

    async login(email, password) {
        const data = await this.request('/auth/login.php', {
            method: 'POST',
            body: JSON.stringify({ email, password })
        });
        
    // Almacenar el token y datos del usuario en localStorage
        authToken = data.token;
        localStorage.setItem('authToken', authToken);
        localStorage.setItem('userEmail', data.email);
        localStorage.setItem('userId', data.user_id);
    if (data.role) localStorage.setItem('userRole', data.role);
        
        return data;
    }

    async register(email, password) {
        const data = await this.request('/auth/register.php', {
            method: 'POST',
            body: JSON.stringify({ email, password })
        });
        
        // Almacenar el token y datos del usuario en localStorage
        authToken = data.token;
        localStorage.setItem('authToken', authToken);
        localStorage.setItem('userEmail', data.email);
        localStorage.setItem('userId', data.user_id);
        
        return data;
    }

    async logout() {
        // Aunque no tengamos un endpoint de logout, limpiamos el lado del cliente
        authToken = null;
        localStorage.removeItem('authToken');
        localStorage.removeItem('userEmail');
        localStorage.removeItem('userId');
        
        // Redirigir a la página de login
        window.location.href = '/login.html';
    }

    // --- Métodos para Órdenes ---

    async getOrders() {
        return await this.request('/orders/read.php');
    }

    async getOrder(orderId) {
        const response = await this.request(`/orders/read.php?id=${orderId}`);
        return response;
    }

    async createOrder(orderData) {
        return await this.request('/orders/create.php', {
            method: 'POST',
            body: JSON.stringify(orderData)
        });
    }
    
    async updateOrder(orderId, orderData) {
        if (!orderId) {
            throw new Error('ID de orden no proporcionado');
        }
        const data = {
            ...orderData,
            id: parseInt(orderId) // Asegurar que el ID sea número y esté presente
        };
        const response = await this.request('/orders/update.php', {
            // Algunos hostings restringen PUT; usar POST garantiza compatibilidad
            method: 'POST',
            headers: { 'X-HTTP-Method-Override': 'PUT' },
            body: JSON.stringify(data)
        });
        return response;
    }

    async deleteOrder(orderId) {
        return await this.request('/orders/delete.php', {
            method: 'DELETE',
            body: JSON.stringify({ id: orderId })
        });
    }

    async sendOrderEmail(orderId) {
        return await this.request('/orders/send_email.php', {
            method: 'POST',
            body: JSON.stringify({ id: orderId })
        });
    }

    async sendInvoiceEmail(orderId) {
        return await this.request('/orders/send_invoice.php', {
            method: 'POST',
            body: JSON.stringify({ id: orderId })
        });
    }

    async getInvoiceRecipients() {
        return await this.request('/orders/invoice_recipients.php');
    }

    async updateInvoiceRecipients(emails) {
        return await this.request('/orders/invoice_recipients.php', {
            method: 'POST',
            headers: { 'X-HTTP-Method-Override': 'PUT' },
            body: JSON.stringify({ emails })
        });
    }

    // --- Métodos para Catálogos (Clientes, Vehículos, Insumos) ---

    async getClients() {
        return await this.request('/clients/read.php');
    }

    async searchClients(query) {
        return await this.request(`/clients/read.php?q=${encodeURIComponent(query)}`);
    }

    async createClient(clientData) {
        return await this.request('/clients/create.php', {
            method: 'POST',
            body: JSON.stringify(clientData)
        });
    }

     async getVehicles() {
        return await this.request('/vehicles/read.php');
    }
    
     async getSupplies() {
        return await this.request('/supplies/read.php');
    }

    async searchSupplies(query) {
        return await this.request(`/supplies/read.php?q=${encodeURIComponent(query)}`);
    }
}

// Instancia global del servicio para que esté disponible en toda la aplicación
window.apiService = new APIService();

// UI helpers para mostrar email y controlar el acceso a Configuración
document.addEventListener('DOMContentLoaded', async ()=>{
    const emailEl = document.getElementById('user-email');
    const cfgBtn = document.getElementById('config-btn');

    let email = localStorage.getItem('userEmail');
    let role = localStorage.getItem('userRole');

    // Si no tenemos role en localStorage pero sí tenemos token, pedir /auth/me.php
    if (!role && authToken) {
        try {
            const me = await window.apiService.request('/auth/me.php');
            if (me) {
                if (me.email) {
                    localStorage.setItem('userEmail', me.email);
                    email = me.email;
                }
                if (me.role) {
                    localStorage.setItem('userRole', me.role);
                    role = me.role;
                }
                if (me.user_id) localStorage.setItem('userId', me.user_id);
            }
        } catch (e) {
            // ignore: if /auth/me fails, keep using localStorage values (if any)
            console.warn('No se pudo obtener info de usuario:', e);
        }
    }

    if (emailEl && email) emailEl.textContent = email;
    if (cfgBtn) {
        // Normalizar role para comparación robusta
        const normalizedRole = role ? String(role).trim().toLowerCase() : null;
        console.debug('UI role check:', { role, normalizedRole, authToken });

        // Si no sabemos el role (p. ej. falta en localStorage), mostrar el botón para evitar parpadeo
        // y validar permisos solo cuando el usuario haga clic.
        if (normalizedRole === null) {
            cfgBtn.style.display = '';
            // Enlazar verificación bajo demanda (solo una vez)
            if (!cfgBtn.dataset.lazyCheck) {
                cfgBtn.dataset.lazyCheck = '1';
                cfgBtn.addEventListener('click', async (e) => {
                    e.preventDefault();
                    try {
                        // Intentar leer usuarios (endpoint admin-only). Si OK, redirigir.
                        await window.apiService.request('/users/read.php');
                        window.location.href = '/configuracion.html';
                    } catch (err) {
                        alert('No tienes permisos para acceder a Configuración.');
                        console.warn('Acceso configuracion denegado:', err);
                    }
                });
            }
        } else if (normalizedRole !== 'administrador' && !normalizedRole.startsWith('admin')) {
            cfgBtn.style.display = 'none';
        } else {
            cfgBtn.style.display = '';
        }
    }
});
