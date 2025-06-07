package com.example.sypagovalidator;

import org.springframework.http.ResponseEntity;
import org.springframework.web.bind.annotation.PostMapping;
import org.springframework.web.bind.annotation.RequestBody;
import org.springframework.web.bind.annotation.RequestHeader;
import org.springframework.web.bind.annotation.RestController;
import org.springframework.web.bind.annotation.RequestMapping;

import java.util.Map;

@RestController
@RequestMapping("/")
public class WebhookController {

    private final SignatureValidationService validationService;

    public WebhookController(SignatureValidationService validationService) {
        this.validationService = validationService;
    }

    @PostMapping("/signature")
    public ResponseEntity<Map<String, String>> handleWebhook(
            @RequestBody String payload,
            @RequestHeader("X-Signature") String signature,
            @RequestHeader("X-Signature-Nonce") String nonce) {
        
        try {
            validationService.validateSyPagoSignature(signature, payload, nonce);
            
            System.out.println("Firma válida. Procesando notificación...");

            return ResponseEntity.ok(Map.of("message", "Firma válida y notificación recibida JAVA"));

        } catch (Exception e) {
            System.err.println("Error al validar la firma: " + e.getMessage());
            return ResponseEntity.status(401).build();
        }
    }
} 