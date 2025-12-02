# Proceso y Funcionalidades del Sistema Gestor de Órdenes de Servicio

## Introducción
Este documento describe el proceso operativo y las funcionalidades principales del **Gestor de Órdenes de Servicio** para el taller automotriz ERR Automotriz. El sistema es una aplicación web que permite gestionar órdenes de servicio, clientes, vehículos e insumos de manera eficiente, con un enfoque en la automatización de catálogos y la generación de documentos impresos.

El sistema está desarrollado con tecnologías web modernas: frontend en HTML/CSS/JavaScript (usando TailwindCSS para estilos y Chart.js para gráficos), backend en PHP con API RESTful, y base de datos MySQL. Está diseñado para ser usado en un entorno de taller, facilitando la creación, seguimiento y facturación de órdenes de servicio.

## Flujo General del Proceso

### 1. Autenticación y Acceso
- **Inicio de Sesión**: Los usuarios acceden mediante correo electrónico y contraseña. Si no tienen cuenta, pueden registrarse directamente en la aplicación.
- **Verificación**: El sistema valida las credenciales contra la base de datos y genera un token de sesión para mantener la autenticidad en las solicitudes posteriores.
- **Redirección**: Una vez autenticado, el usuario es dirigido al dashboard principal.

### 2. Dashboard y Monitoreo
- **Vista General**: Muestra KPIs clave como total de ingresos, ticket promedio y número total de órdenes.
- **Gráficos Interactivos**: Incluye un gráfico de dona para órdenes por estado y un gráfico de barras para ingresos mensuales.
- **Tablas Dinámicas**: Lista órdenes pendientes de pago y órdenes pagadas, con opciones de búsqueda y ordenamiento.
- **Navegación**: Pestañas para acceder a diferentes secciones: Dashboard, Nueva Orden, Historial y Catálogos.

### 3. Gestión de Órdenes de Servicio
- **Creación de Órdenes**:
  - Ingreso de datos del cliente (nombre, teléfono, dirección, RFC, email) con autocompletado desde el catálogo existente.
  - Ingreso de datos del vehículo (marca/modelo, placas, año, kilometraje, nivel de gasolina).
  - Agregado de conceptos (refacciones y mano de obra): cantidad, descripción e importe. Los insumos se autocompletan desde el catálogo.
  - Cálculo automático de subtotal, IVA (opcional al 16%) y total.
  - Opción de anticipo con fecha.
  - Asignación de estado inicial (ej. "Recibido").
  - Al guardar, el sistema actualiza automáticamente los catálogos si el cliente, vehículo o insumo no existen.
- **Edición de Órdenes**: Permite modificar cualquier dato de la orden, incluyendo items y estado.
- **Seguimiento de Estados**: Los estados posibles son: Cotización, Recibido, Diagnóstico, Autorizado en reparación, Preparación para entrega, Entregado pendiente de pago, En Facturación, Facturado, Entregado Pagado.
- **Eliminación**: Opción para borrar órdenes no deseadas.

### 4. Gestión de Catálogos
- **Clientes**: Lista de clientes registrados, con búsqueda por nombre o ID.
- **Vehículos**: Lista de vehículos asociados a clientes, con búsqueda por cliente, marca o placas.
- **Insumos**: Catálogo de refacciones y servicios, con precios unitarios, búsqueda por descripción o ID.
- **Actualización Automática**: Al crear órdenes, los catálogos se enriquecen automáticamente con nuevos datos.

### 5. Generación de Documentos
- **Impresión de Órdenes**: Desde el historial, se puede imprimir una orden en formato PDF, incluyendo logo, datos del cliente/vehículo, conceptos, totales y observaciones.
- **Exportación**: Opción para exportar el historial completo de órdenes a un archivo CSV.

### 6. Funcionalidades Adicionales
- **Autocompletado Inteligente**: En formularios, sugiere datos existentes para acelerar el ingreso.
- **Búsqueda y Filtrado**: En tablas de historial y catálogos, permite buscar por múltiples criterios.
- **Ordenamiento**: Columnas ordenables en tablas para facilitar la navegación.
- **Drag-and-Drop**: En el dashboard, los widgets de KPIs son arrastrables para personalizar la vista.
- **Conexión en Tiempo Real**: Indicador de estado de conexión al servidor.
- **Cierre de Sesión**: Opción para salir y limpiar la sesión.

### 7. Configuración de Correos de Facturación (Solo Administrador)
- En la pestaña Historial, los usuarios administradores verán el botón "Configurar correos de facturación".
- Desde allí pueden gestionar la lista de destinatarios que recibirán la solicitud de facturación con el PDF adjunto.
- Validaciones:
  - Formato de email válido por línea.
  - Debe existir al menos un correo configurado para poder enviar.
- Efecto inmediato: los cambios se guardan en la base de datos y aplican al siguiente envío de facturación sin reiniciar.
- Seguridad:
  - Solo el administrador puede modificar la lista.
  - Los usuarios no administradores utilizan la lista del administrador al enviar facturación.

## Diagrama Lógico del Flujo

```
[Usuario] --> [Login/Register] --> [Autenticación (API)] --> [Dashboard]
                                      |
                                      v
[Dashboard] --> [Nueva Orden] --> [Formulario] --> [Guardar Orden (API)] --> [Actualizar Catálogos]
                                      |
                                      v
[Historial] --> [Ver/Editar/Eliminar Órdenes] --> [API CRUD Órdenes]
                                      |
                                      v
[Catálogos] --> [Ver Clientes/Vehículos/Insumos] --> [API Lectura]
                                      |
                                      v
[Imprimir] --> [Generar PDF] --> [Descargar/Guardar Archivo]
```

## Estados de las Órdenes
- **Cotización**: Estimación inicial sin compromiso.
- **Recibido**: Orden aceptada, vehículo en taller.
- **Diagnóstico**: Evaluación en proceso.
- **Autorizado en reparación**: Trabajo aprobado, en ejecución.
- **Preparación para entrega**: Trabajo terminado, listo para entrega.
- **Entregado pendiente de pago**: Vehículo entregado, pago pendiente.
- **En Facturación**: Generando factura.
- **Facturado**: Factura emitida.
- **Entregado Pagado**: Proceso completo.

## Consideraciones Operativas
- **Multiusuario**: Cada usuario tiene sus propios datos (órdenes, catálogos) asociados por ID de usuario.
- **Persistencia**: Todos los datos se almacenan en MySQL, con respaldo automático en catálogos.
- **Seguridad**: Uso de tokens JWT para autenticación, validación de CORS para orígenes permitidos.
- **Rendimiento**: Optimizado para consultas rápidas, con índices en base de datos.
- **Escalabilidad**: Arquitectura modular permite agregar nuevas funcionalidades como inventario o reportes avanzados.

Este documento proporciona una visión general del proceso y funcionalidades. Para detalles técnicos o implementación, consulta la documentación del código fuente.