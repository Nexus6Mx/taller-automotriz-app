-- Script para agregar campos de anticipo a la tabla orders
ALTER TABLE `orders` 
ADD COLUMN `advance_amount` DECIMAL(10,2) DEFAULT 0.00 AFTER `iva_applied`,
ADD COLUMN `advance_date` DATE DEFAULT NULL AFTER `advance_amount`;
