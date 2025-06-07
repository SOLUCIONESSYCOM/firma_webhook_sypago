package main

import (
	"errors"
	"fmt"
	"io"
	"net/http"

	"github.com/gin-gonic/gin"
)

// WebhookHandler procesa las notificaciones de SyPago
func WebhookHandler(c *gin.Context) {
	// 1. Extraer los componentes de la notificación
	signature := c.Request.Header.Get("X-Signature")
	if signature == "" {
		c.AbortWithError(http.StatusBadRequest, errors.New("cabecera X-Signature es requerida"))
		return
	}

	nonce := c.Request.Header.Get("X-Signature-Nonce")
	if nonce == "" {
		c.AbortWithError(http.StatusBadRequest, errors.New("cabecera X-Signature-Nonce es requerida"))
		return
	}

	payload, err := io.ReadAll(c.Request.Body)
	if err != nil {
		c.AbortWithError(http.StatusBadRequest, err)
		return
	}

	// 2. Validar la firma
	err = ValidateSyPagoSignature(signature, payload, nonce)
	if err != nil {
		// La firma no es válida.
		fmt.Printf("Error al validar la firma: %v\n", err)
		c.AbortWithError(http.StatusUnauthorized, err)
		return
	}

	// 3. La firma es válida.
	fmt.Println("Firma válida. Procesando notificación...")
	c.JSON(http.StatusOK, gin.H{"message": "Firma válida y notificación recibida GO"})
}

func main() {
	router := gin.Default()
	router.POST("/signature", WebhookHandler)

	fmt.Println("Servidor GO escuchando en http://localhost:5900")
	router.Run(":5900")
}
