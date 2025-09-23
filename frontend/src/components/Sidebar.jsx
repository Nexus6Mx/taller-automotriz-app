import React from 'react';
import { NavLink, useNavigate } from 'react-router-dom';

const styles = {
    sidebar: {
        width: '250px',
        backgroundColor: '#2c3e50',
        color: 'white',
        display: 'flex',
        flexDirection: 'column',
        padding: '1rem',
    },
    title: {
        fontSize: '1.5rem',
        textAlign: 'center',
        marginBottom: '2rem',
    },
    nav: {
        display: 'flex',
        flexDirection: 'column',
        flexGrow: 1,
    },
    link: {
        color: '#bdc3c7',
        textDecoration: 'none',
        padding: '1rem',
        borderRadius: '4px',
        marginBottom: '0.5rem',
    },
    activeLink: {
        backgroundColor: '#3498db',
        color: 'white',
    },
    logoutButton: {
        backgroundColor: '#e74c3c',
        color: 'white',
        border: 'none',
        padding: '1rem',
        borderRadius: '4px',
        cursor: 'pointer',
        marginTop: 'auto',
    }
};

function Sidebar() {
    const navigate = useNavigate();

    const handleLogout = () => {
        localStorage.removeItem('authToken');
        navigate('/login');
    };

    return (
        <aside style={styles.sidebar}>
            <h1 style={styles.title}>Taller App</h1>
            <nav style={styles.nav}>
                <NavLink 
                    to="/dashboard" 
                    style={({ isActive }) => ({ ...styles.link, ...(isActive ? styles.activeLink : {}) })}
                >
                    Dashboard
                </NavLink>
                <NavLink 
                    to="/clientes" 
                    style={({ isActive }) => ({ ...styles.link, ...(isActive ? styles.activeLink : {}) })}
                >
                    Clientes
                </NavLink>
            </nav>
            <button style={styles.logoutButton} onClick={handleLogout}>
                Cerrar Sesión
            </button>
        </aside>
    );
}

export default Sidebar;