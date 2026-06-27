# Colesterol Game

Serious game educativo sobre colesterol con modo individual, salas docentes, banco de preguntas, generación con IA, reportes, perfiles, insignias, rachas, correos y paneles por rol.

## Requisitos

- PHP 8.0 o superior.
- MariaDB/MySQL.
- Apache o servidor compatible con PHP.
- Composer si se desea reinstalar dependencias.
- Extensiones PHP comunes: mysqli, curl, mbstring, openssl y gd.

## Instalación

1. Copiar el proyecto en el servidor web.
   - XAMPP: `C:\xampp\htdocs\colesterol_game`
   - Hosting/Linux: carpeta publica equivalente.

2. Crear la base de datos.

```sql
CREATE DATABASE colesterol_game_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

3. Importar el SQL separado.

```bash
mysql -u usuario -p colesterol_game_db < database/schema.sql
mysql -u usuario -p colesterol_game_db < database/seed.sql
```

4. Crear el archivo `.env` desde `.env.example`.

```bash
cp .env.example .env
```

5. Configurar en `.env`:

```env
APP_URL=https://tu-dominio.com/colesterol_game
APP_BASE_PATH=/colesterol_game

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

6. Abrir la aplicación.

```text
https://tu-dominio.com/colesterol_game/
```

## Credenciales iniciales

El seed inicial crea un superadministrador genérico para primera instalación:

```text
Correo: superadmin@example.com
Contraseña: Admin2026@
Rol: super_admin
```

Cambia esta contraseña después del primer inicio de sesión.

## Seguridad y configuración

- No subas `.env` al repositorio.
- `.env.example` sí debe subirse, pero solo con datos ficticios.
- Las credenciales SMTP y Gemini se leen desde `.env`.
- Las respuestas correctas de las preguntas no deben enviarse al navegador antes de responder.
- El backend calcula si una respuesta es correcta y los puntos obtenidos.
- Cambia el superadmin genérico al desplegar en producción.

Documentación técnica:

- `docs/ARCHITECTURE.md`
- `docs/SECURITY_CHECKLIST.md`

## Flujo de despliegue recomendado

Version principal:

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

La rama `docente-deploy` debe mantener el SQL separado en `database/schema.sql` y no debe incluir `.env` ni datos reales privados.
