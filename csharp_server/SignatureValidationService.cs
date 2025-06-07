using System.Security.Cryptography;
using System.Text;
using System.Threading.Tasks;

public interface ISignatureValidationService
{
    Task ValidateSyPagoSignatureAsync(string signatureB64, string payload, string nonce);
}

public class SignatureValidationService : ISignatureValidationService
{
    public Task ValidateSyPagoSignatureAsync(string signatureB64, string payload, string nonce)
    {
        // --- Obtención de Secretos ---
        // El `operationSecret` DEBE ser recuperado de su base de datos.
        string operationSecret = "1bf38a9d-3205-4b8e-b8d1-ce95466ceef2";

        // La `publicKeyPEM` DEBE ser obtenida del endpoint GET /api/v1/user/key
        // y cacheada.
        string publicKeyPEM = @"-----BEGIN PUBLIC KEY-----
MFkwEwYHKoZIzj0CAQYIKoZIzj0DAQcDQgAE9hXXl4g886loHmI10dLrJWFHEB8r
cyqD1hBdIM3ekQkb5YTpOShAu+7xk0fyL/0IBjLEKwSd7rsKrYGtIgJj0w==
-----END PUBLIC KEY-----";

        // --- Proceso de Verificación ---

        // 1. Reconstruir el mensaje a verificar
        string stringToVerify = $"{payload}.{nonce}.{operationSecret}";
        byte[] dataToVerify = Encoding.UTF8.GetBytes(stringToVerify);

        // 2. Calcular el hash SHA-256 del mensaje
        byte[] hash = SHA256.HashData(dataToVerify);
        
        // 3. Decodificar la firma de Base64
        byte[] signatureBytes = System.Convert.FromBase64String(signatureB64);

        // 4. Cargar la clave pública y verificar la firma
        using (var ecdsa = ECDsa.Create())
        {
            ecdsa.ImportFromPem(publicKeyPEM);

            // 5. Verificar la firma del hash
            bool isSignatureValid = ecdsa.VerifyHash(hash, signatureBytes, DSASignatureFormat.Rfc3279DerSequence);

            if (!isSignatureValid)
            {
                throw new CryptographicException("La firma no es válida");
            }
        }
        
        // La firma es válida, la tarea se completa.
        return Task.CompletedTask;
    }
} 