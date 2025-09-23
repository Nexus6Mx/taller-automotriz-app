import React from 'react';
import { Routes, Route, Navigate } from 'react-router-dom';
import LoginPage from './pages/LoginPage.jsx';
import DashboardPage from './pages/DashboardPage.jsx';
import ClientesPage from './pages/ClientesPage.jsx';
import MainLayout from './components/MainLayout.jsx';

// Componente para proteger rutas
const PrivateRoute = ({ children }) => {
  const token = localStorage.getItem('authToken');
  return token ? children : <Navigate to="/login" />;
};

function App() {
  return (
    <Routes>
      {/* Rutas Públicas */}
      <Route path="/login" element={<LoginPage />} />

      {/* Rutas Privadas (Usan el MainLayout) */}
      <Route 
        path="/dashboard" 
        element={
          <PrivateRoute>
            <MainLayout>
              <DashboardPage />
            </MainLayout>
          </PrivateRoute>
        } 
      />
      <Route 
        path="/clientes"
        element={
          <PrivateRoute>
            <MainLayout>
              <ClientesPage />
            </MainLayout>
          </PrivateRoute>
        }
      />

      {/* Ruta por defecto */}
      <Route 
        path="/" 
        element={localStorage.getItem('authToken') ? <Navigate to="/dashboard" /> : <Navigate to="/login" />}
      />
    </Routes>
  );
}

export default App;