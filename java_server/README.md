# Servidor Java (Spring Boot) para Validación de Firma SyPago

Esta carpeta contiene una implementación de un servidor en Java y Spring Boot para validar las firmas de los webhooks de SyPago.

El servidor escucha en el puerto `5900` y expone el endpoint `POST /signature`.

### Prerrequisitos

- Java Development Kit (JDK) 17 o superior.
- Apache Maven.

### Instalación y Ejecución

1.  Navegue a la carpeta `java_server`:
    ```bash
    cd java_server
    ```
2.  Compile el proyecto y genere el paquete usando Maven. Esto también descargará las dependencias necesarias.
    ```bash
    mvn clean package
    ```
3.  Ejecute el archivo JAR generado:
    ```bash
    java -jar target/sypago-validator-0.0.1-SNAPSHOT.jar
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