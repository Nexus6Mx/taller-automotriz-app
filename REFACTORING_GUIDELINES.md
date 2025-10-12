Directrices de refactorización y cambios (guardadas por petición del autor)

Estas pautas fueron solicitadas para mantener un proceso seguro y ordenado al hacer cambios en el código del repositorio.

1. Cambios incrementales
- Haz solo cambios incrementales manteniendo siempre la funcionalidad existente.
- Cada cambio debe ser pequeño y fácil de revisar.

2. Refactorización segura
- Refactoriza el código sin modificar el comportamiento público ni la lógica principal.
- Si hay que cambiar la API pública, documenta y versiona el cambio.

3. Comentarios y documentación
- Añade comentarios claros y útiles explicando cada parte importante del código.
- Añade/actualiza documentación (README, comentarios de funciones y ejemplos de uso) cuando sea relevante.

4. Pruebas
- Genera pruebas unitarias para cada cambio relevante (happy-path + 1-2 edge cases).
- Ejecuta tests localmente antes de abrir un PR.

5. Riesgos y advertencias
- Advierte sobre posibles riesgos de bugs, dependencias o efectos colaterales en cada propuesta.
- Incluye una sección "Riesgos" en la descripción del PR si aplica.

6. Explicación de funciones
- Explica detalladamente qué hace cada función editada o creada (inputs, outputs, errores esperados, side-effects).

7. Rendimiento
- Mejora el rendimiento solo si es posible sin alterar los resultados.
- Mide y documenta cualquier mejora significativa (tiempo/uso de memoria).

8. Dependencias e impactos
- Enumera las dependencias (librerías, archivos, servicios) y posibles impactos del cambio en otros módulos o archivos.
- Si el cambio requiere rollout en infra, documenta los pasos.

9. Compatibilidad y breaking changes
- Indica qué modificaciones pueden afectar funcionalidades ya implementadas o romper compatibilidades.
- Propón un plan de mitigación y una estrategia de migración si aplica.

10. Código redundante y modularización
- Señala código redundante, no utilizado y oportunidades para modularización.
- Prioriza crear funciones pequeñas y reusables.

11. Estilo y convenciones
- Sigue las convenciones, el estilo y las buenas prácticas aplicadas en este repositorio.
- Usa las mismas reglas de formateo y lint existentes.

12. Justificación
- Justifica cada sugerencia o refactorización, especialmente si altera estructura o lógica.
- Adjunta ejemplos o benchmarks cuando proceda.

13. División de cambios complejos
- Divide y recomienda los cambios complejos en tareas pequeñas y desplegables.
- Cada tarea pequeña debe incluir pruebas y revisión independiente.

14. Simplicidad y mantenibilidad
- Aunque sugieras mejoras, prioriza siempre la simplicidad, la legibilidad y la mantenibilidad.

15. Validación antes de producción
- Propón formas de validar y probar cada cambio antes de integrarlo a producción (staging, pruebas automáticas, revisión manual).

16. Preguntar si falta contexto
- Si falta contexto o información para dar una respuesta segura, pregunta primero o avísalo antes de sugerir cambios importantes.

Notas adicionales
- Puedo generar plantillas de PR, checklist para reviewers y tests base si quieres que las cree aquí.
- Si deseas, también puedo añadir estas reglas a `.github/CONTRIBUTING.md` o crear una plantilla de PR en `.github/PULL_REQUEST_TEMPLATE.md`.

Fin de las directrices.
