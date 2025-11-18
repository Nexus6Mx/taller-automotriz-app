-- ========================================================================
-- SCRIPT PARA ELIMINAR ÓRDENES DUPLICADAS EN PRODUCCIÓN
-- Fecha: 2025-11-17
-- Descripción: Elimina órdenes específicas que fueron duplicadas/triplicadas
-- ========================================================================

-- IMPORTANTE: Antes de ejecutar, hacer BACKUP de la base de datos
-- Comando para backup: mysqldump -u usuario -p nombre_base > backup_antes_eliminar.sql

-- ========================================================================
-- PASO 1: VERIFICAR QUÉ SE VA A ELIMINAR (CONSULTA DE SEGURIDAD)
-- ========================================================================
-- Ejecuta esto primero para ver qué órdenes se eliminarán:

SELECT 
    o.id,
    o.numeric_id,
    o.created_at,
    o.client_name,
    o.total,
    o.status,
    COUNT(oi.id) as num_items
FROM orders o
LEFT JOIN order_items oi ON o.id = oi.order_id
WHERE 
    -- Orden 10000 de Guadalupe (2025-11-07)
    (o.numeric_id = 10000 AND o.client_name = 'Guadalupe' AND DATE(o.created_at) = '2025-11-07')
    OR
    -- Orden 10000 de Protector Intercontinental (2025-11-12)
    (o.numeric_id = 10000 AND o.client_name = 'Protector Intercontinental' AND DATE(o.created_at) = '2025-11-12')
    OR
    -- Orden 10001 de Estacionamientos Corsa ERIKA
    (o.numeric_id = 10001 AND o.client_name = 'Estacionamientos Corsa ERIKA')
    OR
    -- Orden 10002 de Estacionamientos Corsa ERIKA
    (o.numeric_id = 10002 AND o.client_name = 'Estacionamientos Corsa ERIKA')
    OR
    -- Orden 10003 de Protector Intercontinental
    (o.numeric_id = 10003 AND o.client_name = 'Protector Intercontinental')
    OR
    -- Orden 10004 de Protector Intercontinental
    (o.numeric_id = 10004 AND o.client_name = 'Protector Intercontinental')
GROUP BY o.id
ORDER BY o.numeric_id, o.created_at;

-- ========================================================================
-- PASO 2: ELIMINAR LAS ÓRDENES (SI LA VERIFICACIÓN ES CORRECTA)
-- ========================================================================
-- ADVERTENCIA: Esta operación NO se puede deshacer sin el backup

-- 2.1: Primero eliminar los items de las órdenes
DELETE oi FROM order_items oi
INNER JOIN orders o ON oi.order_id = o.id
WHERE 
    (o.numeric_id = 10000 AND o.client_name = 'Guadalupe' AND DATE(o.created_at) = '2025-11-07')
    OR
    (o.numeric_id = 10000 AND o.client_name = 'Protector Intercontinental' AND DATE(o.created_at) = '2025-11-12')
    OR
    (o.numeric_id = 10001 AND o.client_name = 'Estacionamientos Corsa ERIKA')
    OR
    (o.numeric_id = 10002 AND o.client_name = 'Estacionamientos Corsa ERIKA')
    OR
    (o.numeric_id = 10003 AND o.client_name = 'Protector Intercontinental')
    OR
    (o.numeric_id = 10004 AND o.client_name = 'Protector Intercontinental');

-- 2.2: Luego eliminar las órdenes
DELETE FROM orders
WHERE 
    (numeric_id = 10000 AND client_name = 'Guadalupe' AND DATE(created_at) = '2025-11-07')
    OR
    (numeric_id = 10000 AND client_name = 'Protector Intercontinental' AND DATE(created_at) = '2025-11-12')
    OR
    (numeric_id = 10001 AND client_name = 'Estacionamientos Corsa ERIKA')
    OR
    (numeric_id = 10002 AND client_name = 'Estacionamientos Corsa ERIKA')
    OR
    (numeric_id = 10003 AND client_name = 'Protector Intercontinental')
    OR
    (numeric_id = 10004 AND client_name = 'Protector Intercontinental');

-- ========================================================================
-- PASO 3: VERIFICAR QUE SE ELIMINARON CORRECTAMENTE
-- ========================================================================
-- Ejecuta esta consulta para confirmar que ya no existen:

SELECT 
    o.id,
    o.numeric_id,
    o.created_at,
    o.client_name,
    o.total,
    o.status
FROM orders o
WHERE 
    o.numeric_id IN (10000, 10001, 10002, 10003, 10004)
ORDER BY o.numeric_id, o.created_at;

-- ========================================================================
-- RESULTADO ESPERADO:
-- - Después del PASO 2, deben eliminarse 6 órdenes en total
-- - El PASO 3 debe mostrar solo las órdenes legítimas (si quedan)
-- ========================================================================
