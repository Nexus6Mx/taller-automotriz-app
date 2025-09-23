import React, { useState, useEffect } from 'react';
import axios from 'axios';

// --- Estilos para la página y el modal ---
const styles = {
    header: {
        display: 'flex',
        justifyContent: 'space-between',
        alignItems: 'center',
        marginBottom: '2rem',
    },
    addButton: {
        backgroundColor: '#28a745',
        color: 'white',
        padding: '10px 20px',
        border: 'none',
        borderRadius: '5px',
        cursor: 'pointer',
        fontSize: '16px',
    },
    table: {
        width: '100%',
        borderCollapse: 'collapse',
        boxShadow: '0 2px 15px rgba(0,0,0,0.1)',
        backgroundColor: 'white',
    },
    th: {
        backgroundColor: '#f2f2f2',
        padding: '12px',
        textAlign: 'left',
        borderBottom: '2px solid #ddd',
    },
    td: {
        padding: '12px',
        borderBottom: '1px solid #ddd',
    },
    modalOverlay: {
        position: 'fixed',
        top: 0,
        left: 0,
        right: 0,
        bottom: 0,
        backgroundColor: 'rgba(0, 0, 0, 0.7)',
        display: 'flex',
        justifyContent: 'center',
        alignItems: 'center',
    },
    modalContent: {
        backgroundColor: 'white',
        padding: '2rem',
        borderRadius: '8px',
        width: '500px',
    },
    formGroup: { marginBottom: '1rem' },
    label: { display: 'block', marginBottom: '0.5rem' },
    input: { width: '100%', padding: '8px', boxSizing: 'border-box' },
    button: { padding: '10px 20px', border: 'none', borderRadius: '5px', cursor: 'pointer' },

};

function ClientesPage() {
    const [clients, setClients] = useState([]);
    const [isLoading, setIsLoading] = useState(true);
    const [isModalOpen, setIsModalOpen] = useState(false);
    
    // Estado para el formulario del nuevo cliente
    const [newClient, setNewClient] = useState({
        name: '',
        phone: '',
        email: '',
    });

    // Función para obtener los clientes del backend
    const fetchClients = async () => {
        try {
            const token = localStorage.getItem('authToken');
            const response = await axios.get('http://localhost:3001/api/clients', {
                headers: { 'Authorization': `Bearer ${token}` }
            });
            setClients(response.data);
        } catch (error) {
            console.error('Error al obtener clientes:', error);
        } finally {
            setIsLoading(false);
        }
    };

    // useEffect para llamar a fetchClients cuando la página carga
    useEffect(() => {
        fetchClients();
    }, []);

    // Manejador para los cambios en el formulario
    const handleInputChange = (e) => {
        const { name, value } = e.target;
        setNewClient(prevState => ({ ...prevState, [name]: value }));
    };
    
    // Manejador para enviar el nuevo cliente
    const handleSubmit = async (e) => {
        e.preventDefault();
        try {
            const token = localStorage.getItem('authToken');
            await axios.post('http://localhost:3001/api/clients', newClient, {
                headers: { 'Authorization': `Bearer ${token}` }
            });
            setIsModalOpen(false); // Cierra el modal
            setNewClient({ name: '', phone: '', email: '' }); // Limpia el formulario
            fetchClients(); // Vuelve a cargar la lista de clientes
        } catch (error) {
            console.error('Error al crear el cliente:', error);
        }
    };

    if (isLoading) {
        return <p>Cargando clientes...</p>;
    }

    return (
        <div>
            <div style={styles.header}>
                <h1>Gestión de Clientes</h1>
                <button style={styles.addButton} onClick={() => setIsModalOpen(true)}>
                    + Añadir Cliente
                </button>
            </div>

            <table style={styles.table}>
                <thead>
                    <tr>
                        <th style={styles.th}>Nombre</th>
                        <th style={styles.th}>Teléfono</th>
                        <th style={styles.th}>Email</th>
                    </tr>
                </thead>
                <tbody>
                    {clients.length > 0 ? (
                        clients.map(client => (
                            <tr key={client.id}>
                                <td style={styles.td}>{client.name}</td>
                                <td style={styles.td}>{client.phone}</td>
                                <td style={styles.td}>{client.email}</td>
                            </tr>
                        ))
                    ) : (
                        <tr>
                            <td colSpan="3" style={{...styles.td, textAlign: 'center'}}>
                                No hay clientes registrados.
                            </td>
                        </tr>
                    )}
                </tbody>
            </table>

            {/* Modal para añadir nuevo cliente */}
            {isModalOpen && (
                <div style={styles.modalOverlay}>
                    <div style={styles.modalContent}>
                        <h2>Nuevo Cliente</h2>
                        <form onSubmit={handleSubmit}>
                            <div style={styles.formGroup}>
                                <label style={styles.label}>Nombre Completo</label>
                                <input style={styles.input} type="text" name="name" value={newClient.name} onChange={handleInputChange} required />
                            </div>
                            <div style={styles.formGroup}>
                                <label style={styles.label}>Teléfono</label>
                                <input style={styles.input} type="tel" name="phone" value={newClient.phone} onChange={handleInputChange} />
                            </div>
                            <div style={styles.formGroup}>
                                <label style={styles.label}>Email</label>
                                <input style={styles.input} type="email" name="email" value={newClient.email} onChange={handleInputChange} />
                            </div>
                            <div style={{ display: 'flex', justifyContent: 'flex-end', gap: '1rem', marginTop: '2rem' }}>
                                <button type="button" style={{...styles.button, backgroundColor: '#6c757d'}} onClick={() => setIsModalOpen(false)}>
                                    Cancelar
                                </button>
                                <button type="submit" style={{...styles.button, backgroundColor: '#007bff'}}>
                                    Guardar Cliente
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </div>
    );
}

export default ClientesPage;