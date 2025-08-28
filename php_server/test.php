<?php
/**
 * Script para probar el validador de firma de SyPago
 * 
 * Este script demuestra cómo utilizar el validador con datos de prueba
 */

require_once 'signature_validator.php';

echo "=== Prueba de Validación de Firma SyPago ===" . PHP_EOL . PHP_EOL;

// Datos de prueba (reemplazar con ejemplos reales para pruebas)
$payload = '{"transaction_id":"EF806AFEE804","status":"completed"}';
$nonce = '1649267348123'; // Timestamp en milisegundos
$operationSecret = '1bf38a9d-3205-4b8e-b8d1-ce95466ceef2';

// Esta firma es un ejemplo y probablemente no validará con los datos anteriores.
// Para una prueba real, necesitarías una firma generada con la clave privada correcta.
$signature = 'IyoN8/vNZHGHBpwXjmMyeWT/mXpL5h0lBH4AS2/Bp5hwa1Iu7m7Vtj0MfCWxMEetvFbgIh4s92ftopgpVxlGlA==';

echo "Payload: $payload" . PHP_EOL;
echo "Nonce: $nonce" . PHP_EOL;
echo "Operation Secret: $operationSecret" . PHP_EOL;
echo "Signature: $signature" . PHP_EOL . PHP_EOL;

$validator = new SignatureValidator();

try {
    echo "Ejecutando validación..." . PHP_EOL;
    // Nota: Para una prueba real, es posible que necesites modificar 
    // la clase SignatureValidator para aceptar el operationSecret como parámetro
    $validator->validateSyPagoSignature($signature, $payload, $nonce);
    echo "✅ Firma válida! La notificación es auténtica." . PHP_EOL;
} catch (SignatureValidationException $e) {
    echo "❌ Error de validación: " . $e->getMessage() . PHP_EOL;
} catch (Exception $e) {
    echo "❌ Error inesperado: " . $e->getMessage() . PHP_EOL;
}

echo PHP_EOL . "=== Fin de la prueba ===" . PHP_EOL;
