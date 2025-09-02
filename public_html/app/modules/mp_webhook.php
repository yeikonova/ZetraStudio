<?php
$config = require __DIR__ . '/../config/config.php';
$pdo = new PDO(
    "mysql:host={$config['db']['host']};dbname={$config['db']['name']};charset={$config['db']['charset']}",
    $config['db']['user'],
    $config['db']['pass']
);
$mp_access_token = $config['mercadopago']['access_token'];
$whatsapp_token = $config['whatsapp']['token'];
$phone_number_id = $config['whatsapp']['phone_number_id'];

function log_mp($msg) {
    file_put_contents(__DIR__ . "/mp_webhook.log", date("Y-m-d H:i:s") . " " . $msg . "\n", FILE_APPEND);
}

function sendWhatsApp($alumno_tel, $msg, $phone_number_id, $whatsapp_token) {
    $url = "https://graph.facebook.com/v17.0/$phone_number_id/messages";
    $data = [
        'messaging_product' => 'whatsapp',
        'to' => $alumno_tel,
        'type' => 'text',
        'text' => ['body' => $msg]
    ];
    $opts = [
        'http' => [
            'header'  => "Content-Type: application/json\r\nAuthorization: Bearer $whatsapp_token\r\n",
            'method'  => 'POST',
            'content' => json_encode($data),
            'timeout' => 10,
        ]
    ];
    $context = stream_context_create($opts);
    @file_get_contents($url, false, $context);
}

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = file_get_contents('php://input');
        log_mp("RAW INPUT: $input");

        // RESPONDER SIEMPRE 200 OK ANTES DE NADA
        http_response_code(200);
        echo 'OK';

        $data = json_decode($input, true);
        log_mp("JSON DECODED: " . print_r($data, true));

        if (!$data) {
            log_mp("No data, early exit");
            exit;
        }

        $topic = $_GET['topic'] ?? ($_POST['topic'] ?? $data['type'] ?? '');
        $payment_id = $data['data']['id'] ?? null;

        log_mp("TOPIC: $topic, PAYMENT_ID: $payment_id");

        if (($topic === 'payment' || $topic === 'payment.updated') && $payment_id) {
            $ch = curl_init("https://api.mercadopago.com/v1/payments/$payment_id");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "Authorization: Bearer $mp_access_token"
            ]);
            $resp = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            log_mp("MP RESPONSE [$http_code]: $resp");

            $mp_payment = json_decode($resp, true);

            if ($http_code == 200 && $mp_payment && $mp_payment['status'] === 'approved') {
                $alumno_id = $mp_payment['external_reference'] ?? null;
                $monto = $mp_payment['transaction_amount'] ?? 0;
                $fecha = $mp_payment['date_approved'] ?? date('Y-m-d');
                $metodo = $mp_payment['payment_method_id'] ?? 'mercadopago';
                $referencia = $mp_payment['id'] ?? null;
                $observaciones = $mp_payment['description'] ?? '';
                $periodo = date('Y-m', strtotime($fecha));

                log_mp("STATUS APPROVED, external_reference: $alumno_id");

                if ($alumno_id) {
                    // Verifica que no haya pago duplicado
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM pagos WHERE referencia = ?");
                    $stmt->execute([$referencia]);
                    $exists = $stmt->fetchColumn();

                    // Obtener teléfono y nombre
                    $st = $pdo->prepare("SELECT telefono, nombre FROM alumnos WHERE id=?");
                    $st->execute([$alumno_id]);
                    $alumno = $st->fetch();
                    $alumno_tel = $alumno['telefono'] ?? '';
                    $alumno_nombre = $alumno['nombre'] ?? '';

                    if (!$exists) {
                        $stmt = $pdo->prepare("INSERT INTO pagos (alumno_id, fecha_pago, monto, metodo, referencia, observaciones, periodo) VALUES (?, ?, ?, ?, ?, ?, ?)");
                        $stmt->execute([$alumno_id, $fecha, $monto, $metodo, $referencia, $observaciones, $periodo]);
                        log_mp("Pago registrado para alumno $alumno_id, pago $referencia");
                    }

                    // Cambiar estado del alumno a cuota_al_dia si pagó este mes
                    $periodo_actual = date('Y-m');
                    if ($periodo === $periodo_actual) {
                        $stmt = $pdo->prepare("UPDATE alumnos SET estado = 'cuota_al_dia' WHERE id = ?");
                        $stmt->execute([$alumno_id]);
                        log_mp("Alumno actualizado a cuota_al_dia: $alumno_id");
                    }

                    // Enviar WhatsApp de confirmación
                    if ($alumno_tel) {
                        $msg = "✅ ¡Hola $alumno_nombre! Tu pago fue recibido correctamente. Ya tienes acceso completo a los servicios del gimnasio. ¡Gracias!";
                        sendWhatsApp($alumno_tel, $msg, $phone_number_id, $whatsapp_token);
                        log_mp("WhatsApp enviado a $alumno_tel");
                    }
                } else {
                    log_mp("No external_reference en el pago");
                }
            } else {
                log_mp("Pago no aprobado o no encontrado");
            }
        } else {
            log_mp("No es topic=payment o falta payment_id");
        }

        exit;
    } else {
        http_response_code(405);
        echo 'Método no permitido';
        exit;
    }
} catch (Throwable $e) {
    log_mp("ERROR: " . $e->getMessage() . " - " . $e->getFile() . ":" . $e->getLine());
    http_response_code(200); // Siempre 200 para Mercado Pago
    echo 'OK';
    exit;
}
?>