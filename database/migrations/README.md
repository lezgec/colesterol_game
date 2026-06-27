# Migraciones

Este directorio queda reservado para cambios incrementales sobre bases existentes.

Para una instalacion limpia usa:

1. `database/schema.sql`
2. `database/seed.sql`

El archivo `schema.sql` es destructivo porque contiene `DROP TABLE`; no debe ejecutarse sobre una base de produccion con datos reales.
