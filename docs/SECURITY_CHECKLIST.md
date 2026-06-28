# Checklist de Seguridad

## Antes de Producción

- Confirmar que `.env` no esté versionado.
- Configurar `.env` con credenciales reales solo en el servidor.
- Cambiar la contraseña del superadministrador genérico.
- Verificar que Apache respete `.htaccess`.
- Confirmar que `APP_DEBUG=false`.
- Confirmar que `SESSION_COOKIE_SECURE=true` cuando se use HTTPS.
- Importar `database/schema.sql` solo en instalaciones limpias.
- Importar `database/seed.sql` después del esquema.
- Revisar datos huérfanos antes de ejecutar migraciones con llaves foráneas.

## Endpoints

- Todo endpoint mutante debe usar `require_csrf_token()`.
- Todo endpoint de sala administrable debe validar propietario o superadmin.
- Todo endpoint con sesión debe cargar `includes/auth.php`.
- Los endpoints JSON no deben imprimir warnings, notices ni HTML.
- Los endpoints nuevos deben responder mediante `backend/support/api_response.php`.
- El backend debe calcular puntajes, aciertos y permisos; el cliente solo envía acciones.

## Usuarios

- El registro público crea solo jugadores.
- Docentes y superadmins deben ser creados o promovidos desde gestión administrativa.
- Usuarios inactivos no pueden iniciar sesión.
- Las sesiones dobles se controlan con `session_token`.

## IA

- `GEMINI_API_KEY` debe vivir en `.env`.
- Las preguntas generadas por IA deben guardarse como pendientes e inactivas.
- Las preguntas con siglas deben incluir el significado completo la primera vez.

## Correos

- Credenciales SMTP en `.env`.
- Sender verificado en el proveedor de correo.
- Historial de correos visible desde el panel administrativo.

## CSV y Exportes

- Los CSV deben pasar por `export_csv_write()`.
- Los reportes docentes deben filtrar por salas del docente.
- Los PDF/CSV de sala deben validar propiedad de la sala.
