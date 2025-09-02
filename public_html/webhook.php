<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

$config = require __DIR__ . '/app/config/config.php';
$whatsapp_token = $config['whatsapp']['token'];
$phone_number_id = $config['whatsapp']['phone_number_id'];
$mp_access_token = $config['mercadopago']['access_token'];
$db = $config['db'];

// DB connection
try {
    $pdo = new PDO(
        "mysql:host={$db['host']};dbname={$db['name']};charset={$db['charset']}",
        $db['user'], $db['pass'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    error_log("Error DB: " . $e->getMessage());
    exit('Error de conexión BD');
}

define('STATE_DIR', __DIR__ . '/app/states');
if (!file_exists(STATE_DIR)) mkdir(STATE_DIR, 0777, true);

function saveUserState($userId, $stateData) {
    $file = STATE_DIR . "/$userId.json";
    if ($stateData === null) {
        if (file_exists($file)) unlink($file);
    } else {
        $stateData['last_interaction'] = time();
        file_put_contents($file, json_encode($stateData));
    }
}
function getUserState($userId) {
    $file = STATE_DIR . "/$userId.json";
    if (!file_exists($file)) return null;
    $content = file_get_contents($file);
    $state = json_decode($content, true);
    if (isset($state['last_interaction']) && (time() - $state['last_interaction']) > 600) {
        unlink($file);
        return null;
    }
    return $state;
}
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
    @file_get_contents($url, false, $context);
}

// Leer input JSON (POST)
$input = json_decode(file_get_contents('php://input'), true);
file_put_contents(__DIR__.'/log_gym.txt', date('Y-m-d H:i:s')." ".print_r($input,true)."\n", FILE_APPEND);

$phone_number_id_in = $input['entry'][0]['changes'][0]['value']['metadata']['phone_number_id'] ?? '';
$from = $input['entry'][0]['changes'][0]['value']['messages'][0]['from'] ?? '';
$message_body_raw = $input['entry'][0]['changes'][0]['value']['messages'][0]['text']['body'] ?? '';
$message_body = strtolower(trim($message_body_raw));

if (!$from || !$phone_number_id_in) { http_response_code(200); echo 'OK'; exit; }

$state = getUserState($from);

// --- 1. Verificar si el remitente es socio activo ---
$stmt = $pdo->prepare("SELECT * FROM alumnos WHERE telefono=?");
$stmt->execute([$from]);
$alumno = $stmt->fetch();

if (!$alumno) {
    // --- FLUJO PARA NO SOCIOS ---
    if (!$state || !isset($state['step'])) {
        // Primer contacto: menú simple para no socios
        $msg = "👋 ¡Hola! Bienvenido a *Tu Gimnasio*.\n\nNo estás registrado como socio.\n\nPor favor selecciona una opción:\n\n1️⃣ Quiero afiliarme\n2️⃣ Obtener información\n3️⃣ Contactar staff";
        saveUserState($from, ['step' => 'no_socio_menu']);
        sendMessage($phone_number_id, $from, $msg, $whatsapp_token);
        http_response_code(200); echo 'OK'; exit;
    }

    // Manejar flujo para no socios
    switch ($state['step']) {
        case 'no_socio_menu':
            if ($message_body === '1' || strpos($message_body, 'afili') !== false) {
                // Iniciar afiliación: pedir datos paso a paso
                $msg = "¡Genial! Para comenzar tu afiliación, por favor envíame tu nombre completo:";
                saveUserState($from, ['step' => 'afiliacion_nombre', 'afiliacion' => []]);
                sendMessage($phone_number_id, $from, $msg, $whatsapp_token);
            } elseif ($message_body === '2') {
                $msg = "ℹ️ Información general:\n\n- Horarios: Lunes a Viernes 7:00 a 22:00\n- Dirección: Calle Ejemplo 123, Ciudad\n- Teléfono: 099111222\n\n¿Quieres volver al menú? Escribe 'menu'.";
                sendMessage($phone_number_id, $from, $msg, $whatsapp_token);
            } elseif ($message_body === '3') {
                $msg = "📞 Puedes contactarnos enviando un mail a staff@gym.com o llamando al 099111222.\n¿Deseas que te contacten? Escribe tu consulta y el staff será notificado.";
                saveUserState($from, ['step' => 'no_socio_contacto']);
                sendMessage($phone_number_id, $from, $msg, $whatsapp_token);
            } else {
                sendMessage($phone_number_id, $from, "Opción no válida. Escribe 'menu' para volver.", $whatsapp_token);
            }
            break;
        case 'afiliacion_nombre':
            if (trim($message_body_raw) !== '') {
                $afiliacion = $state['afiliacion'] ?? [];
                $afiliacion['nombre'] = $message_body_raw;
                saveUserState($from, ['step' => 'afiliacion_fecha', 'afiliacion' => $afiliacion]);
                sendMessage($phone_number_id, $from, "Por favor, envíame tu fecha de nacimiento (formato DD/MM/AAAA):", $whatsapp_token);
            } else {
                sendMessage($phone_number_id, $from, "Por favor, escribe tu nombre completo:", $whatsapp_token);
            }
            break;
        case 'afiliacion_fecha':
            if (preg_match('/^(0[1-9]|[12][0-9]|3[01])[\/\-](0[1-9]|1[0-2])[\/\-](19|20)\d\d$/', $message_body_raw)) {
                $afiliacion = $state['afiliacion'] ?? [];
                $afiliacion['fecha_nac'] = $message_body_raw;
                saveUserState($from, ['step' => 'afiliacion_cedula', 'afiliacion' => $afiliacion]);
                sendMessage($phone_number_id, $from, "Ingresa tu cédula (sin puntos ni guión):", $whatsapp_token);
            } else {
                sendMessage($phone_number_id, $from, "Formato inválido. Escribe tu fecha de nacimiento (DD/MM/AAAA):", $whatsapp_token);
            }
            break;
        case 'afiliacion_cedula':
            if (preg_match('/^\d{6,10}$/', $message_body_raw)) {
                $afiliacion = $state['afiliacion'] ?? [];
                $afiliacion['cedula'] = $message_body_raw;
                // Guardar en la base
                $nombre = $afiliacion['nombre'];
                $fecha_nac = $afiliacion['fecha_nac'];
                $cedula = $afiliacion['cedula'];
                $telefono = $from;
                $estado = 'pendiente';
                $stmt = $pdo->prepare("INSERT INTO alumnos (nombre, telefono, estado, avatar) VALUES (?, ?, ?, NULL)");
                $stmt->execute([$nombre, $telefono, $estado]);
                $nuevo_id = $pdo->lastInsertId();
                // Opcional: guardar datos extendidos (fecha_nac, cedula) en otra tabla o en 'observaciones'
                sendMessage($phone_number_id, $from, "¡Registro recibido! Para activar tu perfil debes:\n\n- Realizar el pago online (te enviamos el link)\n- O acercarte a la sucursal para abonar personalmente.", $whatsapp_token);

                // Obtener cuota actual desde configuracion SIEMPRE
                $stmt2 = $pdo->prepare("SELECT valor FROM configuracion WHERE clave = 'cuota_general' LIMIT 1");
                $stmt2->execute();
                $monto = floatval($stmt2->fetchColumn());

                $preference_data = [
                    "items" => [["title" => "Cuota mensual gimnasio", "quantity" => 1, "unit_price" => $monto]],
                    "notification_url" => $config['base_url']."/mp_webhook.php",
                    "external_reference" => $nuevo_id
                ];
                $ch = curl_init("https://api.mercadopago.com/checkout/preferences");
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($preference_data));
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    "Content-Type: application/json",
                    "Authorization: Bearer $mp_access_token"
                ]);
                $response = curl_exec($ch);
                $resp = json_decode($response, true);
                $mp_link = $resp['init_point'] ?? null;
                curl_close($ch);

                if ($mp_link) {
                    sendMessage($phone_number_id, $from, "💳 Paga tu cuota aquí:\n$mp_link\nCuando se acredite, tu perfil estará listo para usar todos los servicios.", $whatsapp_token);
                } else {
                    sendMessage($phone_number_id, $from, "❌ Error al generar el link de pago. Puedes abonar en la sucursal.", $whatsapp_token);
                }
                saveUserState($from, ['step' => 'no_socio_menu']);
            } else {
                sendMessage($phone_number_id, $from, "Por favor, ingresa tu cédula solo con números (sin puntos ni guiones):", $whatsapp_token);
            }
            break;
        case 'no_socio_contacto':
            // Aquí podrías notificar al staff por mail o guardar la consulta
            sendMessage($phone_number_id, $from, "¡Gracias! El staff recibió tu mensaje y te contactará pronto.", $whatsapp_token);
            saveUserState($from, ['step' => 'no_socio_menu']);
            break;
        default:
            sendMessage($phone_number_id, $from, "Opción no válida. Escribe 'menu' para volver.", $whatsapp_token);
            saveUserState($from, ['step' => 'no_socio_menu']);
            break;
    }
    http_response_code(200); echo 'OK'; exit;
}

// --- FLUJO PARA SOCIOS ACTIVOS ---
$alumno_id = $alumno['id'];
$periodo_actual = date('Y-m');
$stmt = $pdo->prepare("SELECT COUNT(*) FROM pagos WHERE alumno_id=? AND periodo = ?");
$stmt->execute([$alumno_id, $periodo_actual]);
$al_dia = $stmt->fetchColumn() > 0;

// Siempre obtener el valor de la cuota desde configuracion
$stmt2 = $pdo->prepare("SELECT valor FROM configuracion WHERE clave = 'cuota_general' LIMIT 1");
$stmt2->execute();
$monto = floatval($stmt2->fetchColumn());

$menu = "🏋️ *Menú Principal Socio*\n\n1️⃣ Reservar clase\n2️⃣ Mis clases\n3️⃣ Pagar cuota\n4️⃣ Mi rutina\n5️⃣ Ayuda\n6️⃣ Solicitar actualización de rutina\n\n_Responde con el número de la opción_.";

$state = getUserState($from);
if (!$state) $state = [ 'step' => 'saludo', 'welcome_sent' => false ];

// Bienvenida
if (!isset($state['welcome_sent']) || $state['welcome_sent'] === false) {
    sendMessage($phone_number_id, $from, "👋 ¡Hola {$alumno['nombre']}! Bienvenido a *Tu Gimnasio*.", $whatsapp_token);
    $state['welcome_sent'] = true;
    $state['step'] = 'menu_principal';
    saveUserState($from, $state);
    sendMessage($phone_number_id, $from, $menu, $whatsapp_token);
    http_response_code(200); echo 'OK'; exit;
}

// Comandos globales
if (in_array($message_body, ['menu', 'volver'])) {
    $state['step'] = 'menu_principal';
    saveUserState($from, $state);
    sendMessage($phone_number_id, $from, $menu, $whatsapp_token);
    http_response_code(200); echo 'OK'; exit;
}

// Si no está al día, solo puede pagar
if (!$al_dia) {
    if ($message_body === '3' || strpos($message_body, 'pagar') !== false) {
        $preference_data = [
            "items" => [["title" => "Cuota mensual gimnasio", "quantity" => 1, "unit_price" => $monto]],
            "notification_url" => $config['base_url']."/mp_webhook.php",
            "external_reference" => $alumno_id
        ];
        $ch = curl_init("https://api.mercadopago.com/checkout/preferences");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($preference_data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Content-Type: application/json",
            "Authorization: Bearer $mp_access_token"
        ]);
        $response = curl_exec($ch);
        $resp = json_decode($response, true);
        $mp_link = $resp['init_point'] ?? null;
        curl_close($ch);

        if ($mp_link) {
            sendMessage($phone_number_id, $from, "💳 Paga tu cuota aquí:\n$mp_link\nDespués de pagar, tendrás acceso completo.", $whatsapp_token);
        } else {
            sendMessage($phone_number_id, $from, "❌ Error al generar el link de pago. Intenta más tarde o consulta en sucursal.", $whatsapp_token);
        }
        http_response_code(200); echo 'OK'; exit;
    } else {
        sendMessage($phone_number_id, $from, "⚠️ Tu cuota está vencida o pendiente. Solo puedes pagar la cuota para acceder a los servicios. Escribe '3' para recibir el link.", $whatsapp_token);
        http_response_code(200); echo 'OK'; exit;
    }
}

// --- Menú completo para socio al día ---
switch ($state['step']) {
    case 'menu_principal':
        if ($message_body === '1') { // Reservar clase
            $stmt = $pdo->query("
    SELECT c.id, c.nombre, DATE_FORMAT(c.horario, '%d/%m %H:%i') as horario, c.cupo as cupo_total, c.estado,
        (SELECT COUNT(*) FROM reservas r WHERE r.clase_id = c.id AND r.estado = 'reservada') as ocupados
    FROM clases c
    WHERE c.estado='activa' AND c.horario>NOW()
    ORDER BY c.horario ASC LIMIT 5
");
$clases = $stmt->fetchAll();

$msg = "📅 *Clases disponibles:*\n";
foreach ($clases as $idx => $clase) {
    $disponibles = $clase['cupo_total'] - $clase['ocupados'];
    $msg .= ($idx+1).") {$clase['nombre']} - {$clase['horario']} (cupos: $disponibles/{$clase['cupo_total']})\n";
}
$msg .= "\nResponde con el número para reservar tu lugar o *menu* para volver.";
            $state['clases_options'] = array_column($clases, 'id');
            $state['step'] = 'reserva_clase';
            saveUserState($from, $state);
            sendMessage($phone_number_id, $from, $msg, $whatsapp_token);
        } elseif ($message_body === '2') { // Mis clases
            $stmt = $pdo->prepare("SELECT c.nombre, DATE_FORMAT(c.horario, '%d/%m %H:%i') as horario, r.estado 
                FROM reservas r JOIN clases c ON r.clase_id=c.id 
                JOIN alumnos a ON r.alumno_id=a.id WHERE a.telefono=? AND c.horario>NOW() ORDER BY c.horario ASC");
            $stmt->execute([$from]);
            $misclases = $stmt->fetchAll();
            if (empty($misclases)) {
                sendMessage($phone_number_id, $from, "No tienes clases reservadas.", $whatsapp_token);
            } else {
                $msg = "📋 *Tus próximas clases:*\n";
                foreach ($misclases as $c) {
                    $msg .= "- {$c['nombre']} el {$c['horario']} ({$c['estado']})\n";
                }
                sendMessage($phone_number_id, $from, $msg, $whatsapp_token);
            }
        } elseif ($message_body === '3') { // Pagar cuota
            $preference_data = [
                "items" => [["title" => "Cuota mensual gimnasio", "quantity" => 1, "unit_price" => $monto]],
                "notification_url" => $config['base_url']."/mp_webhook.php",
                "external_reference" => $alumno_id
            ];
            $ch = curl_init("https://api.mercadopago.com/checkout/preferences");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($preference_data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "Content-Type: application/json",
                "Authorization: Bearer $mp_access_token"
            ]);
            $response = curl_exec($ch);
            $resp = json_decode($response, true);
            $mp_link = $resp['init_point'] ?? null;
            curl_close($ch);

            if ($mp_link) {
                sendMessage($phone_number_id, $from, "💳 Para pagar tu cuota, ingresa aquí:\n$mp_link\nDespués de pagar, recibirás confirmación automática.", $whatsapp_token);
            } else {
                sendMessage($phone_number_id, $from, "❌ Error al generar el link de pago. Intenta más tarde.", $whatsapp_token);
            }
        } elseif ($message_body === '4') { // Mi rutina
            $stmt = $pdo->prepare("SELECT descripcion, fecha_inicio, fecha_fin FROM rutinas r JOIN alumnos a ON r.alumno_id=a.id WHERE a.telefono=? AND r.estado='activa' ORDER BY fecha_inicio DESC LIMIT 1");
            $stmt->execute([$from]);
            $rutina = $stmt->fetch();
            if ($rutina) {
                $msg = "🏋️ *Tu rutina actual:*\n{$rutina['descripcion']}\n\nDesde: {$rutina['fecha_inicio']} hasta {$rutina['fecha_fin']}";
            } else {
                $msg = "No tienes una rutina asignada. Solicita a tu entrenador.";
            }
            sendMessage($phone_number_id, $from, $msg, $whatsapp_token);
        } elseif ($message_body === '5') { // Ayuda
            $msg = "ℹ️ *Ayuda*\nEscribe:\n1️⃣ para reservar clase\n2️⃣ para ver tus clases\n3️⃣ para pagar cuota\n4️⃣ para tu rutina\n6️⃣ para solicitar actualización de rutina\nEscribe *menu* para volver al menú principal.";
            sendMessage($phone_number_id, $from, $msg, $whatsapp_token);
        } elseif ($message_body === '6') { // Solicitar rutina/actualización con mensaje
            sendMessage(
                $phone_number_id,
                $from,
                "Por favor, escribe un mensaje al staff sobre tu solicitud o tus objetivos de rutina.\n\nPor ejemplo: 'Quiero una rutina para bajar de peso' o 'Actualizar rutina, entreno para fuerza'.",
                $whatsapp_token
            );
            $state['step'] = 'esperando_mensaje_rutina';
            saveUserState($from, $state);
        } else {
            sendMessage($phone_number_id, $from, "❌ Opción inválida. Escribe *menu* para volver.", $whatsapp_token);
        }
        break;

    case 'esperando_mensaje_rutina':
        $mensaje = trim($message_body_raw);
        if ($mensaje !== '') {
            $stmt = $pdo->prepare("INSERT INTO solicitudes (alumno_id, tipo, mensaje) VALUES (?, 'actualizacion', ?)");
            $stmt->execute([$alumno_id, $mensaje]);
            sendMessage(
                $phone_number_id,
                $from,
                "✅ ¡Solicitud enviada! El staff recibió tu mensaje y pronto un entrenador te contactará.",
                $whatsapp_token
            );
            $state['step'] = 'menu_principal';
            saveUserState($from, $state);
        } else {
            sendMessage(
                $phone_number_id,
                $from,
                "Por favor, escribe un mensaje sobre tu solicitud de rutina.",
                $whatsapp_token
            );
        }
        break;

    case 'reserva_clase':
        $op = intval($message_body) - 1;
        $clases_options = $state['clases_options'] ?? [];
        if (isset($clases_options[$op])) {
            $clase_id = $clases_options[$op];
            $stmt = $pdo->prepare("SELECT id, cupo FROM clases WHERE id=? AND estado='activa'");
            $stmt->execute([$clase_id]);
            $clase = $stmt->fetch();
            if (!$clase) {
                sendMessage($phone_number_id, $from, "Clase no disponible. Escribe *menu* para volver.", $whatsapp_token);
                $state['step'] = 'menu_principal';
                saveUserState($from, $state);
                break;
            }
            // Verifica que no esté ya inscrito
            $stmt = $pdo->prepare("SELECT id FROM reservas WHERE alumno_id=? AND clase_id=?");
            $stmt->execute([$alumno_id, $clase['id']]);
            if ($stmt->fetchColumn()) {
                sendMessage($phone_number_id, $from, "Ya tienes reserva en esta clase.", $whatsapp_token);
                $state['step'] = 'menu_principal';
                saveUserState($from, $state);
                break;
            }
            // Verifica cupo
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM reservas WHERE clase_id=? AND estado='reservada'");
            $stmt->execute([$clase['id']]);
            $ocupados = $stmt->fetchColumn();
            if ($ocupados >= $clase['cupo']) {
                sendMessage($phone_number_id, $from, "No quedan cupos para esta clase.", $whatsapp_token);
                $state['step'] = 'menu_principal';
                saveUserState($from, $state);
                break;
            }
            // Reserva
            $stmt = $pdo->prepare("INSERT INTO reservas (alumno_id, clase_id) VALUES (?, ?)");
            $stmt->execute([$alumno_id, $clase['id']]);
            sendMessage($phone_number_id, $from, "✅ Reserva exitosa. Te esperamos en clase!", $whatsapp_token);
            $state['step'] = 'menu_principal';
            saveUserState($from, $state);
        } else {
            sendMessage($phone_number_id, $from, "Opción inválida. Escribe *menu* para volver.", $whatsapp_token);
        }
        break;

    default:
        $state['step'] = 'menu_principal';
        saveUserState($from, $state);
        sendMessage($phone_number_id, $from, $menu, $whatsapp_token);
        break;
}

saveUserState($from, $state);
http_response_code(200);
echo 'OK';
exit;