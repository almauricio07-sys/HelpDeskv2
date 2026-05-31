<?php
require_once 'app/models/Ticket.php';

class WhatsappController {
    public function webhook() {
        // Twilio manda los datos directamente por POST
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            
            // Extraer quién lo manda y qué escribió
            $telefono = $_POST['From'] ?? ''; 
            $texto_recibido = trim($_POST['Body'] ?? '');

            // Log para que veas en tu computadora si Twilio sí se está conectando
            file_put_contents('twilio_log.txt', "De: $telefono - Mensaje: $texto_recibido\n", FILE_APPEND);

            // Mandar a procesar
            $this->procesarSolicitud($telefono, $texto_recibido);
        } else {
            // Si entras desde el navegador, solo muestra que está activo
            echo "El Webhook de Twilio está activo y escuchando.";
        }
    }

    private function procesarSolicitud($telefono, $texto) {
        $partes = explode(',', $texto);

        // Validamos si escribió la palabra REPORTE y separó por comas
        if (count($partes) >= 3 && strtoupper(trim($partes[0])) === 'REPORTE') {
            $clave = trim($partes[1]);
            $descripcion = trim($partes[2]);

            $ticketModel = new Ticket();
            $id_canal = 2; // Suponiendo que el ID 2 en tu base de datos es WhatsApp
            
            $folio = $ticketModel->crearTicketWhatsApp($clave, $descripcion, $id_canal);

            if ($folio) {
                $respuesta = "✅ ¡Hola! Tu solicitud ha sido registrada exitosamente. Tu número de folio es *$folio*. Un especialista te atenderá pronto.";
            } else {
                $respuesta = "❌ Error al registrar tu reporte. Verifica que tu clave de usuario exista en el sistema.";
            }
        } else {
            // Flujo Alternativo del RF_02: Mensajes de guía al usuario
            $respuesta = "🤖 ¡Hola! Soy el bot de la Mesa de Ayuda.\nPara levantar un ticket envía tu mensaje con este formato exacto:\n\n*REPORTE, TuClave, Descripción de tu problema*\n\nEjemplo:\nREPORTE, ALUM123, El proyector del aula 5 no funciona.";
        }

        // Devolvemos la respuesta a Twilio
        $this->responderTwilio($respuesta);
    }

    private function responderTwilio($mensaje) {
        header("Content-Type: text/xml");
        echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
        echo "<Response>\n";
        echo "    <Message>" . htmlspecialchars($mensaje) . "</Message>\n";
        echo "</Response>";
        exit;
    }
}
?>