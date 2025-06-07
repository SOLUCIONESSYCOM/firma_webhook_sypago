# Servidor JavaScript (Node.js/Express) para Validación de Firma SyPago

Esta carpeta contiene una implementación de un servidor en Node.js y Express para validar las firmas de los webhooks de SyPago.

El servidor escucha en el puerto `5900` y expone el endpoint `POST /signature`.

### Prerrequisitos

- Node.js
- npm

### Instalación

1.  Asegúrese de estar en la carpeta `js_server`.
2.  Instale las dependencias:
    ```bash
    npm install
    ```

### Ejecución

Para iniciar el servidor, ejecute:
```bash
npm start
```
o
```bash
node server.js
```
El servidor se iniciará en `http://localhost:5900`.

### Cómo Probar

Para validar una notificación, envíe una petición `POST` al endpoint `/signature`, incluyendo:

- **Headers**:
    - `X-Signature`: La firma digital en Base64.
    - `X-Signature-Nonce`: El nonce.
    - `Content-Type`: `application/json`
- **Body**:
    - El cuerpo `JSON` crudo (raw) de la notificación.

Si la firma es válida, recibirá una respuesta `200 OK`. Si no, recibirá un `401 Unauthorized`. 