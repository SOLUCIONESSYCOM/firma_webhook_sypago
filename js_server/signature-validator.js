const crypto = require('crypto');

/**
 * Valida la firma de una notificación de SyPago.
 * @param {string} signatureB64 Firma en formato Base64 recibida en el header X-Signature.
 * @param {Buffer} payloadBuffer El cuerpo crudo (raw) de la solicitud.
 * @param {string} nonce El nonce recibido en el header X-Signature-Nonce.
 * @returns {boolean}
 * @throws {Error} Si la firma no es válida.
 */
function validateSyPagoSignature(signatureB64, payloadBuffer, nonce) {
    // --- Secretos ---
    // El `operationSecret` debería ser recuperado de su base de datos.
    const operationSecret = '1bf38a9d-3205-4b8e-b8d1-ce95466ceef2';

    // La `publicKeyPEM` debería ser obtenida del endpoint de SyPago y cacheada.
    const publicKeyPEM = `-----BEGIN PUBLIC KEY-----
MFkwEwYHKoZIzj0CAQYIKoZIzj0DAQcDQgAE9hXXl4g886loHmI10dLrJWFHEB8r
cyqD1hBdIM3ekQkb5YTpOShAu+7xk0fyL/0IBjLEKwSd7rsKrYGtIgJj0w==
-----END PUBLIC KEY-----`;

    // --- Proceso de Verificación ---

    // 1. Reconstruir el mensaje a verificar (payload.nonce.operationSecret)
    // Se concatena el buffer del payload con los demás datos para evitar problemas de codificación.
    const stringToVerify = Buffer.concat([
        payloadBuffer,
        Buffer.from(`.${nonce}.${operationSecret}`)
    ]);

    // 2. Crear un objeto de verificación con el algoritmo SHA-256
    const verify = crypto.createVerify('SHA256');

    // 3. Cargar el mensaje en el objeto
    verify.update(stringToVerify);
    verify.end();

    // 4. Verificar la firma usando la clave pública y la firma en Base64.
    // El formato esperado por crypto.verify (ASN.1 DER) es el que se usa.
    const isSignatureValid = verify.verify(publicKeyPEM, signatureB64, 'base64');

    if (!isSignatureValid) {
        throw new Error('La firma no es válida');
    }

    return true;
}

module.exports = { validateSyPagoSignature };