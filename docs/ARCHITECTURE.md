# Arquitectura del Proyecto

El proyecto mantiene una separación por responsabilidad sin cambiar las rutas públicas actuales:

- `pages/`: vistas PHP renderizadas por el servidor. Actúan como capa de presentación.
- `assets/`: CSS, JavaScript, sonidos, iconos y recursos estáticos del frontend.
- `backend/`: endpoints JSON, exportaciones y lógica de aplicación invocada por el frontend.
- `includes/`: helpers compartidos de autenticación, correo, menús, rate limit e iconos.
- `config/`: configuración de base de datos, correo, Gemini, países, categorías y reglas.
- `app/`: bootstrap y soporte transversal de entorno, HTTP y CSRF.
- `database/`: esquema, seed inicial y migraciones.
- `lang/`: traducciones ES/EN.
- `docs/`: documentación técnica y de seguridad.

## Flujo Frontend

Las páginas en `pages/` cargan estilos desde `assets/css` y scripts desde `assets/js`.
Los scripts llaman endpoints de `backend/` usando `fetch` y enviando `X-CSRF-Token` cuando modifican datos.

## Flujo Backend

Los endpoints de `backend/` deben:

1. Cargar `config/db.php` si usan base de datos.
2. Cargar `includes/auth.php` si usan sesión, roles o CSRF.
3. Validar rol y propiedad del recurso antes de modificar datos.
4. Devolver JSON en endpoints AJAX.
5. No confiar en identificadores sensibles enviados por el cliente.

## Salas

Las acciones docentes sobre salas deben pasar por:

- `backend/rooms/room_auth_helpers.php`
- `require_room_owner_or_super_admin($conn, $roomCode)`

Esto evita que un docente pueda modificar o exportar salas creadas por otro docente.

## Preguntas

Las preguntas tienen dos estados conceptuales:

- Revisión: `pending`, `verified`, `rejected`.
- Disponibilidad: `is_active`.

Una pregunta solo debe estar disponible para jugar si está verificada y activa.
Las preguntas creadas o generadas por IA inician como pendientes e inactivas.

## Reportes

Los reportes globales segmentan por rol:

- Superadmin: ve toda la información.
- Docente: ve información asociada a sus salas.

Los CSV usan protección contra CSV injection para evitar que celdas iniciadas con `=`, `+`, `-` o `@` se interpreten como fórmulas.
