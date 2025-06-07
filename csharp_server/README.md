# Servidor C# (.NET) para Validación de Firma SyPago

Esta carpeta contiene una implementación de un servidor en C# y .NET para validar las firmas de los webhooks de SyPago.

El servidor escucha en el puerto `5900` y expone el endpoint `POST /signature`.

### Prerrequisitos

- .NET SDK (versión 8.0 o superior recomendada)

### Instalación y Ejecución

1.  Navegue a la carpeta `csharp_server`:
    ```bash
    cd csharp_server
    ```
2.  Restaure las dependencias (el comando `run` lo hace automáticamente):
    ```bash
    dotnet restore
    ```
3.  Ejecute el proyecto:
    ```bash
    dotnet run
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