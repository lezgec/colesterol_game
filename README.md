# Colesterol Game

Serious game educativo sobre colesterol con modo individual, salas docentes, banco de preguntas, generación con IA, reportes, perfiles, insignias, rachas, correos y paneles por rol.

Esta guía explica cómo instalar el proyecto en otro servidor PHP, desde cero, usando Apache/Nginx con PHP y MySQL/MariaDB.

## 1. Requisitos del servidor

Antes de subir el proyecto, verifica que el servidor tenga:

- PHP 8.1 o superior.
- MySQL 8.0.29+ o MariaDB 10.4+.
- Apache, Nginx o servidor compatible con PHP.
- Composer 2 si vas a reinstalar dependencias.
- Extensiones PHP: `mysqli`, `curl`, `mbstring`, `openssl`, `gd`, `json`, `fileinfo`.
- Permiso de escritura en `assets/uploads/`.

Para confirmar la versión de PHP:

```bash
php -v
```

## 2. Subir el proyecto

Clona o copia el proyecto en la carpeta pública del servidor.

Ejemplos:

```text
XAMPP Windows: C:\xampp\htdocs\colesterol_game
Apache Linux: /var/www/html/colesterol_game
cPanel: public_html/colesterol_game
```

Si usas Git:

```bash
git clone https://github.com/lezgec/colesterol_game.git colesterol_game
cd colesterol_game
```

Si el servidor no incluye `vendor/`, instala dependencias:

```bash
composer install --no-dev --optimize-autoloader
```

## 3. Configurar permisos

El sistema necesita escribir avatares y archivos cargados por usuarios.

En Linux:

```bash
chmod -R 775 assets/uploads
```

Si el servidor usa un usuario específico para Apache/Nginx, ajusta el propietario según corresponda:

```bash
chown -R www-data:www-data assets/uploads
```

En hosting compartido o cPanel, asegúrate de que `assets/uploads/` tenga permisos de escritura.

## 4. Crear la base de datos

Crea una base de datos vacía con codificación `utf8mb4`.

```sql
CREATE DATABASE colesterol_game_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Crea o usa un usuario de base de datos con permisos sobre esa base:

```sql
CREATE USER 'colesterol_user'@'localhost' IDENTIFIED BY 'cambia_esta_clave';
GRANT ALL PRIVILEGES ON colesterol_game_db.* TO 'colesterol_user'@'localhost';
FLUSH PRIVILEGES;
```

En cPanel también puedes hacerlo desde "MySQL Databases".

## 5. Importar estructura y datos iniciales

Para una instalación limpia, importa los archivos en este orden:

```bash
mysql -u colesterol_user -p colesterol_game_db < database/schema.sql
mysql -u colesterol_user -p colesterol_game_db < database/migrations/002_runtime_schema_requirements.sql
mysql -u colesterol_user -p colesterol_game_db < database/migrations/001_add_foreign_keys.sql
mysql -u colesterol_user -p colesterol_game_db < database/seed.sql
```

Si usas phpMyAdmin:

1. Selecciona la base `colesterol_game_db`.
2. Importa `database/schema.sql`.
3. Importa `database/migrations/002_runtime_schema_requirements.sql`.
4. Importa `database/migrations/001_add_foreign_keys.sql`.
5. Importa `database/seed.sql`.

Importante: `schema.sql` contiene `DROP TABLE`, por eso solo debe ejecutarse en una instalación limpia o en una base sin datos reales.

Las migraciones están pensadas para ejecutarse de forma controlada. `001_add_foreign_keys.sql` debe ejecutarse una sola vez; si necesitas un sistema de migraciones repetibles, agrega una tabla `schema_migrations` antes de automatizar despliegues.

## 6. Crear el archivo `.env`

Copia el archivo de ejemplo:

```bash
cp .env.example .env
```

En Windows puedes copiarlo manualmente:

```powershell
Copy-Item .env.example .env
```

Edita `.env` con los datos reales del servidor.

Ejemplo:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://tu-dominio.com/colesterol_game
APP_BASE_PATH=/colesterol_game
APP_SUPPORT_EMAIL=support@tu-dominio.com

DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=colesterol_game_db
DB_USERNAME=colesterol_user
DB_PASSWORD=cambia_esta_clave

MAIL_HOST=smtp-relay.brevo.com
MAIL_PORT=587
MAIL_ENCRYPTION=tls
MAIL_USERNAME=tu_login_smtp
MAIL_PASSWORD=tu_clave_smtp
MAIL_FROM_EMAIL=no-reply@tu-dominio.com
MAIL_FROM_NAME="Colesterol Game"
MAIL_REPLY_TO=support@tu-dominio.com

GEMINI_API_KEY=tu_api_key_de_gemini
GEMINI_MODEL=gemini-2.5-flash
```

Notas:

- `APP_URL` debe ser la URL pública completa hasta la carpeta del proyecto.
- `APP_BASE_PATH` debe coincidir con la subcarpeta pública. Si el proyecto está en la raíz del dominio, usa `/`.
- `APP_DEBUG=false` evita mostrar errores internos en producción.
- No subas `.env` al repositorio.

## 7. Configurar correo SMTP

El sistema usa SMTP para bienvenida, recuperación de contraseña y notificaciones.

Ejemplo con Brevo:

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

En Brevo, el correo definido en `MAIL_FROM_EMAIL` debe estar verificado como sender.

Si el SMTP falla, el registro no debe bloquearse; el error queda registrado para revisión.

## 8. Configurar Gemini

Para usar generación de preguntas con IA, configura:

```env
GEMINI_API_KEY=tu_api_key_de_gemini
GEMINI_MODEL=gemini-2.5-flash
```

Si no configuras Gemini, el resto del sistema puede funcionar, pero la generación automática de preguntas no estará disponible.

## 9. Abrir la aplicación

Entra desde el navegador:

```text
https://tu-dominio.com/colesterol_game/
```

Si instalaste en raíz:

```text
https://tu-dominio.com/
```

## 10. Usuario inicial

`database/seed.sql` crea un superadministrador genérico:

```text
Correo: superadmin@example.com
Contraseña: Admin2026@
Rol: super_admin
```

Después del primer inicio de sesión:

1. Cambia el correo del superadministrador.
2. Cambia la contraseña.
3. Crea los docentes y jugadores necesarios.
4. Verifica que el correo SMTP envíe correctamente.

## 11. Pruebas rápidas después de instalar

Haz estas pruebas antes de entregar el sistema:

1. Iniciar sesión con el superadministrador.
2. Registrar un jugador nuevo.
3. Registrar un docente nuevo.
4. Recuperar contraseña con un correo real.
5. Crear una sala como docente o superadministrador.
6. Entrar a una sala como jugador.
7. Responder una pregunta.
8. Ver ranking.
9. Exportar un reporte en CSV.
10. Exportar un reporte en PDF.
11. Generar preguntas con Gemini.
12. Revisar el historial de correos enviados.

## 12. Actualizar una instalación existente

Si ya existe una base con datos reales, no ejecutes `schema.sql`.

Usa este orden:

```bash
mysql -u usuario -p colesterol_game_db < database/migrations/002_runtime_schema_requirements.sql
# Validar registros huérfanos antes del siguiente paso.
mysql -u usuario -p colesterol_game_db < database/migrations/001_add_foreign_keys.sql
```

Antes de aplicar `001_add_foreign_keys.sql`, valida que no existan registros huérfanos en usuarios, salas, preguntas, respuestas y badges.

Después actualiza el código:

```bash
git pull origin main
composer install --no-dev --optimize-autoloader
```

## 13. Seguridad básica

- Mantén `.env` fuera del repositorio.
- Usa `APP_DEBUG=false` en producción.
- Cambia el superadministrador inicial.
- Usa contraseñas fuertes.
- Verifica que `assets/uploads/avatars/.htaccess` exista si usas Apache.
- En Nginx, replica la protección de `assets/uploads/avatars/.htaccess` en la configuración del servidor, porque Nginx no lee `.htaccess`.
- Los avatares aceptan JPG, PNG y WebP; el servidor re-codifica la imagen antes de guardarla.
- No guardes claves SMTP o Gemini dentro del código fuente.
- Usa HTTPS en producción.
- Revisa `docs/SECURITY_CHECKLIST.md` antes de publicar.

## 14. Archivos importantes

```text
database/schema.sql                         Estructura base para instalación limpia.
database/seed.sql                           Datos iniciales mínimos.
database/migrations/                        Migraciones para bases existentes.
.env.example                                Plantilla de configuración.
assets/uploads/                             Archivos subidos por usuarios.
docs/ARCHITECTURE.md                        Arquitectura general.
docs/SECURITY_CHECKLIST.md                  Checklist de seguridad.
docs/TEST_RUN_2026-06-29.md                 Registro de pruebas funcionales.
docs/TEST_RUN_2026-06-30.md                 Validación de seguridad y despliegue.
```

## 15. Problemas comunes

Si aparece un error de versión de PHP:

```text
Composer dependencies require a PHP version >= 8.1.0
```

Actualiza el servidor a PHP 8.1 o superior.

Si la aplicación no conecta a la base:

- Revisa `DB_HOST`, `DB_DATABASE`, `DB_USERNAME` y `DB_PASSWORD`.
- Confirma que el usuario tenga permisos sobre la base.
- Activa temporalmente `APP_DEBUG=true` solo en entorno local para ver más detalle.

Si los correos no salen:

- Revisa las credenciales SMTP.
- Verifica que el sender esté validado en Brevo o en tu proveedor.
- Revisa la sección de correos enviados dentro del panel.

Si la generación con IA no funciona:

- Revisa `GEMINI_API_KEY`.
- Confirma que el modelo configurado esté disponible.
- Verifica que el servidor tenga salida a internet.

## 16. Despliegue recomendado con Git

Para subir una versión:

```bash
git add .
git commit -m "Prepare deployment version"
git push origin main
```

Para crear una rama de despliegue independiente:

```bash
git checkout -b docente-deploy
git push -u origin docente-deploy
```

La rama de despliegue debe mantener separados:

- `database/schema.sql`
- `database/migrations/`
- `database/seed.sql`
- `.env.example`

Nunca debe incluir `.env` con credenciales reales.
