#!/bin/bash
# verify_signature.sh - Script para verificar firmas de SyPago

# ===== VARIABLES (Modifique estos valores) =====
# Clave pública (asegúrese de que este archivo exista y tenga el formato correcto)
PUBLIC_KEY_FILE="sypago_public.pem"

# Payload (el cuerpo JSON exacto recibido)
PAYLOAD='{"internal_id":"40EF58974F33","transaction_id":"0C18C7B6747B","ref_ibp":"00012025082813214524420317","group_id":"1EB1CA4382A7","operation_date":"2025-08-28T09:21:45.233000","amount":{"type":"NONE","amt":2.00,"pay_amt":0,"currency":"USD","rate":145.74530000,"use_day_rate":false},"receiving_user":{"name":"Gussie Jacobs","document_info":{"type":"V","number":"12345678"},"account":{"bank_code":"0102","type":"CELE","number":"4121234567"}},"status":"RJCT","rejected_code":"AB01","expiration":0}'

# Firma (valor de la cabecera X-Signature)
SIGNATURE_B64="MEYCIQDrSowgiex+sBgoq1+1Sf6x9u7sAu91gWu82yNHd9rlhQIhALHCdQyOs9WEtoa1/TDTSg5TQdr/rSZKmj6AFc12iQ/s"

# Nonce (valor de la cabecera X-Signature-Nonce)
NONCE="1756387305379"

# Secreto de operación (guardado previamente para esta transacción)
OPERATION_SECRET="748ed1f8-6123-44e1-90c3-c37fad0ff69e"

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
