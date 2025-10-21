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

## Correo: local vs producción
Los endpoints `api/orders/send_email.php` y `api/orders/send_invoice.php` usan un transporte de correo configurable:

- Archivo de configuración: `api/config/mail.php` (copia desde `api/config/mail.example.php`).
- Opción `transport`: `smtp` (vía PHPMailer) o `mail` (función nativa de PHP).
- Si `transport` = `smtp` y existe la carpeta `PHPMailer/`, se usará SMTP. Si falla o no está disponible, se hace fallback automático a `mail()`.
- También puedes usar `transport` = `log` para desarrollo: en lugar de enviar, se guarda un archivo .eml en `api/logs/outbox/` con el contenido del correo y el adjunto.

Pasos:
1. Copia `api/config/mail.example.php` a `api/config/mail.php` y ajusta host, puerto, credenciales y remitente.
2. Producción: define `transport => 'smtp'` y coloca `PHPMailer/` en la raíz del proyecto (ya existe en este repo). Asegura permisos para escribir en `api/logs/` y `ordenes/`.
3. Local: si no tienes SMTP, puedes usar `transport => 'log'` para inspeccionar los correos generados (quedan en `api/logs/outbox/`). Alternativamente, define SMTP de pruebas o usa `transport => 'mail'` si tu PHP local envía con `mail()`.

Verificación rápida:
- Revisa `api/logs/send_email.log` y `api/logs/send_invoice.log` para ver el transporte elegido y el resultado (`Using SMTP via PHPMailer`, `SMTP configured but PHPMailer not found, falling back to mail()`, `mail() result: true/false`, `Saved EML to outbox`).

## Contacto
Para dudas técnicas, abre un Issue o contacta al responsable del repositorio.
