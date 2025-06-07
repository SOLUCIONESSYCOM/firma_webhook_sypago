# SyPago Proceso de Firma Para Notificaciones (Webhook)

Para más seguridad de los usuarios de SyPago, todas las notificaciones de webhook son firmadas digitalmente. Este proceso garantiza que las notificaciones que recibe en su servidor provienen de SyPago y que su contenido no ha sido alterado durante el tránsito.

Este documento explica los conceptos y algoritmos criptográficos utilizados en nuestro sistema de firma.

## Conceptos Clave

Nuestro mecanismo de firma se basa en varios componentes criptográficos estándar de la industria para garantizar la autenticidad e integridad de los datos.

*   **Clave Privada del Usuario**: Para cada usuario de API (e inclusive para cada sub-usuario), SyPago genera un par de claves criptográficas asimétricas (pública y privada) únicas. La clave privada se utiliza para firmar digitalmente todas las notificaciones. Es importante destacar que esta clave privada se almacena de forma segura y **nunca abandona los servidores de SyPago**, garantizando su máxima protección.
*   **Payload**: Es el cuerpo de la notificación en formato JSON que contiene los detalles del evento. Este es el dato principal que se protege.
*   **Nonce**: Un "número usado una sola vez". En nuestro sistema, generamos un nonce a partir de la marca de tiempo Unix (en milisegundos) en el momento en que se crea la notificación. La inclusión de un nonce en cada firma previene ataques de repetición, donde un atacante podría interceptar una notificación y reenviarla para duplicar una transacción.
*   **Secreto de Operación (`operationSecret`)**: Este es un identificador único universal (UUID) que se genera y devuelve al inicio de cada operación que puede resultar en una notificación asíncrona. Cuando invoca un endpoint de SyPago (por ejemplo, para iniciar un debito otp), la respuesta incluirá tanto el `transaction_id` como el `operation_secret`. Es crucial que su sistema almacene ambos valores.
    ```json
    {
        "transaction_id": "EF806AFEE804",
        "operation_secret": "9f4aaf08-8d04-4007-a097-c0e95eddad5e"
    }
    ```
    Al incluir este secreto en el proceso de firma, se asegura que la firma es única para esa transacción específica.

## Algoritmos Criptográficos

Utilizamos una combinación de algoritmos robustos y eficientes para generar la firma digital.

### SHA-256 (Secure Hash Algorithm 256-bit)

Antes de firmar los datos, aplicamos la función de hash SHA-256 a la concatenación del `payload`, el `nonce` y el `operationSecret`. SHA-256 produce una huella digital (hash) de tamaño fijo (256 bits) del mensaje original. Cualquier cambio, por mínimo que sea, en los datos de entrada resultará en un hash completamente diferente. Esto asegura la integridad de los datos.

### ECDSA (Elliptic Curve Digital Signature Algorithm)

Para la firma digital, hemos elegido ECDSA. Este es un algoritmo de firma de clave asimétrica basado en la criptografía de curva elíptica.

**¿Por qué ECDSA?**

La elección de ECDSA se basa en su excelente equilibrio entre seguridad y rendimiento:

*   **Seguridad Robusta**: ECDSA ofrece un nivel de seguridad comparable a otros algoritmos como RSA, pero con claves mucho más cortas. Por ejemplo, una clave ECDSA de 256 bits proporciona un nivel de seguridad similar a una clave RSA de 3072 bits.
*   **Alto Rendimiento**: Las claves más pequeñas de ECDSA se traducen en operaciones criptográficas (generación de claves, firma y verificación) más rápidas y con menor consumo de recursos computacionales. Esto es crucial para un servicio como SyPago, que necesita procesar un gran volumen de notificaciones de forma rápida y eficiente sin sacrificar la seguridad.

## Proceso de Firma (Lado de SyPago)

1.  **Construcción del Mensaje**: Se crea una cadena de texto única concatenando el `payload` de la notificación, el `nonce` y el `operationSecret`, separados por puntos.
    ```
    stringToSign = "{payload}.{nonce}.{operationSecret}"
    ```
2.  **Generación de la Firma**:
    *   Se calcula el hash SHA-256 de `stringToSign`.
    *   Este hash se firma utilizando la **clave privada del usuario de API** con el algoritmo ECDSA.
    *   La firma binaria resultante se codifica en Base64 para facilitar su transmisión en las cabeceras HTTP.
3.  **Envío de la Notificación**: Se envía la notificación HTTP POST a su endpoint de webhook con:
    *   El `payload` original en el cuerpo de la solicitud.
    *   La firma en Base64 en la cabecera `X-Signature`.
    *   El `nonce` utilizado en la cabecera `X-Signature-Nonce`.

## Proceso de Verificación Detallado (Su Lado)

Para validar la autenticidad de una notificación, su sistema debe realizar los siguientes pasos. Es recomendable obtener y almacenar en caché su clave pública para no tener que solicitarla en cada verificación.

### Paso 1: Obtener su Clave Pública

La verificación de la firma se realiza utilizando su clave pública. Esta clave es la contraparte de la clave privada que SyPago utiliza para firmar las notificaciones en su nombre.

Para obtenerla, debe realizar una petición `GET` al siguiente endpoint, asegurándose de incluir su JWT de autenticación en la cabecera `Authorization`.

**Endpoint:**
`GET /api/v1/user/key`

**Respuesta Exitosa (200 OK):**
El endpoint retornará su clave pública en formato PEM.

```json
{
    "public_key": "-----BEGIN PUBLIC KEY-----\nMFkwEwYHKoZIzj0CAQYIKoZIzj0DAQcDQgAE9hXXl4g886loHmI10dLrJWFHEB8r\ncyqD1hBdIM3ekQkb5YTpOShAu+7xk0fyL/0IBjLEKwSd7rsKrYGtIgJj0w==\n-----END PUBLIC KEY-----\n"
}
```
Debe almacenar esta clave de forma segura para utilizarla en el paso de verificación.

### Paso 2: Extraer los Componentes de la Notificación

Cuando su endpoint de webhook recibe una notificación de SyPago, debe extraer los siguientes tres componentes:

1.  **El `payload`**: Es el cuerpo JSON completo de la solicitud HTTP POST.
2.  **La cabecera `X-Signature`**: Contiene la firma digital codificada en Base64.
3.  **La cabecera `X-Signature-Nonce`**: Contiene el nonce utilizado para generar la firma.

### Paso 3: Reconstruir el Mensaje a Verificar

Debe construir la misma cadena de texto (`stringToSign`) que SyPago generó para la firma. Para ello, necesita el `operation_secret` que guardó cuando inició la transacción.

Concatene el `payload` (exactamente como lo recibió), el `nonce` de la cabecera y su `operation_secret`, separados por puntos.

```
stringToSign = "{payload}.{nonce}.{operationSecret}"
```
**Importante**: El `payload` debe ser la cadena de texto cruda del cuerpo de la solicitud, sin ninguna modificación o re-serialización, ya que cualquier cambio, incluso de espacios en blanco, invalidará la firma.

### Paso 4: Verificar la Firma

Con todos los componentes listos, proceda con la verificación criptográfica:

1.  **Decodificar la Firma**: Tome el valor de la cabecera `X-Signature` y decodifíquelo de Base64 a bytes.
2.  **Realizar la Verificación**: Utilizando una biblioteca criptográfica compatible con ECDSA, use su **clave pública** (obtenida en el Paso 1) para verificar que la firma decodificada corresponde al hash SHA-256 de la cadena `stringToSign` que reconstruyó en el Paso 3.

**Ejemplo Conceptual (pseudocódigo):**
```
publicKey = "-----BEGIN PUBLIC KEY-----\n..." // Obtenida del endpoint /api/v1/user/key
signatureB64 = request.headers['X-Signature']
nonce = request.headers['X-Signature-Nonce']
payload = request.body.raw_string
operationSecret = database.getSecretForTransaction(payload.transaction_id)

stringToSign = $"{payload}.{nonce}.{operationSecret}"

isValid = ECDSA.verify(publicKey, stringToSign, Base64.decode(signatureB64), "SHA256")

if (isValid) {
  // La notificación es auténtica. Procesar el pago.
} else {
  // La notificación es inválida. Descartar y registrar el intento.
}
```

Si la función de verificación devuelve `true`, puede estar seguro de que la notificación es auténtica, proviene de SyPago y no ha sido modificada. Si devuelve `false`, debe descartar la notificación, ya que podría ser fraudulenta, y es recomendable registrar el evento para su revisión.

## Códigos de Ejemplo para Verificar la Firma

A continuación, se presentan ejemplos de código en varios lenguajes para ayudarle a implementar la validación de la firma en su backend.

### Go

El ejemplo en Go se divide en dos partes: el manejador de la petición HTTP que recibe la notificación y la función de validación criptográfica.

#### 1. Recepción de la Notificación (Framework Gin)

Este código muestra cómo configurar un endpoint en su servidor usando el popular framework [Gin](https://gin-gonic.com/) para recibir la notificación de SyPago, extraer los datos necesarios y llamar a la función de validación.

```go
package main

import (
	"errors"
	"fmt"
	"io"
	"net/http"

	"github.com/gin-gonic/gin"
)

// WebhookHandler procesa las notificaciones de SyPago
func WebhookHandler(c *gin.Context) {
	// 1. Extraer los componentes de la notificación
	signature := c.Request.Header.Get("X-Signature")
	if signature == "" {
		c.AbortWithError(http.StatusBadRequest, errors.New("cabecera X-Signature es requerida"))
		return
	}

	nonce := c.Request.Header.Get("X-Signature-Nonce")
	if nonce == "" {
		c.AbortWithError(http.StatusBadRequest, errors.New("cabecera X-Signature-Nonce es requerida"))
		return
	}

	payload, err := io.ReadAll(c.Request.Body)
	if err != nil {
		c.AbortWithError(http.StatusBadRequest, err)
		return
	}

	// 2. Validar la firma
	err = ValidateSyPagoSignature(signature, payload, nonce)
	if err != nil {
		// La firma no es válida. Descartar la petición.
		fmt.Printf("Error al validar la firma: %v\n", err)
		c.AbortWithError(http.StatusUnauthorized, err)
		return
	}

	// 3. La firma es válida. Procesar la notificación.
	fmt.Println("Firma válida. Procesando notificación...")
	// Aquí iría la lógica para procesar el payload de la notificación.
	// Por ejemplo, actualizar el estado de una orden en la base de datos.

	c.JSON(http.StatusOK, gin.H{"message": "Firma válida y notificación recibida"})
}
```

#### 2. Validación de la Firma

Esta función contiene la lógica criptográfica principal para verificar la firma. Toma los datos extraídos de la notificación y los valida contra su clave pública y el secreto de la operación.

```go
package main

import (
	"crypto/ecdsa"
	"crypto/sha256"
	"crypto/x509"
	"encoding/base64"
	"encoding/pem"
	"fmt"
)

// ValidateSyPagoSignature verifica la firma de una notificación de SyPago.
func ValidateSyPagoSignature(signatureB64 string, payload []byte, nonce string) error {
	// --- Obtención de Secretos ---
	// El `operationSecret` DEBE ser recuperado de su base de datos.
	// Lo guardó cuando inició la transacción con SyPago y recibió el `transaction_id`.
	operationSecret := "9f4aaf08-8d04-4007-a097-c0e95eddad5e" // ¡EJEMPLO! Reemplazar con su lógica de obtención.

	// La `publicKeyPEM` DEBE ser obtenida del endpoint GET /api/v1/user/key
	// usando su token JWT. Es recomendable cachear esta clave para no solicitarla en cada notificación.
	publicKeyPEM := `-----BEGIN PUBLIC KEY-----
MFkwEwYHKoZIzj0CAQYIKoZIzj0DAQcDQgAE9hXXl4g886loHmI10dLrJWFHEB8r
cyqD1hBdIM3ekQkb5YTpOShAu+7xk0fyL/0IBjLEKwSd7rsKrYGtIgJj0w==
-----END PUBLIC KEY-----` // ¡EJEMPLO! Reemplazar con la clave real obtenida.

	// --- Proceso de Verificación ---

	// 1. Reconstruir el mensaje a verificar (payload.nonce.operationSecret)
	stringToVerify := fmt.Sprintf("%s.%s.%s", string(payload), nonce, operationSecret)
	dataToVerify := []byte(stringToVerify)

	// 2. Calcular el hash SHA-256 del mensaje
	hash := sha256.Sum256(dataToVerify)

	// 3. Decodificar la firma de Base64
	signatureBytes, err := base64.StdEncoding.DecodeString(signatureB64)
	if err != nil {
		return fmt.Errorf("error decodificando la firma desde base64: %v", err)
	}

	// 4. Parsear la clave pública en formato PEM
	block, _ := pem.Decode([]byte(publicKeyPEM))
	if block == nil {
		return fmt.Errorf("error decodificando el bloque PEM de la clave pública")
	}
	genericPublicKey, err := x509.ParsePKIXPublicKey(block.Bytes)
	if err != nil {
		return fmt.Errorf("error parseando la clave pública: %v", err)
	}

	// 5. Convertir a una clave pública ECDSA
	publicKey, ok := genericPublicKey.(*ecdsa.PublicKey)
	if !ok {
		return fmt.Errorf("la clave pública no es de tipo ECDSA")
	}

	// 6. Verificar la firma usando el estándar ASN.1 (formato RFC3279 DER Sequence)
	isSignatureValid := ecdsa.VerifyASN1(publicKey, hash[:], signatureBytes)
	if !isSignatureValid {
		return fmt.Errorf("la firma no es válida")
	}

	// La firma es válida
	return nil
}
```

### C# (.NET)

El ejemplo en C# se divide en dos partes: un endpoint de API en ASP.NET Core que recibe la notificación, y una clase de servicio para la validación criptográfica.

#### 1. Recepción de la Notificación (ASP.NET Core Minimal API)

Este código muestra cómo configurar un endpoint para recibir la notificación de SyPago. Es importante configurar la lectura del cuerpo de la solicitud como un `string` o `byte[]` crudo para no alterar su contenido.

```csharp
// En Program.cs o donde configures tus endpoints
using Microsoft.AspNetCore.Mvc;
using System.Security.Cryptography;
using System.Text;

// ...

app.MapPost("/webhook-sypago", async (HttpRequest request, [FromServices] ISignatureValidationService validationService) =>
{
    // 1. Extraer los componentes de la notificación
    if (!request.Headers.TryGetValue("X-Signature", out var signature))
    {
        return Results.BadRequest("Cabecera X-Signature es requerida");
    }

    if (!request.Headers.TryGetValue("X-Signature-Nonce", out var nonce))
    {
        return Results.BadRequest("Cabecera X-Signature-Nonce es requerida");
    }

    string payload;
    using (var reader = new StreamReader(request.Body, Encoding.UTF8))
    {
        payload = await reader.ReadToEndAsync();
    }
    
    // 2. Validar la firma
    try
    {
        await validationService.ValidateSyPagoSignatureAsync(signature.First()!, payload, nonce.First()!);
    }
    catch (Exception ex)
    {
        // La firma no es válida. Descartar la petición.
        Console.WriteLine($"Error al validar la firma: {ex.Message}");
        return Results.Unauthorized();
    }

    // 3. La firma es válida. Procesar la notificación.
    Console.WriteLine("Firma válida. Procesando notificación...");
    // Aquí iría la lógica para procesar el payload de la notificación.
    // Por ejemplo, deserializar el JSON del payload y actualizar la base de datos.
    
    return Results.Ok(new { message = "Firma válida y notificación recibida" });
});
```

#### 2. Validación de la Firma

Esta clase de servicio contiene la lógica de validación.

```csharp
using System.Security.Cryptography;
using System.Text;

public interface ISignatureValidationService
{
    Task ValidateSyPagoSignatureAsync(string signatureB64, string payload, string nonce);
}

public class SignatureValidationService : ISignatureValidationService
{
    public async Task ValidateSyPagoSignatureAsync(string signatureB64, string payload, string nonce)
    {
        // --- Obtención de Secretos ---
        // El `operationSecret` DEBE ser recuperado de su base de datos.
        // Lo guardó cuando inició la transacción con SyPago y recibió el `transaction_id`.
        string operationSecret = "9f4aaf08-8d04-4007-a097-c0e95eddad5e"; // ¡EJEMPLO! Reemplazar con su lógica de obtención.

        // La `publicKeyPEM` DEBE ser obtenida del endpoint GET /api/v1/user/key
        // usando su token JWT. Es recomendable cachear esta clave.
        string publicKeyPEM = @"-----BEGIN PUBLIC KEY-----
MFkwEwYHKoZIzj0CAQYIKoZIzj0DAQcDQgAE9hXXl4g886loHmI10dLrJWFHEB8r
cyqD1hBdIM3ekQkb5YTpOShAu+7xk0fyL/0IBjLEKwSd7rsKrYGtIgJj0w==
-----END PUBLIC KEY-----"; // ¡EJEMPLO! Reemplazar con la clave real obtenida.

        // --- Proceso de Verificación ---

        // 1. Reconstruir el mensaje a verificar
        string stringToVerify = $"{payload}.{nonce}.{operationSecret}";
        byte[] dataToVerify = Encoding.UTF8.GetBytes(stringToVerify);

        // 2. Calcular el hash SHA-256 del mensaje
        byte[] hash = SHA256.HashData(dataToVerify);
        
        // 3. Decodificar la firma de Base64
        byte[] signatureBytes = Convert.FromBase64String(signatureB64);

        // 4. Cargar la clave pública y verificar la firma
        using (var ecdsa = ECDsa.Create())
        {
            ecdsa.ImportFromPem(publicKeyPEM);

            // 5. Verificar la firma del hash usando el formato ASN.1 DER
            // DSASignatureFormat.Rfc3279DerSequence es el formato que usa SyPago y es el default para VerifyHash
            bool isSignatureValid = ecdsa.VerifyHash(hash, signatureBytes, DSASignatureFormat.Rfc3279DerSequence);

            if (!isSignatureValid)
            {
                throw new CryptographicException("La firma no es válida");
            }
        }
        
        // La firma es válida
    }
}
```

### JavaScript (Node.js)

El ejemplo en Node.js utiliza el framework [Express](https://expressjs.com/) para el servidor y el módulo nativo `crypto` para la validación.

#### 1. Recepción de la Notificación (Express)

Es crucial usar un middleware como `express.raw` para asegurarse de que el cuerpo de la solicitud se lee como un buffer sin ser modificado.

```javascript
const express = require('express');
const { validateSyPagoSignature } = require('./signature-validator');

const app = express();

// Middleware para leer el body como un buffer crudo para las rutas de webhook
app.use('/webhook-sypago', express.raw({ type: 'application/json' }));

app.post('/webhook-sypago', async (req, res) => {
    try {
        // 1. Extraer los componentes de la notificación
        const signature = req.headers['x-signature'];
        if (!signature) {
            return res.status(400).send('Cabecera X-Signature es requerida');
        }
        
        const nonce = req.headers['x-signature-nonce'];
        if (!nonce) {
            return res.status(400).send('Cabecera X-Signature-Nonce es requerida');
        }

        // El payload es el buffer crudo del body (req.body)
        const payload = req.body;

        // 2. Validar la firma
        await validateSyPagoSignature(signature, payload, nonce);
        
        // 3. La firma es válida. Procesar la notificación.
        console.log("Firma válida. Procesando notificación...");
        // Aquí iría la lógica para procesar el payload.
        // const notificationData = JSON.parse(payload.toString('utf8'));

        res.status(200).json({ message: 'Firma válida y notificación recibida' });

    } catch (error) {
        // La firma no es válida. Descartar la petición.
        console.error(`Error al validar la firma: ${error.message}`);
        res.status(401).send(error.message);
    }
});

// ... iniciar el servidor
// const PORT = 3000;
// app.listen(PORT, () => console.log(`Servidor escuchando en el puerto ${PORT}`));
```

#### 2. Validación de la Firma

Este módulo exporta la función de validación.

```javascript
const crypto = require('crypto');

async function validateSyPagoSignature(signatureB64, payloadBuffer, nonce) {
    // --- Obtención de Secretos ---
    // El `operationSecret` DEBE ser recuperado de su base de datos.
    // Lo guardó cuando inició la transacción con SyPago y recibió el `transaction_id`.
    const operationSecret = '9f4aaf08-8d04-4007-a097-c0e95eddad5e'; // ¡EJEMPLO! Reemplazar con su lógica de obtención.

    // La `publicKeyPEM` DEBE ser obtenida del endpoint GET /api/v1/user/key
    // usando su token JWT. Es recomendable cachear esta clave.
    const publicKeyPEM = `-----BEGIN PUBLIC KEY-----
MFkwEwYHKoZIzj0CAQYIKoZIzj0DAQcDQgAE9hXXl4g886loHmI10dLrJWFHEB8r
cyqD1hBdIM3ekQkb5YTpOShAu+7xk0fyL/0IBjLEKwSd7rsKrYGtIgJj0w==
-----END PUBLIC KEY-----`; // ¡EJEMPLO! Reemplazar con la clave real obtenida.

    // --- Proceso de Verificación ---
    
    // 1. Reconstruir el mensaje a verificar (payload.nonce.operationSecret)
    // Es importante usar el buffer del payload directamente
    const stringToVerify = Buffer.concat([
        payloadBuffer,
        Buffer.from(`.${nonce}.${operationSecret}`)
    ]);

    // 2. Crear un objeto de verificación con el algoritmo SHA-256
    const verify = crypto.createVerify('SHA256');

    // 3. Cargar el mensaje en el objeto
    verify.update(stringToVerify);
    verify.end();

    // 4. Verificar la firma usando la clave pública y la firma en Base64
    // El formato de la firma es ASN.1 DER, que es el estándar que `crypto` espera.
    const isSignatureValid = verify.verify(publicKeyPEM, signatureB64, 'base64');

    if (!isSignatureValid) {
        throw new Error('La firma no es válida');
    }

    // La firma es válida
    return true;
}

module.exports = { validateSyPagoSignature };
```

### Java

El ejemplo en Java utiliza el framework [Spring Boot](https://spring.io/projects/spring-boot) para el endpoint y las clases nativas de `java.security` para la validación.

#### 1. Recepción de la Notificación (Spring Boot Controller)

```java
package com.example.sypagovalidator;

import org.springframework.http.ResponseEntity;
import org.springframework.web.bind.annotation.PostMapping;
import org.springframework.web.bind.annotation.RequestBody;
import org.springframework.web.bind.annotation.RequestHeader;
import org.springframework.web.bind.annotation.RestController;

import java.util.Map;

@RestController
public class WebhookController {

    private final SignatureValidationService validationService;

    public WebhookController(SignatureValidationService validationService) {
        this.validationService = validationService;
    }

    @PostMapping("/webhook-sypago")
    public ResponseEntity<Map<String, String>> handleWebhook(
            @RequestBody String payload,
            @RequestHeader("X-Signature") String signature,
            @RequestHeader("X-Signature-Nonce") String nonce) {
        
        try {
            // 2. Validar la firma
            validationService.validateSyPagoSignature(signature, payload, nonce);
            
            // 3. La firma es válida. Procesar la notificación.
            System.out.println("Firma válida. Procesando notificación...");
            // Aquí iría la lógica para procesar el payload.

            return ResponseEntity.ok(Map.of("message", "Firma válida y notificación recibida"));

        } catch (Exception e) {
            // La firma no es válida. Descartar la petición.
            System.err.println("Error al validar la firma: " + e.getMessage());
            return ResponseEntity.status(401).build();
        }
    }
}
```

#### 2. Validación de la Firma

```java
package com.example.sypagovalidator;

import org.springframework.stereotype.Service;

import java.nio.charset.StandardCharsets;
import java.security.KeyFactory;
import java.security.PublicKey;
import java.security.Signature;
import java.security.spec.X509EncodedKeySpec;
import java.util.Base64;

@Service
public class SignatureValidationService {

    public void validateSyPagoSignature(String signatureB64, String payload, String nonce) throws Exception {
        // --- Obtención de Secretos ---
        // El `operationSecret` DEBE ser recuperado de su base de datos.
        // Lo guardó cuando inició la transacción con SyPago.
        String operationSecret = "9f4aaf08-8d04-4007-a097-c0e95eddad5e"; // ¡EJEMPLO! Reemplazar.

        // La `publicKeyPEM` DEBE ser obtenida del endpoint GET /api/v1/user/key
        // usando su token JWT. Es recomendable cachear esta clave.
        String publicKeyPEM = "-----BEGIN PUBLIC KEY-----\n" +
                "MFkwEwYHKoZIzj0CAQYIKoZIzj0DAQcDQgAE9hXXl4g886loHmI10dLrJWFHEB8r\n" +
                "cyqD1hBdIM3ekQkb5YTpOShAu+7xk0fyL/0IBjLEKwSd7rsKrYGtIgJj0w==\n" +
                "-----END PUBLIC KEY-----"; // ¡EJEMPLO! Reemplazar.

        // --- Proceso de Verificación ---
        
        // 1. Reconstruir el mensaje
        String stringToVerify = payload + "." + nonce + "." + operationSecret;
        byte[] dataToVerify = stringToVerify.getBytes(StandardCharsets.UTF_8);

        // 2. Limpiar y decodificar la clave pública PEM
        String publicKeyBase64 = publicKeyPEM
                .replace("-----BEGIN PUBLIC KEY-----", "")
                .replaceAll("\\s+", "") // Usar "\\s+" para remover todos los espacios en blanco, incluyendo saltos de línea
                .replace("-----END PUBLIC KEY-----", "");
        
        byte[] keyBytes = Base64.getDecoder().decode(publicKeyBase64);
        X509EncodedKeySpec spec = new X509EncodedKeySpec(keyBytes);
        KeyFactory kf = KeyFactory.getInstance("EC");
        PublicKey publicKey = kf.generatePublic(spec);

        // 3. Decodificar la firma de Base64
        byte[] signatureBytes = Base64.getDecoder().decode(signatureB64);
        
        // 4. Inicializar el objeto Signature y verificar
        // "SHA256withECDSA" es el algoritmo estándar que manejará el hash y la verificación ASN.1
        Signature sig = Signature.getInstance("SHA256withECDSA");
        sig.initVerify(publicKey);
        sig.update(dataToVerify);
        
        boolean isSignatureValid = sig.verify(signatureBytes);
        
        if (!isSignatureValid) {
            throw new SecurityException("La firma no es válida");
        }
        
        // La firma es válida
    }
}
```

### Python

El ejemplo en Python utiliza el framework [Flask](https://flask.palletsprojects.com/) y la librería [cryptography](https://cryptography.io/).

#### 1. Recepción de la Notificación (Flask)

```python
from flask import Flask, request, jsonify
from signature_validator import validate_sypago_signature, SignatureValidationError

app = Flask(__name__)

@app.route('/webhook-sypago', methods=['POST'])
def webhook_sypago():
    try:
        # 1. Extraer los componentes de la notificación
        signature = request.headers.get('X-Signature')
        if not signature:
            return "Cabecera X-Signature es requerida", 400

        nonce = request.headers.get('X-Signature-Nonce')
        if not nonce:
            return "Cabecera X-Signature-Nonce es requerida", 400

        # Importante: obtener el cuerpo crudo de la solicitud
        payload = request.get_data()

        # 2. Validar la firma
        validate_sypago_signature(signature, payload, nonce)

        # 3. La firma es válida. Procesar la notificación.
        print("Firma válida. Procesando notificación...")
        # notification_data = request.get_json()
        # ... lógica de negocio ...

        return jsonify({"message": "Firma válida y notificación recibida"}), 200

    except SignatureValidationError as e:
        print(f"Error al validar la firma: {e}")
        return str(e), 401
    except Exception as e:
        print(f"Error inesperado: {e}")
        return "Error interno del servidor", 500

# if __name__ == '__main__':
#     app.run(port=3000)
```

#### 2. Validación de la Firma

Asegúrese de instalar la librería `cryptography`: `pip install cryptography`.

```python
import base64
from cryptography.exceptions import InvalidSignature
from cryptography.hazmat.primitives import hashes
from cryptography.hazmat.primitives.asymmetric import ec
from cryptography.hazmat.primitives.serialization import load_pem_public_key

class SignatureValidationError(Exception):
    pass

def validate_sypago_signature(signature_b64: str, payload: bytes, nonce: str):
    # --- Obtención de Secretos ---
    # El `operation_secret` DEBE ser recuperado de su base de datos.
    # Lo guardó cuando inició la transacción con SyPago.
    operation_secret = "9f4aaf08-8d04-4007-a097-c0e95eddad5e" # ¡EJEMPLO!

    # La `public_key_pem` DEBE ser obtenida del endpoint GET /api/v1/user/key
    # usando su token JWT. Es recomendable cachear esta clave.
    public_key_pem = b"""-----BEGIN PUBLIC KEY-----
MFkwEwYHKoZIzj0CAQYIKoZIzj0DAQcDQgAE9hXXl4g886loHmI10dLrJWFHEB8r
cyqD1hBdIM3ekQkb5YTpOShAu+7xk0fyL/0IBjLEKwSd7rsKrYGtIgJj0w==
-----END PUBLIC KEY-----""" # ¡EJEMPLO!

    # --- Proceso de Verificación ---
    
    # 1. Reconstruir el mensaje a verificar
    # El payload ya está en bytes, lo cual es ideal.
    string_to_verify = b'.'.join([
        payload,
        nonce.encode('utf-8'),
        operation_secret.encode('utf-8')
    ])

    try:
        # 2. Cargar la clave pública PEM
        public_key = load_pem_public_key(public_key_pem, backend=None)

        # 3. Decodificar la firma Base64
        signature_bytes = base64.b64decode(signature_b64)
        
        # 4. Verificar la firma
        # La librería verificará el hash (SHA-256) contra la firma (formato ASN.1 DER)
        public_key.verify(
            signature_bytes,
            string_to_verify,
            ec.ECDSA(hashes.SHA256())
        )
        
        # La firma es válida, la función no retorna nada si es exitosa.

    except InvalidSignature:
        raise SignatureValidationError("La firma no es válida")
    except Exception as e:
        # Capturar otros errores (p. ej., mal formato de clave)
        raise SignatureValidationError(f"Error en el proceso de validación: {e}")
``` 