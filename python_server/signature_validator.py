import base64
from cryptography.exceptions import InvalidSignature
from cryptography.hazmat.primitives import hashes
from cryptography.hazmat.primitives.asymmetric import ec
from cryptography.hazmat.primitives.serialization import load_pem_public_key

class SignatureValidationError(Exception):
    pass

def validate_sypago_signature(signature_b64: str, payload: bytes, nonce: str):
    """
    Valida la firma de una notificación de SyPago.
    """
    # --- Secretos ---
    # El `operation_secret` debería ser recuperado de una base de datos o un sistema de caché.
    # Para este ejemplo, usamos el valor proporcionado.
    operation_secret = "1bf38a9d-3205-4b8e-b8d1-ce95466ceef2"

    # La `public_key_pem` debería ser obtenida del endpoint de SyPago y cacheada.
    # Para este ejemplo, usamos la clave proporcionada.
    public_key_pem = b"""-----BEGIN PUBLIC KEY-----
MFkwEwYHKoZIzj0CAQYIKoZIzj0DAQcDQgAE9hXXl4g886loHmI10dLrJWFHEB8r
cyqD1hBdIM3ekQkb5YTpOShAu+7xk0fyL/0IBjLEKwSd7rsKrYGtIgJj0w==
-----END PUBLIC KEY-----"""

    # --- Proceso de Verificación ---
    
    # 1. Reconstruir el mensaje a verificar (payload.nonce.operationSecret)
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
        
        # 4. Verificar la firma usando el hash SHA-256 y la curva elíptica apropiada.
        public_key.verify(
            signature_bytes,
            string_to_verify,
            ec.ECDSA(hashes.SHA256())
        )
        
        # Si la verificación es exitosa, la función no retorna nada.
        
    except InvalidSignature:
        raise SignatureValidationError("La firma no es válida")
    except Exception as e:
        # Captura otros posibles errores, como un mal formato de la clave o firma.
        raise SignatureValidationError(f"Error en el proceso de validación: {e}") 