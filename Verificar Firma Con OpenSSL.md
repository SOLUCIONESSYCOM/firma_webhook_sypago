## Verificación Manual Paso a Paso De Firma

Si desea verificar manualmente una firma, siga estos pasos:
- Es recomendado que antes de realizar esta verificacion primero haya valido seguido su llave publica siguiendo la guia de "Verificar Clave Publica"

Recuerde que al guardar su llave publica en un archivo .pem debe respetar los correctos saltos de linea
Para mas informacion puede ver esto en el archivo Validar Clave Publica

1. **Guardar el mensaje concatenado**:
   ```bash
   echo -n "PAYLOAD.NONCE.OPERATION_SECRET" > message.txt
   ```
   (Reemplace los valores con los datos reales)

2. **Calcular el hash SHA-256**:
   ```bash
   openssl dgst -sha256 -binary message.txt > message.hash
   ```

3. **Decodificar la firma Base64**:
   ```bash
   echo -n "FIRMA_BASE64" | base64 -d > signature.bin
   ```
   (Reemplace FIRMA_BASE64 con el valor de la cabecera X-Signature)

4. **Verificar la firma**:
   ```bash
   openssl pkeyutl -verify -pubin -inkey sypago_public.pem -in message.hash -sigfile signature.bin -pkeyopt digest:sha256
   ```

5. **Interpretar el resultado**:
   - `Signature Verified Successfully` → La firma es válida
   - `Signature Verification Failure` → La firma es inválida

## Script Automatizado para Verificación

A continuación se incluye un script completo que automatiza el proceso de verificación. Puede guardarlo como `verify_signature.sh` y ejecutarlo con sus datos:

```bash
#!/bin/bash
# verify_signature.sh - Script para verificar firmas de SyPago

# ===== VARIABLES (Modifique estos valores) =====
# Clave pública (asegúrese de que este archivo exista y tenga el formato correcto)
PUBLIC_KEY_FILE="sypago_public.pem"

# Payload (el cuerpo JSON exacto recibido)
PAYLOAD='{"transaction_id":"EF806AFEE804","status":"completed"}'

# Firma (valor de la cabecera X-Signature)
SIGNATURE_B64="IyoN8/vNZHGHBpwXjmMyeWT/mXpL5h0lBH4AS2/Bp5hwa1Iu7m7Vtj0MfCWxMEetvFbgIh4s92ftopgpVxlGlA=="

# Nonce (valor de la cabecera X-Signature-Nonce)
NONCE="1649267348123"

# Secreto de operación (guardado previamente para esta transacción)
OPERATION_SECRET="1bf38a9d-3205-4b8e-b8d1-ce95466ceef2"

# ===== VERIFICACIÓN =====
echo "=== Verificación de Firma SyPago con OpenSSL ==="
echo "Payload: $PAYLOAD"
echo "Nonce: $NONCE"
echo "Signature: $SIGNATURE_B64"
echo ""

# 1. Crear mensaje para verificar (payload.nonce.operationSecret)
echo -n "${PAYLOAD}.${NONCE}.${OPERATION_SECRET}" > message.txt
echo "✓ Mensaje concatenado creado."

# 2. Calcular hash SHA-256
openssl dgst -sha256 -binary message.txt > message.hash
echo "✓ Hash SHA-256 calculado."

# 3. Decodificar firma Base64
echo -n "$SIGNATURE_B64" | base64 -d > signature.bin
echo "✓ Firma decodificada."

# 4. Verificar firma - SALIDA RAW COMPLETA
echo ""
echo "======== INICIO SALIDA RAW DE OPENSSL ========"
# Mostrar el comando que se va a ejecutar
echo "$ openssl pkeyutl -verify -pubin -inkey $PUBLIC_KEY_FILE -in message.hash -sigfile signature.bin -pkeyopt digest:sha256"
echo ""

# Ejecutar el comando y capturar tanto la salida como el código de retorno
openssl pkeyutl -verify -pubin -inkey "$PUBLIC_KEY_FILE" -in message.hash -sigfile signature.bin -pkeyopt digest:sha256
OPENSSL_RESULT=$?
echo "======== FIN SALIDA RAW DE OPENSSL ========"
echo ""

# Interpretar el resultado
if [ $OPENSSL_RESULT -eq 0 ]; then
  echo "✅ Firma verificada correctamente - La notificación es auténtica"
  RESULT=0
else
  echo "❌ Verificación de firma fallida - La notificación podría ser fraudulenta"
  RESULT=1
fi

# 5. Limpieza (opcional)
rm -f message.txt message.hash signature.bin
echo "✓ Archivos temporales eliminados."

exit $RESULT
```

### Uso del Script

1. **Guardar el script**:
   ```bash
   chmod +x verify_signature.sh
   ```

2. **Modificar las variables** al inicio del script con los datos reales de la notificación.

3. **Ejecutar el script**:
   ```bash
   ./verify_signature.sh
   ```

## Solución de Problemas

1. **Error "Could not read public key"**: Verifique que el archivo de clave pública tiene el formato correcto con los saltos de línea adecuados.

2. **Error "Signature Verification Failure"**: Posibles causas:
   - El payload no es exactamente el mismo que se firmó (incluso espacios o caracteres diferentes)
   - El nonce es incorrecto
   - El operation_secret no corresponde a la transacción
   - La firma ha sido manipulada

3. **Error "unknown option -pkeyopt"**: Su versión de OpenSSL es demasiado antigua. Actualice a OpenSSL 1.1.1 o superior.

## Conclusión

La verificación de firmas mediante OpenSSL proporciona una manera independiente y transparente de validar la autenticidad de las notificaciones de SyPago. Este proceso garantiza que las notificaciones provienen de SyPago y que no han sido alteradas durante la transmisión.