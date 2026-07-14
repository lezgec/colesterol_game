# Colesterol Game

Serious game educativo sobre colesterol con modo individual, salas docentes, banco de preguntas, generación con IA, reportes, perfiles, insignias, rachas, correos y paneles por rol.

## Descripción del juego

Colesterol Game es una plataforma educativa tipo serious game para reforzar el aprendizaje sobre colesterol, factores de riesgo, estilos de vida saludables, prevención y conceptos clínicos básicos. El sistema permite jugar partidas individuales con dificultad adaptativa, crear salas guiadas por docentes, competir en rankings, ganar insignias, revisar rachas, exportar reportes y administrar un banco de preguntas validado.

El proyecto incluye:

- Modo jugador con partida individual, historial, ranking global, perfil, insignias y rachas.
- Modo docente para crear salas, dirigir partidas, revisar resultados y proponer preguntas al banco global.
- Modo superadministrador para gestionar usuarios, banco de preguntas, reportes, notificaciones, correos enviados y configuración general del contenido.
- Banco de preguntas con creación manual, importación CSV, generación con Gemini y flujo de revisión antes de publicar.
- Reportes CSV/PDF para jugadores, salas y analítica general.
- Correos transaccionales para bienvenida, recuperación de contraseña, cambios de rol y avisos administrativos.

## Dificultad adaptativa

Las preguntas se clasifican en niveles de dificultad de `1` a `5`. Internamente, el motor puede manejar valores decimales para ajustar el progreso con más precisión, pero al seleccionar preguntas busca el nivel entero más cercano disponible en el banco.

La dificultad sube cuando el jugador responde correctamente y con buen tiempo de respuesta. Si el jugador falla, responde tarde o agota el tiempo, la dificultad baja o se mantiene según su desempeño. De esta forma, el sistema intenta mantener la partida desafiante sin bloquear al estudiante con preguntas demasiado difíciles ni dejarlo demasiado tiempo en preguntas simples.

En salas docentes, la sala puede iniciar con una configuración base y luego el docente o administrador puede ajustar cantidad de preguntas, tiempo por pregunta y selección de preguntas desde el lobby. Las preguntas generadas por IA o importadas por CSV no quedan publicadas automáticamente como disponibles: primero deben revisarse y activarse.

Esta guía explica cómo instalar el proyecto en otro servidor PHP usando Apache/Nginx con PHP y MySQL/MariaDB.

## 1. Requisitos

- PHP 8.1 o superior.
- MySQL 8.0.29+ o MariaDB 10.4+.
- Apache, Nginx o servidor compatible con PHP.
- Composer 2 si necesitas reinstalar dependencias.
- Extensiones PHP: `mysqli`, `curl`, `mbstring`, `openssl`, `gd`, `json`, `fileinfo`.
- Permiso de escritura en `assets/uploads/`.

Verifica PHP con:

```bash
php -v
```

## 2. Subir el proyecto

Copia o clona el proyecto dentro de la carpeta pública del servidor.

Ejemplos:

```text
XAMPP Windows: C:\xampp\htdocs\colesterol_game
Apache Linux: /var/www/html/colesterol_game
cPanel: public_html/tu-carpeta
```

Con Git:

```bash
git clone https://github.com/lezgec/colesterol_game.git tu-carpeta
cd tu-carpeta
```

Si el servidor no trae `vendor/`, instala dependencias:

```bash
composer install --no-dev --optimize-autoloader
```

## 3. Permisos

El sistema necesita escribir avatares y archivos subidos.

En Linux:

```bash
chmod -R 775 assets/uploads
```

Si controlas el usuario del servidor web:

```bash
chown -R www-data:www-data assets/uploads
```

En cPanel, revisa que `assets/uploads/` tenga permisos de escritura.

## 4. Base de datos

Crea una base vacía con `utf8mb4`.

```sql
CREATE DATABASE colesterol_game_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Crea o usa un usuario con permisos sobre esa base.

```sql
CREATE USER 'colesterol_user'@'localhost' IDENTIFIED BY 'cambia_esta_clave';
GRANT ALL PRIVILEGES ON colesterol_game_db.* TO 'colesterol_user'@'localhost';
FLUSH PRIVILEGES;
```

En cPanel también puedes hacerlo desde **MySQL Databases**.

## 5. Importar SQL único

Para una instalación limpia, importa un solo archivo:

```bash
mysql -u colesterol_user -p colesterol_game_db < database/colesterol_game_full.sql
```

En phpMyAdmin:

1. Selecciona la base `colesterol_game_db`.
2. Entra a **Importar**.
3. Selecciona `database/colesterol_game_full.sql`.
4. Ejecuta la importación.

Importante: `database/colesterol_game_full.sql` contiene `DROP TABLE`, por eso solo debe usarse en una instalación limpia o en una base sin datos reales.

Este repositorio mantiene un solo archivo SQL oficial para instalación limpia: `database/colesterol_game_full.sql`.

## 6. Crear `.env`

Copia el archivo de ejemplo:

```bash
cp .env.example .env
```

En Windows:

```powershell
Copy-Item .env.example .env
```

Edita `.env` con los datos reales.

Ejemplo para un proyecto instalado en una subcarpeta pública:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://tu-dominio.com/tu-carpeta
APP_BASE_PATH=/tu-carpeta
APP_SUPPORT_EMAIL=support@example.com

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
MAIL_FROM_EMAIL=no-reply@example.com
MAIL_FROM_NAME="Cholesterol Game"
MAIL_REPLY_TO=support@example.com

GEMINI_API_KEY=tu_api_key_de_gemini
GEMINI_MODEL=gemini-2.5-flash
```

Notas:

- `APP_URL` debe ser la URL pública completa hasta la carpeta del proyecto.
- `APP_BASE_PATH` debe coincidir con la subcarpeta pública.
- Si el proyecto está en la raíz del dominio, usa `APP_BASE_PATH=/`.
- `APP_DEBUG=false` evita mostrar errores internos en producción.
- No subas `.env` al repositorio.

## 7. Correo SMTP

El sistema usa SMTP para bienvenida, recuperación de contraseña y notificaciones.

Ejemplo con Brevo:

```env
MAIL_HOST=smtp-relay.brevo.com
MAIL_PORT=587
MAIL_ENCRYPTION=tls
MAIL_USERNAME=tu_login_smtp_brevo
MAIL_PASSWORD=tu_clave_smtp_brevo
MAIL_FROM_EMAIL=no-reply@example.com
MAIL_FROM_NAME="Cholesterol Game"
MAIL_REPLY_TO=support@example.com
```

El correo definido en `MAIL_FROM_EMAIL` debe estar verificado como sender en Brevo.

Si SMTP falla, el registro no debe bloquearse; el error queda registrado para revisión.

## 8. Gemini

Para usar generación de preguntas con IA:

```env
GEMINI_API_KEY=tu_api_key_de_gemini
GEMINI_MODEL=gemini-2.5-flash
```

Si no configuras Gemini, el resto del sistema puede funcionar, pero la generación automática de preguntas no estará disponible.

## 9. Usuario inicial

El archivo `database/colesterol_game_full.sql` crea este superadministrador:

```text
Correo: superadmin@example.com
Contraseña: Admin2026@
Rol: super_admin
```

Después del primer inicio:

1. Cambia el correo.
2. Cambia la contraseña.
3. Crea docentes y jugadores.
4. Verifica que SMTP envíe correctamente.

## 10. Abrir la aplicación

Ejemplo con subcarpeta:

```text
https://tu-dominio.com/tu-carpeta/
```

Ejemplo en raíz:

```text
https://tu-dominio.com/
```

Si la página carga sin estilos, revisa `APP_BASE_PATH`. Por ejemplo, si el proyecto está en `/tu-carpeta`, debe ser:

```env
APP_BASE_PATH=/tu-carpeta
APP_URL=https://tu-dominio.com/tu-carpeta
```

## 11. Pruebas rápidas

Antes de entregar, prueba:

1. Iniciar sesión con el superadministrador.
2. Registrar un jugador.
3. Registrar un docente.
4. Recuperar contraseña con un correo real.
5. Crear una sala.
6. Entrar a una sala como jugador.
7. Responder preguntas.
8. Ver ranking.
9. Exportar CSV.
10. Exportar PDF.
11. Generar preguntas con Gemini.
12. Revisar correos enviados.
13. Revisar notificaciones de preguntas enviadas al banco global.

## 12. Actualizar una base existente

Si ya tienes datos reales, no ejecutes `database/colesterol_game_full.sql`, porque es destructivo y contiene `DROP TABLE`.

Para actualizar una base existente:

1. Haz backup completo de la base.
2. Compara tu base actual contra `database/colesterol_game_full.sql`.
3. Aplica manualmente solo los cambios necesarios con `ALTER TABLE`.
4. Luego actualiza el código:

```bash
git pull origin main
composer install --no-dev --optimize-autoloader
```

Para despliegues nuevos o servidores limpios, usa siempre `database/colesterol_game_full.sql`.

## 13. Seguridad

- Mantén `.env` fuera del repositorio.
- Usa `APP_DEBUG=false` en producción.
- Cambia el superadministrador inicial.
- Usa contraseñas fuertes.
- Usa HTTPS.
- Verifica que `.env` no sea visible desde el navegador.
- Si usas Apache, confirma que `.htaccess` se respete.
- Si usas Nginx, replica las protecciones de `.htaccess` en la configuración del servidor.
- Los avatares aceptan JPG, PNG y WebP; el servidor re-codifica la imagen antes de guardarla.
- No guardes claves SMTP o Gemini en el código fuente.

## 14. Archivos importantes

```text
database/colesterol_game_full.sql           Instalador unificado para una base limpia.
.env.example                                Plantilla de configuración.
assets/uploads/                             Archivos subidos por usuarios.
docs/ARCHITECTURE.md                        Arquitectura general.
docs/SECURITY_CHECKLIST.md                  Checklist de seguridad.
docs/TEST_RUN_2026-06-30.md                 Validación de seguridad y despliegue.
```

## 15. Problemas comunes

Si aparece error de PHP:

```text
Composer dependencies require a PHP version >= 8.1.0
```

Actualiza el servidor a PHP 8.1 o superior.

Si no conecta a la base:

- Revisa `DB_HOST`, `DB_DATABASE`, `DB_USERNAME` y `DB_PASSWORD`.
- Confirma que el usuario tenga permisos.
- Activa temporalmente `APP_DEBUG=true` solo en entorno local.

Si se ve sin estilos:

- Revisa `APP_BASE_PATH`.
- Limpia caché del navegador.
- Confirma que `assets/css/style.css` exista en el servidor.

Si los correos no salen:

- Revisa credenciales SMTP.
- Verifica el sender en Brevo.
- Revisa la sección de correos enviados en el panel.

Si Gemini no funciona:

- Revisa `GEMINI_API_KEY`.
- Confirma que el modelo exista.
- Verifica salida a internet desde el servidor.

## 16. Despliegue con Git

Para subir cambios:

```bash
git add .
git commit -m "Prepare deployment version"
git push origin main
```

Nunca subas `.env` con credenciales reales.
