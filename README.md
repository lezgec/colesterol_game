# Colesterol Game

Serious game educativo sobre colesterol con modo individual, salas docentes, banco de preguntas, generación con IA, reportes, perfiles, insignias, rachas, correos y paneles por rol.

## Requisitos

- PHP 8.0 o superior.
- MariaDB/MySQL.
- Apache o servidor compatible con PHP.
- Composer si se desea reinstalar dependencias.
- Extensiones PHP comunes: mysqli, curl, mbstring, openssl, gd.

## Instalación rápida

1. Copiar el proyecto en el servidor web.
   - En XAMPP: `C:\xampp\htdocs\colesterol_game`
   - En Linux/hosting: carpeta pública equivalente.

2. Crear la base de datos:

```sql
CREATE DATABASE colesterol_game_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

3. Importar el SQL separado:

```bash
mysql -u usuario -p colesterol_game_db < database/schema.sql
```

4. Configurar conexión en `config/db.php`.

5. Configurar correo SMTP en `config/mail.php`.

6. Configurar Gemini en `config/gemini.php`.

7. Abrir la aplicación:

```text
https://tu-dominio.com/colesterol_game/
```

## Credenciales iniciales

El SQL incluye un superadministrador genérico:

```text
Correo: superadmin@example.com
Contraseña: Admin2026@
Rol: super_admin
```

Cambia esta contraseña después del primer inicio de sesión.

## Configuración de correo

`config/mail.php` está versionado con datos de ejemplo. Para producción cambia:

```php
"username" => "tu_correo_smtp",
"password" => "tu_app_password",
"from_email" => "support@tu-dominio.com",
"reply_to" => "support@tu-dominio.com"
```

## Configuración de Gemini

`config/gemini.php` está versionado con una clave de ejemplo. Para producción cambia:

```php
define("GEMINI_API_KEY", "TU_API_KEY_REAL");
define("GEMINI_MODEL", "gemini-2.5-flash");
```

## Notas de seguridad

- Los archivos `config/mail.php` y `config/gemini.php` del repositorio contienen datos de ejemplo.
- No subir contraseñas reales ni claves reales al repositorio.
- Cambia el superadmin genérico después de instalar.
- Si el servidor usa otra ruta base, actualiza `app_url` en `config/mail.php`.
