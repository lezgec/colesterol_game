# Migraciones

Este directorio contiene cambios incrementales para bases existentes.

Orden recomendado para una instalacion limpia:

1. Ejecutar `database/schema.sql`.
2. Ejecutar `database/migrations/001_add_foreign_keys.sql`.
3. Ejecutar `database/seed.sql`.

`schema.sql` es destructivo porque contiene `DROP TABLE`; no debe ejecutarse sobre una base de produccion con datos reales.

Antes de aplicar `001_add_foreign_keys.sql` sobre una base existente, valida que no haya registros huerfanos en tablas relacionadas con usuarios, salas, preguntas, respuestas y badges.
