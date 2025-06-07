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
	operationSecret := "1bf38a9d-3205-4b8e-b8d1-ce95466ceef2" // ¡EJEMPLO!

	// La `publicKeyPEM` DEBE ser obtenida del endpoint GET /api/v1/user/key
	// y cacheada.
	publicKeyPEM := `-----BEGIN PUBLIC KEY-----
MFkwEwYHKoZIzj0CAQYIKoZIzj0DAQcDQgAE9hXXl4g886loHmI10dLrJWFHEB8r
cyqD1hBdIM3ekQkb5YTpOShAu+7xk0fyL/0IBjLEKwSd7rsKrYGtIgJj0w==
-----END PUBLIC KEY-----` // ¡EJEMPLO!

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

	// 6. Verificar la firma
	isSignatureValid := ecdsa.VerifyASN1(publicKey, hash[:], signatureBytes)
	if !isSignatureValid {
		return fmt.Errorf("la firma no es válida")
	}

	return nil
}
