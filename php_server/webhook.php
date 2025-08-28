<?php

require_once 'signature_validator.php';

/**
 * Procesa una notificación webhook de SyPago.
 */
function processWebhook() {
    // Configurar cabeceras para la respuesta
    header('Content-Type: application/json');
    
    try {
        // 1. Extraer los componentes de la notificación
        $signature = $_SERVER['HTTP_X_SIGNATURE'] ?? null;
        if (!$signature) {
            http_response_code(400);
            echo json_encode(['error' => 'Cabecera X-Signature es requerida']);
            return;
        }
        
        $nonce = $_SERVER['HTTP_X_SIGNATURE_NONCE'] ?? null;
        if (!$nonce) {
            http_response_code(400);
            echo json_encode(['error' => 'Cabecera X-Signature-Nonce es requerida']);
            return;
        }
        
        // Leer el cuerpo de la petición como una cadena sin procesar
        $payload = file_get_contents('php://input');
        if (!$payload) {
            http_response_code(400);
            echo json_encode(['error' => 'El cuerpo de la petición no puede estar vacío']);
            return;
        }
        
        // 2. Validar la firma
        $validator = new SignatureValidator();
        $validator->validateSyPagoSignature($signature, $payload, $nonce);
        
        // 3. La firma es válida. Procesar la notificación.
        // Aquí puedes deserializar el payload JSON y procesar según tus necesidades
        $data = json_decode($payload, true);
        
        // Ejemplo de procesamiento (aquí deberías agregar tu lógica de negocio)
        error_log("Firma válida. Procesando notificación de transacción: " . ($data['transaction_id'] ?? 'desconocida'));
        
        // Responder con éxito
        http_response_code(200);
        echo json_encode(['message' => 'Firma válida y notificación recibida']);
        
    } catch (SignatureValidationException $e) {
        // La firma no es válida. Descartar la petición.
        error_log("Error al validar la firma: " . $e->getMessage());
        http_response_code(401);
        echo json_encode(['error' => $e->getMessage()]);
        
    } catch (Exception $e) {
        // Error inesperado
        error_log("Error inesperado: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Error interno del servidor']);
    }
}

// Solo procesar solicitudes POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    processWebhook();
} else {
    header('Content-Type: application/json');
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido. Use POST']);
}
