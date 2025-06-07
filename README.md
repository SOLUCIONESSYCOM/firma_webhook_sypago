# Firma de Webhooks de SyPago

Este repositorio contiene ejemplos de cómo validar las firmas de las notificaciones de webhook enviadas por SyPago. La validación de la firma es un paso de seguridad crucial para garantizar que las notificaciones que recibe su servidor provienen realmente de SyPago y que no han sido alteradas.

## Estructura del Repositorio

El repositorio está organizado en los siguientes directorios:

-   `csharp_server/`: Contiene un ejemplo de servidor en C# (.NET).
-   `go_server/`: Contiene un ejemplo de servidor en Go.
-   `java_server/`: Contiene un ejemplo de servidor en Java.
-   `js_server/`: Contiene un ejemplo de servidor en JavaScript (Node.js).
-   `python_server/`: Contiene un ejemplo de servidor en Python (Flask).
-   `FirmaSyPagoWebhook.postman_collection.json`: Una colección de Postman para probar los servidores de ejemplo.
-   `SyPago Proceso de Firma Para Notificaciones (Webhook Checkout).md`: Documentación detallada sobre el proceso de firma de webhooks de SyPago.

Cada directorio de servidor contiene un `README.md` con instrucciones específicas para ejecutar ese servidor en particular.

## Uso

Para utilizar los ejemplos de este repositorio, siga estos pasos:

1.  **Clone el repositorio:**
    ```bash
    git clone https://github.com/sypago/firma_webhook_sypago.git
    cd firma_webhook_sypago
    ```

2.  **Elija un lenguaje:**
    Navegue al directorio del lenguaje de su elección (por ejemplo, `cd python_server`).

3.  **Siga las instrucciones:**
    Dentro de cada directorio de servidor, encontrará un archivo `README.md` con instrucciones detalladas sobre cómo instalar las dependencias y ejecutar el servidor.

## Probar con Postman

La colección de Postman (`FirmaSyPagoWebhook.postman_collection.json`) está configurada para ayudarle a probar sus implementaciones de webhook.

### Pasos para usar la colección de Postman:

1.  **Importar la colección:**
    -   Abra Postman.
    -   Haga clic en `File` > `Import...`.
    -   Seleccione el archivo `FirmaSyPagoWebhook.postman_collection.json` del repositorio.

2.  **Ejecutar un servidor de ejemplo:**
    -   Asegúrese de tener uno de los servidores de ejemplo (por ejemplo, el de Python) ejecutándose localmente. Por defecto, los servidores escuchan en `http://localhost:5900/signature`.

3.  **Configurar las variables de entorno en Postman (Opcional):**
    -   La colección utiliza una variable `{{base_url}}`. Puede crear un entorno en Postman y establecer `base_url` en `http://localhost:5900` para no tener que escribir la URL completa cada vez.

4.  **Enviar una solicitud:**
    -   En la colección importada, encontrará una solicitud llamada `SyPago Webhook Signature`.
    -   Seleccione esta solicitud.
    -   En la pestaña `Body`, puede ver y modificar el payload de la notificación de ejemplo.
    -   Haga clic en el botón `Send` para enviar la solicitud a su servidor local.

5.  **Verificar la respuesta:**
    -   Si la firma es válida, su servidor debería devolver una respuesta `200 OK` con un mensaje de éxito.
    -   Si la firma no es válida, debería recibir una respuesta de error (por ejemplo, `401 Unauthorized`).

    Puede experimentar cambiando el `payload` o la `secret key` en la pestaña `Pre-request Script` de la solicitud de Postman para ver cómo el servidor rechaza las solicitudes con firmas no válidas. 