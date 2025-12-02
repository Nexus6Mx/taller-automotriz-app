# Resumen Detallado del Proyecto: Sistema Gestor de Órdenes de Servicio (ERR Automotriz)

## 1. Descripción General
El sistema es una aplicación web integral diseñada para la gestión operativa y administrativa del taller "ERR Automotriz". Su objetivo principal es digitalizar y automatizar el flujo de trabajo, desde la recepción del vehículo hasta la facturación y entrega, facilitando el control de órdenes de servicio, clientes, vehículos e inventario de insumos.

La solución está construida sobre una arquitectura moderna utilizando **HTML5, CSS3 (TailwindCSS) y JavaScript** para el frontend, y **PHP con MySQL** para el backend, operando bajo un modelo de API RESTful.

## 2. Funcionalidades Principales

### A. Control de Acceso y Seguridad
*   **Autenticación Segura:** Sistema de inicio de sesión y registro de usuarios mediante correo electrónico y contraseña.
*   **Gestión de Sesiones:** Uso de tokens (JWT) para mantener sesiones seguras y validar la identidad en cada transacción.
*   **Roles y Permisos:** Diferenciación de privilegios (ej. Administrador) para funciones sensibles como la configuración de correos de facturación.
*   **Auditoría:** Registro de actividades (logs) para monitorear acciones críticas dentro del sistema.

### B. Dashboard Operativo (Cuadro de Mando)
*   **Indicadores Clave (KPIs):** Visualización inmediata de métricas financieras y operativas:
    *   Ingresos totales.
    *   Ticket promedio por orden.
    *   Total de órdenes procesadas.
*   **Visualización de Datos:**
    *   **Gráficos Interactivos:** Distribución de órdenes por estado (Gráfico de Dona) y evolución de ingresos mensuales (Gráfico de Barras).
    *   **Tablas Dinámicas:** Listados de acceso rápido para "Órdenes Pendientes de Pago" y "Órdenes Pagadas".
*   **Personalización:** Interfaz con widgets arrastrables (drag-and-drop) para adaptar la vista a las preferencias del usuario.

### C. Gestión Integral de Órdenes de Servicio
*   **Creación Inteligente:**
    *   Formularios con **autocompletado** para clientes, vehículos e insumos, agilizando la captura de datos.
    *   Cálculos automáticos de subtotales, IVA (opcional 16%) y totales finales.
    *   Registro de anticipos y fechas de compromiso.
*   **Flujo de Estados:** Control granular del ciclo de vida de la orden:
    1.  Cotización
    2.  Recibido
    3.  Diagnóstico
    4.  Autorizado en reparación
    5.  Preparación para entrega
    6.  Entregado pendiente de pago
    7.  En Facturación
    8.  Facturado
    9.  Entregado Pagado
*   **Edición y Actualización:** Capacidad para modificar items, precios y estados en cualquier momento del proceso.
*   **Sincronización de Catálogos:** Al registrar una orden con datos nuevos (ej. un nuevo cliente o vehículo), el sistema actualiza automáticamente los catálogos maestros.

### D. Administración de Catálogos
*   **Clientes:** Base de datos centralizada con información de contacto y fiscal (RFC).
*   **Vehículos:** Registro detallado (Marca, Modelo, Año, Placas, VIN) vinculado a sus propietarios.
*   **Insumos y Servicios:** Catálogo de refacciones y mano de obra con precios unitarios predefinidos.
*   **Búsqueda Avanzada:** Herramientas de filtrado y búsqueda rápida en todos los listados.

### E. Generación de Documentos y Reportes
*   **Impresión de Órdenes (PDF):** Generación de documentos profesionales listos para imprimir, incluyendo logotipos, desglose de costos y observaciones.
*   **Facturación:**
    *   Módulo para el envío de solicitudes de facturación por correo electrónico.
    *   Configuración de destinatarios de facturación (funcionalidad administrativa).
*   **Exportación de Datos:** Capacidad de exportar el historial completo de operaciones a formato CSV para análisis externos (Excel).

## 3. Infraestructura y Despliegue

### Servidor de Producción
*   **Dominio:** El sistema se encuentra desplegado y operativo en la dirección: **[errautomotriz.online](https://errautomotriz.online)**

### Estado del Hosting y Dominio
*   **Cobertura:** Los servicios de alojamiento web (hosting) y el registro del dominio se encuentran **pagados y cubiertos hasta el año 2027**, garantizando la continuidad operativa del servicio a mediano plazo sin requerir renovaciones inmediatas.
