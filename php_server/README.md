# Servidor PHP para Validación de Firma SyPago

Esta carpeta contiene una implementación de un servidor en PHP para validar las firmas de los webhooks de SyPago.

## Requisitos

- PHP 7.4 o superior
- Extensión OpenSSL para PHP

## Estructura de Archivos

- `signature_validator.php`: Contiene la clase para validar firmas ECDSA
- `webhook.php`: Script que recibe las notificaciones webhook y procesa las peticiones
- `README.md`: Este archivo

## Instalación y Ejecución

1. Asegúrese de tener PHP instalado con la extensión OpenSSL habilitada:
   ```bash
   php -m | grep openssl
   ```

2. Navegue a la carpeta `php_server`:
   ```bash
   cd php_server
   ```

3. Inicie el servidor PHP integrado para pruebas:
   ```bash
   php -S localhost:8000
   ```

   El servidor estará disponible en `http://localhost:8000`.

## Cómo Probar

Para validar una notificación, envíe una petición `POST` al script `webhook.php`, incluyendo:

- **Headers**:
  - `X-Signature`: La firma digital en Base64.
  - `X-Signature-Nonce`: El nonce.
  - `Content-Type`: `application/json`
- **Body**:
  - El cuerpo `JSON` crudo (raw) de la notificación.

### Ejemplo con curl

```bash
curl -X POST http://localhost:8000/webhook.php \
  -H "Content-Type: application/json" \
  -H "X-Signature: <firma_en_base64>" \
  -H "X-Signature-Nonce: <nonce>" \
  -d '{"transaction_id":"EF806AFEE804","status":"completed"}'
```

## Configuración para Producción

Para un entorno de producción:

1. **Almacenamiento Seguro**: Guarde el `operationSecret` en una base de datos o sistema de caché.

2. **Obtención de Clave Pública**: Implemente la lógica para obtener la clave pública desde la API de SyPago.

3. **Seguridad**: Asegúrese de que su servidor utiliza HTTPS y tiene las cabeceras de seguridad apropiadas.

4. **Procesamiento Asíncrono**: Para mejor rendimiento, considere procesar las notificaciones de forma asíncrona.

## Consideraciones Importantes

- El webhook debe responder rápidamente (idealmente en menos de 5 segundos).
- Almacene y procese la notificación antes de responder para evitar pérdida de datos.
- Implemente un mecanismo de reintentos para operaciones críticas.
