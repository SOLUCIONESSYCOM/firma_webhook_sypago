from flask import Flask, request, jsonify
from signature_validator import validate_sypago_signature, SignatureValidationError

app = Flask(__name__)

@app.route('/signature', methods=['POST'])
def signature_webhook():
    """
    Endpoint para recibir y validar las notificaciones de SyPago.
    """
    try:
        # 1. Extraer los componentes de la notificación
        signature = request.headers.get('X-Signature')
        if not signature:
            return "Cabecera X-Signature es requerida", 400

        nonce = request.headers.get('X-Signature-Nonce')
        if not nonce:
            return "Cabecera X-Signature-Nonce es requerida", 400

        # Importante: obtener el cuerpo crudo de la solicitud para que la firma coincida.
        payload = request.get_data()

        # 2. Validar la firma
        validate_sypago_signature(signature, payload, nonce)

        # 3. La firma es válida. Procesar la notificación.
        print("Firma válida. Procesando notificación...")
        # Aquí puedes añadir la lógica de tu negocio, por ejemplo,
        # procesar el payload JSON:
        # notification_data = request.get_json()

        return jsonify({"message": "Firma válida y notificación recibida PYTHON"}), 200

    except SignatureValidationError as e:
        print(f"Error al validar la firma: {e}")
        return str(e), 401
    except Exception as e:
        print(f"Error inesperado: {e}")
        return "Error interno del servidor", 500

if __name__ == '__main__':
    # Iniciar el servidor en el puerto 5900
    app.run(port=5900, debug=True) 