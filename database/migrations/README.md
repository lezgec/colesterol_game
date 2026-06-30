# Migraciones

Este directorio contiene cambios incrementales para bases existentes.

Orden recomendado para una instalación limpia:

1. Ejecutar `database/schema.sql`.
2. Ejecutar `database/migrations/002_runtime_schema_requirements.sql`.
3. Ejecutar `database/migrations/001_add_foreign_keys.sql`.
4. Ejecutar `database/seed.sql`.

`schema.sql` es destructivo porque contiene `DROP TABLE`; no debe ejecutarse sobre una base de producción con datos reales.

Orden recomendado para actualizar una base existente:

1. Ejecutar `database/migrations/002_runtime_schema_requirements.sql`.
2. Validar que no haya registros huérfanos.
3. Ejecutar `database/migrations/001_add_foreign_keys.sql`.

Antes de aplicar `001_add_foreign_keys.sql`, valida que no haya registros huérfanos en tablas relacionadas con usuarios, salas, preguntas, respuestas y badges.

Antes de desplegar código nuevo en una base ya creada, aplica las migraciones pendientes. El runtime ya no debe crear tablas ni columnas automáticamente.

Compatibilidad: estas migraciones usan sintaxis como `ADD COLUMN IF NOT EXISTS`; se recomiendan MariaDB 10.4+ o MySQL 8.0.29+. `001_add_foreign_keys.sql` no es idempotente y debe ejecutarse una sola vez, después de validar datos huérfanos.
