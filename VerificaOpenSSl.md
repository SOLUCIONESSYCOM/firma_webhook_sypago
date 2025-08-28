# Verificación de Firma SyPago mediante OpenSSL

Este documento detalla los procedimientos para validar tanto la clave pública como la firma digital de las notificaciones de SyPago utilizando OpenSSL, una herramienta de línea de comandos ampliamente disponible para criptografía.

## Análisis del Proceso de Firma SyPago

El sistema de notificaciones de SyPago utiliza:

1. **Algoritmos**: SHA-256 para el hash y ECDSA con curva P-256 para la firma digital
2. **Componentes**: 
   - Payload (cuerpo JSON de la notificación)
   - Nonce (basado en timestamp, enviado en cabecera X-Signature-Nonce)
   - Operation Secret (UUID generado al inicio de la transacción)
   - Firma digital (enviada en cabecera X-Signature)

3. **Proceso de Verificación**:
   - Concatenar: `stringToVerify = "{payload}.{nonce}.{operationSecret}"`
   - Calcular hash SHA-256 de esta cadena
   - Verificar firma ECDSA contra este hash usando la clave pública

## 1. Validación de la Clave Pública con OpenSSL

La clave pública obtenida del endpoint `/api/v1/user/key` debe ser validada para asegurar que es una clave ECDSA válida en formato PEM.

### Procedimiento:

1. **Guardar la clave pública en un archivo**

   Guarde la clave pública recibida en un archivo llamado `sypago_public.pem`:

   ```bash
   cat > sypago_public.pem << 'EOL'
   -----BEGIN PUBLIC KEY-----
   MFkwEwYHKoZIzj0CAQYIKoZIzj0DAQcDQgAE9hXXl4g886loHmI10dLrJWFHEB8r
   cyqD1hBdIM3ekQkb5YTpOShAu+7xk0fyL/0IBjLEKwSd7rsKrYGtIgJj0w==
   -----END PUBLIC KEY-----
   EOL
   ```

2. **Verificar el formato de la clave**

   ```bash
   openssl ec -pubin -in sypago_public.pem -noout -text
   ```

   Este comando mostrará los detalles de la clave pública si es válida. Debería ver información sobre:
   - El tipo de curva (debería ser prime256v1/P-256)
   - Las coordenadas de la clave pública (x, y)
   
   Si la clave es inválida, OpenSSL mostrará un error.

3. **Verificar que la clave sea ECDSA**

   ```bash
   openssl asn1parse -in sypago_public.pem -dump
   ```

   Este comando mostrará la estructura ASN.1 de la clave. Verifique que aparezca el OID `1.2.840.10045.2.1` que corresponde a ECDSA.

## 2. Validación de Firma con OpenSSL

Para validar una firma ECDSA recibida en las notificaciones de webhook, necesitamos:

1. La clave pública (ya validada)
2. El mensaje original firmado 
3. La firma digital recibida

### Procedimiento:

1. **Preparar el mensaje a verificar**

   Cree un archivo que contenga el mensaje concatenado:

   ```bash
   # Suponiendo que tenemos los componentes:
   # $PAYLOAD: El cuerpo JSON de la notificación
   # $NONCE: El valor de la cabecera X-Signature-Nonce
   # $OPERATION_SECRET: El secreto guardado de la operación

   echo -n "$PAYLOAD.$NONCE.$OPERATION_SECRET" > message.txt
   ```

2. **Calcular el hash SHA-256 del mensaje**

   ```bash
   openssl dgst -sha256 -binary message.txt > message.hash
   ```

3. **Decodificar la firma de Base64**

   La firma viene codificada en Base64 en la cabecera `X-Signature`:

   ```bash
   echo -n "$SIGNATURE_B64" | base64 -d > signature.bin
   ```

4. **Verificar la firma**

   ```bash
   openssl pkeyutl -verify -pubin -inkey sypago_public.pem -in message.hash -sigfile signature.bin -pkeyopt digest:sha256
   ```

   Si la verificación es exitosa, OpenSSL mostrará:
   ```
   Signature Verified Successfully
   ```

   Si falla, mostrará:
   ```
   Signature Verification Failure
   ```

### Script Completo para Verificación de Firma

Aquí hay un script de Bash que automatiza el proceso completo:

```bash
#!/bin/bash

# Parámetros de entrada
PUBLIC_KEY_FILE="sypago_public.pem"
PAYLOAD="$1"            # El cuerpo JSON exacto recibido
SIGNATURE_B64="$2"      # Valor de X-Signature 
NONCE="$3"              # Valor de X-Signature-Nonce
OPERATION_SECRET="$4"   # Secreto almacenado para la transacción

# Verificar que todos los parámetros estén presentes
if [ -z "$PAYLOAD" ] || [ -z "$SIGNATURE_B64" ] || [ -z "$NONCE" ] || [ -z "$OPERATION_SECRET" ]; then
  echo "Error: Todos los parámetros son requeridos"
  echo "Uso: $0 <payload> <signature_base64> <nonce> <operation_secret>"
  exit 1
fi

# Crear mensaje para verificar (payload.nonce.operationSecret)
echo -n "${PAYLOAD}.${NONCE}.${OPERATION_SECRET}" > message.txt

# Calcular hash SHA-256
openssl dgst -sha256 -binary message.txt > message.hash

# Decodificar firma Base64
echo -n "$SIGNATURE_B64" | base64 -d > signature.bin

# Verificar firma
echo "Verificando firma..."
if openssl pkeyutl -verify -pubin -inkey "$PUBLIC_KEY_FILE" -in message.hash -sigfile signature.bin -pkeyopt digest:sha256; then
  echo "✅ Firma verificada correctamente - La notificación es auténtica"
  exit 0
else
  echo "❌ Verificación de firma fallida - La notificación podría ser fraudulenta"
  exit 1
fi
```

## Consideraciones Importantes

1. **Formato de la Firma**: OpenSSL espera la firma en formato ASN.1 DER, que es el estándar que SyPago utiliza.

2. **Mensaje Exacto**: Es crucial que el payload utilizado para verificar sea exactamente el mismo texto recibido en el cuerpo de la solicitud, sin ninguna modificación o re-serialización.

3. **Versión de OpenSSL**: Este procedimiento ha sido probado con OpenSSL 1.1.1 y superiores. Las versiones más antiguas podrían requerir comandos ligeramente diferentes.

4. **Manejo de Errores**: En un entorno de producción, implemente un manejo adecuado de errores y registre los intentos de verificación fallidos.

## Recursos Adicionales

- [Documentación oficial de OpenSSL](https://www.openssl.org/docs/)
- [Manual de OpenSSL para verificación de firmas](https://www.openssl.org/docs/man1.1.1/man1/openssl-pkeyutl.html)
