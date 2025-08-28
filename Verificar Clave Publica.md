# Verificación de Clave Publica mediante OpenSSL

Este documento detalla los procedimientos para validar la clave pública 


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


