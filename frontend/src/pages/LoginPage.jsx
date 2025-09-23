import React, { useState } from 'react';
import axios from 'axios';
import { useNavigate } from 'react-router-dom';

// Estilos
const styles = {
    container: { display: 'flex', justifyContent: 'center', alignItems: 'center', height: '100vh', fontFamily: 'sans-serif', backgroundColor: '#f0f2f5' },
    formContainer: { padding: '40px', boxShadow: '0 4px 12px rgba(0, 0, 0, 0.1)', backgroundColor: '#ffffff', borderRadius: '8px', width: '350px' },
    title: { textAlign: 'center', marginBottom: '24px', color: '#333' },
    formGroup: { marginBottom: '16px' },
    label: { display: 'block', marginBottom: '8px', color: '#555' },
    input: { width: '100%', padding: '10px', borderRadius: '4px', border: '1px solid #ccc', boxSizing: 'border-box' },
    button: { width: '100%', padding: '12px', borderRadius: '4px', border: 'none', backgroundColor: '#007bff', color: 'white', fontSize: '16px', cursor: 'pointer' },
    message: (isSuccess) => ({ marginTop: '16px', textAlign: 'center', color: isSuccess ? 'green' : 'red' }),
};

function LoginPage() {
    const [email, setEmail] = useState('');
    const [password, setPassword] = useState('');
    const [message, setMessage] = useState('');
    const navigate = useNavigate();

    const handleLogin = async (e) => {
        e.preventDefault();
        setMessage('');
        try {
            const response = await axios.post('http://localhost:3001/api/auth/login', { email, password });
            localStorage.setItem('authToken', response.data.token);
            navigate('/dashboard'); // Redirige al dashboard
        } catch (error) {
            if (error.response) {
                setMessage(error.response.data.message);
            } else {
                setMessage('Error de red. ¿El servidor backend está corriendo?');
            }
        }
    };

    return (
        <div style={styles.container}>
            <div style={styles.formContainer}>
                <h1 style={styles.title}>Iniciar Sesión</h1>
                <form onSubmit={handleLogin}>
                    <div style={styles.formGroup}>
                        <label style={styles.label}>Email:</label>
                        <input style={styles.input} type="email" value={email} onChange={(e) => setEmail(e.target.value)} required />
                    </div>
                    <div style={styles.formGroup}>
                        <label style={styles.label}>Contraseña:</label>
                        <input style={styles.input} type="password" value={password} onChange={(e) => setPassword(e.target.value)} required />
                    </div>
                    <button style={styles.button} type="submit">Entrar</button>
                </form>
                {message && <p style={styles.message(false)}>{message}</p>}
            </div>
        </div>
    );
}

export default LoginPage;