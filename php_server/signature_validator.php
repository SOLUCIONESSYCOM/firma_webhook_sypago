<?php
/**
 * SyPago Signature Validator
 * 
 * Este archivo contiene la lógica para validar firmas digitales de SyPago 
 * utilizando ECDSA con SHA-256.
 */

class SignatureValidationException extends Exception {}

class SignatureValidator {
    /**
     * Valida la firma de una notificación de SyPago.
     *
     * @param string $signatureB64 La firma en formato Base64 recibida en la cabecera X-Signature.
     * @param string $payload El cuerpo crudo JSON de la notificación.
     * @param string $nonce El nonce recibido en la cabecera X-Signature-Nonce.
     * @throws SignatureValidationException Si la firma no es válida.
     */
    public function validateSyPagoSignature(string $signatureB64, string $payload, string $nonce): void {
        // --- Obtención de Secretos ---
        // El `operationSecret` DEBE ser recuperado de su base de datos.
        // Lo guardó cuando inició la transacción con SyPago.
        $operationSecret = "1bf38a9d-3205-4b8e-b8d1-ce95466ceef2"; // ¡EJEMPLO! Reemplazar con el valor real.

        // La `publicKeyPEM` DEBE ser obtenida del endpoint GET /api/v1/user/key
        // usando su token JWT. Es recomendable cachear esta clave.
        $publicKeyPEM = "-----BEGIN PUBLIC KEY-----
MFkwEwYHKoZIzj0CAQYIKoZIzj0DAQcDQgAE9hXXl4g886loHmI10dLrJWFHEB8r
cyqD1hBdIM3ekQkb5YTpOShAu+7xk0fyL/0IBjLEKwSd7rsKrYGtIgJj0w==
-----END PUBLIC KEY-----"; // ¡EJEMPLO! Reemplazar con la clave real obtenida.

        // --- Proceso de Verificación ---

        try {
            // 1. Reconstruir el mensaje a verificar (payload.nonce.operationSecret)
            $stringToVerify = $payload . '.' . $nonce . '.' . $operationSecret;
            
            // 2. Cargar la clave pública
            $publicKey = openssl_pkey_get_public($publicKeyPEM);
            if ($publicKey === false) {
                throw new SignatureValidationException("Error al cargar la clave pública: " . openssl_error_string());
            }
            
            // 3. Decodificar la firma de Base64
            $signatureBytes = base64_decode($signatureB64, true);
            if ($signatureBytes === false) {
                throw new SignatureValidationException("Error al decodificar la firma desde Base64");
            }
            
            // 4. Calcular hash SHA-256 y verificar la firma ECDSA
            // OpenSSL en PHP maneja automáticamente el hash SHA-256 y la verificación ASN.1 DER
            $isSignatureValid = openssl_verify(
                $stringToVerify, 
                $signatureBytes, 
                $publicKey,
                OPENSSL_ALGO_SHA256
            );
            
            // Liberar recursos
            openssl_free_key($publicKey);
            
            // 5. Evaluar el resultado
            if ($isSignatureValid === 1) {
                // La firma es válida
                return;
            } elseif ($isSignatureValid === 0) {
                throw new SignatureValidationException("La firma no es válida");
            } else {
                throw new SignatureValidationException("Error en la verificación: " . openssl_error_string());
            }
        } catch (SignatureValidationException $e) {
            throw $e;
        } catch (Exception $e) {
            throw new SignatureValidationException("Error inesperado: " . $e->getMessage());
        }
    }
}
