# Colesterol Game

Serious game educativo sobre colesterol con modo individual, salas docentes, banco de preguntas, generación con IA, reportes, perfiles, insignias, rachas, correos y paneles por rol.

## Requisitos

- PHP 8.0 o superior.
- MariaDB/MySQL.
- Apache o servidor compatible con PHP.
- Composer si se desea reinstalar dependencias.
- Extensiones PHP comunes: mysqli, curl, mbstring, openssl y gd.

## Instalación limpia

1. Copiar el proyecto en el servidor web.
   - XAMPP: `C:\xampp\htdocs\colesterol_game`
   - Hosting/Linux: carpeta pública equivalente.

2. Crear la base de datos.

```sql
CREATE DATABASE colesterol_game_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

3. Importar estructura, migraciones obligatorias y seed.

```bash
mysql -u usuario -p colesterol_game_db < database/schema.sql
mysql -u usuario -p colesterol_game_db < database/migrations/002_runtime_schema_requirements.sql
mysql -u usuario -p colesterol_game_db < database/migrations/001_add_foreign_keys.sql
mysql -u usuario -p colesterol_game_db < database/seed.sql
```

`schema.sql` es destructivo porque contiene `DROP TABLE`; úsalo solo para instalaciones limpias o bases sin datos reales.

## Actualización de una base existente

Para una base existente, primero prepara tablas y columnas esperadas por el runtime, luego valida registros huérfanos y finalmente aplica llaves foráneas:

```bash
mysql -u usuario -p colesterol_game_db < database/migrations/002_runtime_schema_requirements.sql
# validar registros huérfanos antes del siguiente paso
mysql -u usuario -p colesterol_game_db < database/migrations/001_add_foreign_keys.sql
```

No ejecutes `schema.sql` sobre producción con datos reales.

## Variables de entorno

Crear `.env` desde `.env.example`.

```bash
cp .env.example .env
```

Configurar:

```env
APP_URL=https://tu-dominio.com/colesterol_game
APP_BASE_PATH=/colesterol_game
APP_DEBUG=false

DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=colesterol_game_db
DB_USERNAME=usuario
DB_PASSWORD=clave

MAIL_HOST=smtp.tu-proveedor.com
MAIL_PORT=587
MAIL_ENCRYPTION=tls
MAIL_USERNAME=correo@example.com
MAIL_PASSWORD=app_password
MAIL_FROM_EMAIL=support@example.com
MAIL_FROM_NAME="Colesterol Game"
MAIL_REPLY_TO=support@example.com

GEMINI_API_KEY=tu_api_key
GEMINI_MODEL=gemini-2.5-flash
```

## SMTP con Brevo

Ejemplo para Brevo:

```env
MAIL_HOST=smtp-relay.brevo.com
MAIL_PORT=587
MAIL_ENCRYPTION=tls
MAIL_USERNAME=tu_login_smtp_brevo
MAIL_PASSWORD=tu_clave_smtp_brevo
MAIL_FROM_EMAIL=no-reply@tu-dominio.com
MAIL_FROM_NAME="Colesterol Game"
MAIL_REPLY_TO=support@tu-dominio.com
```

`MAIL_FROM_EMAIL` debe ser un sender verificado en Brevo. No subas credenciales reales al repositorio.

## Credenciales iniciales

`database/seed.sql` crea un superadministrador genérico:

```text
Correo: superadmin@example.com
Contraseña: Admin2026@
Rol: super_admin
```

Cambia este correo y contraseña después del primer inicio de sesión.

## Seguridad y configuración

- No subas `.env` al repositorio.
- `.env.example` debe contener solo datos ficticios.
- Las credenciales SMTP y Gemini se leen desde `.env`.
- Las respuestas correctas no se envían al navegador antes de responder.
- El backend calcula si una respuesta es correcta y los puntos obtenidos.
- Aplica las migraciones pendientes antes de desplegar código nuevo sobre una base existente.
- `001_add_foreign_keys.sql` requiere validar registros huérfanos antes de ejecutarse.

Documentación técnica:

- `docs/ARCHITECTURE.md`
- `docs/SECURITY_CHECKLIST.md`
- `docs/TEST_RUN_2026-06-29.md`

## Flujo de despliegue recomendado

Versión principal:

```bash
git add .
git commit -m "Prepare deployment version"
git push origin main
```

Rama para despliegue del docente:

```bash
git checkout -b docente-deploy
git push -u origin docente-deploy
```

La rama `docente-deploy` debe mantener el SQL separado en `database/schema.sql`, `database/migrations/` y `database/seed.sql`, sin `.env` ni datos reales privados.
