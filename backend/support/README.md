# Backend Support

Helpers compartidos para endpoints de backend.

- `api_response.php`: respuestas JSON estandarizadas para endpoints AJAX.

Los endpoints nuevos deben preferir:

- `api_success([...])`
- `api_error("Mensaje", 400)`
- `api_response([...], 200)`
