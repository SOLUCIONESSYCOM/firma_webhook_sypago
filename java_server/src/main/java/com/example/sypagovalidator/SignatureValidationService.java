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
        String operationSecret = "1bf38a9d-3205-4b8e-b8d1-ce95466ceef2";

        String publicKeyPEM = "-----BEGIN PUBLIC KEY-----\n" +
                "MFkwEwYHKoZIzj0CAQYIKoZIzj0DAQcDQgAE9hXXl4g886loHmI10dLrJWFHEB8r\n" +
                "cyqD1hBdIM3ekQkb5YTpOShAu+7xk0fyL/0IBjLEKwSd7rsKrYGtIgJj0w==\n" +
                "-----END PUBLIC KEY-----";

        // --- Proceso de Verificación ---
        
        // 1. Reconstruir el mensaje
        String stringToVerify = payload + "." + nonce + "." + operationSecret;
        byte[] dataToVerify = stringToVerify.getBytes(StandardCharsets.UTF_8);

        // 2. Limpiar y decodificar la clave pública PEM
        String publicKeyBase64 = publicKeyPEM
                .replace("-----BEGIN PUBLIC KEY-----", "")
                .replaceAll("\\s+", "")
                .replace("-----END PUBLIC KEY-----", "");
        
        byte[] keyBytes = Base64.getDecoder().decode(publicKeyBase64);
        X509EncodedKeySpec spec = new X509EncodedKeySpec(keyBytes);
        KeyFactory kf = KeyFactory.getInstance("EC");
        PublicKey publicKey = kf.generatePublic(spec);

        // 3. Decodificar la firma de Base64
        byte[] signatureBytes = Base64.getDecoder().decode(signatureB64);
        
        // 4. Inicializar el objeto Signature y verificar
        Signature sig = Signature.getInstance("SHA256withECDSA");
        sig.initVerify(publicKey);
        sig.update(dataToVerify);
        
        boolean isSignatureValid = sig.verify(signatureBytes);
        
        if (!isSignatureValid) {
            throw new SecurityException("La firma no es válida");
        }
    }
} 