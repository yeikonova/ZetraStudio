<?php
echo "Iniciando cron_recordatorio.php\n";
$config = require __DIR__ . '/app/config/config.php';
$pdo = new PDO("mysql:host={$config['db']['host']};dbname={$config['db']['name']};charset={$config['db']['charset']}", $config['db']['user'], $config['db']['pass']);
$whatsapp_token = $config['whatsapp']['token'];
$phone_number_id = $config['whatsapp']['phone_number_id'];

function sendMessage($phone_number_id, $to, $message, $whatsapp_token) {
    $url = "https://graph.facebook.com/v17.0/$phone_number_id/messages";
    $data = [
        'messaging_product' => 'whatsapp',
        'to' => $to,
        'type' => 'text',
        'text' => ['body' => $message]
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
    $result = @file_get_contents($url, false, $context);
    echo "Respuesta de WhatsApp para $to: $result\n";
    file_put_contents('whatsapp_api_debug.log', date('Y-m-d H:i:s')."\nEnviado a $to: $result\n", FILE_APPEND);
}

echo "Ejecutando consulta de alumnos...\n";
$hoy = new DateTime();
$stmt = $pdo->query("
    SELECT a.telefono, a.nombre, MAX(p.fecha_pago) as fecha_pago
    FROM alumnos a 
    JOIN pagos p ON a.id=p.alumno_id 
    WHERE a.estado='activo'
    GROUP BY a.id
");

$rows = $stmt->fetchAll();
echo "Filas encontradas: " . count($rows) . "\n";

foreach ($rows as $row) {
    $fecha_pago = new DateTime($row['fecha_pago']);
    $diff = $hoy->diff($fecha_pago);

    // condicionó true
    
    //$diff->days >= 28 && $diff->days < 31
    if (true) {
        echo "Intentando enviar mensaje a {$row['telefono']} ({$row['nombre']})\n";
        sendMessage(
            $phone_number_id,
            $row['telefono'],
            "🔔 Hola {$row['nombre']}, tu membresía del gimnasio vence en ".(31-$diff->days)." días. Responde '3' para pagar tu cuota y no perder tus beneficios.",
            $whatsapp_token
        );
    }
}