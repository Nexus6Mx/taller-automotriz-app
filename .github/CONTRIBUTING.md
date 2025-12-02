Contributing
============

Gracias por contribuir a este repositorio. Estas son las pautas mínimas para mantener el código consistente, seguro y fácil de revisar.

1. Cambios incrementales
- Haz cambios pequeños y atómicos. Cada PR debe centrarse en una sola tarea o bugfix.

2. Seguridad
- Nunca incluyas credenciales, tokens, claves privadas, passwords o dumps SQL en commits.
- Si necesitas compartir configuración, añade un archivo ejemplo (`*.example`) y añade la versión real a `.gitignore`.

3. Refactorización segura
- No cambies el comportamiento público ni la API sin documentarlo explícitamente.
- Si debes introducir un breaking change, indica claramente el motivo y crea una guía de migración.

4. Comentarios y documentación
- Añade comentarios claros a funciones complejas.
- Actualiza `README.md` o documentación adicional si el cambio lo requiere.

5. Pruebas
- Incluye pruebas unitarias para funcionalidades nuevas o modificadas.
- Ejecuta las pruebas locales antes de abrir PR.

6. Estilo y linting
- Mantén el estilo predominante del repositorio (PHP procedural/OO y JS modular según corresponda).
- Ejecuta linters si están configurados.

7. Checklist de PR
- Usa la plantilla de PR proporcionada al abrir un pull request.

8. Revisión y merges
- Los PRs pequeños se revisan más rápido. Responde comentarios de revisión y actualiza el PR.
- No mezcles cambios de formato masivo con cambios lógicos en el mismo PR.

9. Histórico y secretos
- Si accidentalmente commiteaste secretos, contacta al equipo antes de proceder; se requerirá borrar del historial y rotar las credenciales.

10. Contacto
- Si tienes dudas sobre cómo aplicar estas reglas, abre un Issue o contacta al mantenedor.

Gracias por ayudar a mantener el proyecto sano y seguro.
