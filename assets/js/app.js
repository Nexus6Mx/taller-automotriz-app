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
            const data = await response.json();

            if (!response.ok) {
                // Si la respuesta no es exitosa, lanzamos un error con el mensaje de la API.
                throw new Error(data.message || 'Ocurrió un error en la solicitud.');
            }

            return data;
        } catch (error) {
            console.error('Error en APIService:', error);
            // Re-lanzamos el error para que pueda ser capturado por el código que llamó a la función.
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

    async createOrder(orderData) {
        return await this.request('/orders/create.php', {
            method: 'POST',
            body: JSON.stringify(orderData)
        });
    }
    
    async updateOrder(orderId, orderData) {
        return await this.request('/orders/update.php', {
            method: 'PUT',
            body: JSON.stringify({ id: orderId, ...orderData })
        });
    }

    async deleteOrder(orderId) {
        return await this.request('/orders/delete.php', {
            method: 'DELETE',
            body: JSON.stringify({ id: orderId })
        });
    }

    // --- Métodos para Catálogos (Clientes, Vehículos, Insumos) ---

    async getClients() {
        return await this.request('/clients/read.php');
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
}

// Instancia global del servicio para que esté disponible en toda la aplicación
window.apiService = new APIService();
