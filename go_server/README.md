# Servidor Go (Gin) para Validación de Firma SyPago

Esta carpeta contiene una implementación de un servidor en Go y Gin para validar las firmas de los webhooks de SyPago.

El servidor escucha en el puerto `5900` y expone el endpoint `POST /signature`.

### Prerrequisitos

- Go (versión 1.18 o superior recomendada)

### Instalación

1.  Navegue a la carpeta `go_server`:
    ```bash
    cd go_server
    ```
2.  Inicialice el módulo (solo la primera vez):
    ```bash
    go mod init sypago-go-server
    ```
3.  Descargue las dependencias:
    ```bash
    go mod tidy
    ```

### Ejecución

Para iniciar el servidor, ejecute:
```bash
go run .
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