# Servidor Python (Flask) para Validación de Firma SyPago

Esta carpeta contiene una implementación de un servidor en Python y Flask para validar las firmas de los webhooks de SyPago.

El servidor escucha en el puerto `5900` y expone el endpoint `POST /signature`.

### Prerrequisitos

- Python 3.6+
- pip

### Instalación

1.  Asegúrese de estar en la carpeta `python_server`.
2.  Cree un entorno virtual (recomendado):
    ```bash
    python -m venv venv
    ```
3.  Active el entorno virtual:
    -   En Windows: `venv\\Scripts\\activate`
    -   En macOS/Linux: `source venv/bin/activate`

4.  Instale las dependencias:
    ```bash
    pip install -r requirements.txt
    ```

### Ejecución

Para iniciar el servidor, ejecute:
```bash
python main.py
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