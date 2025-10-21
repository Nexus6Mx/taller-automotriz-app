# Taller Automotriz App

Este proyecto es un sistema de gestión de órdenes para un taller automotriz.

## ¿Cómo contribuir?
Lee primero las reglas y recomendaciones en [REFACTORING_GUIDELINES.md](REFACTORING_GUIDELINES.md) y en [.github/CONTRIBUTING.md](.github/CONTRIBUTING.md).

## Reportar errores o bugs
Si encuentras un error, abre un Issue en GitHub usando la plantilla de reporte de bugs. Describe el problema, los pasos para reproducirlo y adjunta capturas si es posible.

## Estructura básica
- `api/` - Endpoints PHP para autenticación, órdenes, clientes, etc.
- `assets/` - Archivos JS y CSS
- Archivos HTML para las vistas principales

## Correo: notas
En producción, el envío se realiza con PHPMailer sobre SMTP y en su ausencia, se intenta `mail()`.
Los logs `api/logs/send_email.log` y `api/logs/send_invoice.log` ayudan a diagnosticar problemas.

## Contacto
Para dudas técnicas, abre un Issue o contacta al responsable del repositorio.
