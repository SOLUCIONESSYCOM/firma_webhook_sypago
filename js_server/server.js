const express = require('express');
const { validateSyPagoSignature } = require('./signature-validator');

const app = express();
const PORT = 5900;

// Middleware para leer el body como un buffer crudo para la ruta de signature.
// Es crucial para que la firma coincida, ya que se calcula sobre los bytes exactos.
app.use('/signature', express.raw({ type: 'application/json' }));

app.post('/signature', (req, res) => {
    try {
        // 1. Extraer los componentes de la notificación
        const signature = req.headers['x-signature'];
        if (!signature) {
            return res.status(400).send('Cabecera X-Signature es requerida');
        }

        const nonce = req.headers['x-signature-nonce'];
        if (!nonce) {
            return res.status(400).send('Cabecera X-Signature-Nonce es requerida');
        }

        // gracias al middleware express.raw, req.body es un Buffer con el contenido crudo.
        const payload = req.body;

        // 2. Validar la firma
        validateSyPagoSignature(signature, payload, nonce);

        // 3. La firma es válida. Procesar la notificación.
        console.log("Firma válida. Procesando notificación...");
        // Aquí puedes añadir la lógica de tu negocio, por ejemplo:
        // const notificationData = JSON.parse(payload.toString('utf8'));

        res.status(200).json({ message: 'Firma válida y notificación recibida JS' });

    } catch (error) {
        // La firma no es válida.
        console.error(`Error al validar la firma: ${error.message}`);
        res.status(401).send(error.message);
    }
});

app.listen(PORT, () => console.log(`Servidor JS escuchando en http://localhost:${PORT}`));