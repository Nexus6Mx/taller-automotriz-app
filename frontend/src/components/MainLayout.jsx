import React from 'react';
import Sidebar from './Sidebar.jsx';

const styles = {
    layout: {
        display: 'flex',
        minHeight: '100vh',
    },
    content: {
        flexGrow: 1,
        padding: '2rem',
        backgroundColor: '#f0f2f5',
    }
};

function MainLayout({ children }) {
    return (
        <div style={styles.layout}>
            <Sidebar />
            <main style={styles.content}>
                {children}
            </main>
        </div>
    );
}

export default MainLayout;