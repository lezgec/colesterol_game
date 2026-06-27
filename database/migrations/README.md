# Migraciones

Este directorio queda reservado para cambios incrementales sobre bases existentes.

Para una instalación limpia usa:

1. `database/schema.sql`
2. `database/seed.sql`

El archivo `schema.sql` es destructivo porque contiene `DROP TABLE`; no debe ejecutarse sobre una base de producción con datos reales.
