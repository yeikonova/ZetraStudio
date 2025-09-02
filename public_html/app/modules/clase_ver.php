<?php
session_start();
if (!isset($_SESSION['staff_id'])) header('Location: /login.php');
$title = "Ver clase";
require "../layout/header.php";
require "../layout/sidebar.php";
$id = $_GET['id'] ?? 0;
$config = require __DIR__ . '/../config/config.php';
$pdo = new PDO("mysql:host={$config['db']['host']};dbname={$config['db']['name']};charset={$config['db']['charset']}", $config['db']['user'], $config['db']['pass']);

// Eliminar inscripción si se envía el formulario y notificar por WhatsApp
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['eliminar_reserva'], $_POST['reserva_id'])) {
    // Obtener datos del alumno antes de eliminar la reserva
    $stmt = $pdo->prepare("SELECT a.telefono, a.nombre, c.nombre AS clase_nombre, c.horario 
        FROM reservas r
        JOIN alumnos a ON r.alumno_id = a.id
        JOIN clases c ON r.clase_id = c.id
        WHERE r.id = ?");
    $stmt->execute([$_POST['reserva_id']]);
    $alumno = $stmt->fetch();

    // Eliminar la reserva (cambiar estado)
    $stmt = $pdo->prepare("UPDATE reservas SET estado='cancelada' WHERE id=?");
    $stmt->execute([$_POST['reserva_id']]);

    // Notificar por WhatsApp si se encontró el alumno
    if ($alumno && $alumno['telefono']) {
        // Reemplaza estas variables por las de tu entorno si es necesario
        $phone_number_id = $config['whatsapp_phone_id'] ?? ''; // O tu variable global
        $whatsapp_token = $config['whatsapp_token'] ?? '';     // O tu variable global
        $msg = "⚠️ Hola {$alumno['nombre']}, tu inscripción a la clase \"{$alumno['clase_nombre']}\" del día ".date('d/m/Y H:i', strtotime($alumno['horario']))." fue cancelada por el staff. Si tienes dudas, consulta en recepción.";
        sendMessage($phone_number_id, $alumno['telefono'], $msg, $whatsapp_token);
    }

    echo "<script>
        showToast('Reserva eliminada correctamente.', true, ()=>window.location='clase_ver.php?id=$id');
        setTimeout(()=>window.location='clase_ver.php?id=$id', 2500);
    </script>";
    exit;
}

// Datos de la clase
$stmt = $pdo->prepare("SELECT * FROM clases WHERE id=?");
$stmt->execute([$id]);
$c = $stmt->fetch();
if (!$c) die("No existe la clase.");

// Inscriptos a la clase
$stmt = $pdo->prepare("
    SELECT r.id AS reserva_id, a.nombre, a.telefono
    FROM reservas r
    JOIN alumnos a ON r.alumno_id = a.id
    WHERE r.clase_id = ? AND r.estado = 'reservada'
    ORDER BY a.nombre
");
$stmt->execute([$id]);
$inscriptos = $stmt->fetchAll();
?>
<div class="main-content">
    <h2 data-aos="fade-right">Ficha de Clase</h2>
    <div class="card p-4 col-md-6 mx-auto" data-aos="fade-in">
        <div class="fw-bold fs-5"><?= htmlspecialchars($c['nombre']) ?></div>
        <div>Horario: <?= date('d/m/Y H:i', strtotime($c['horario'])) ?></div>
        <div>Cupo: <?= $c['cupo'] ?></div>
        <div>Estado: <span class="badge <?= $c['estado']=='activa'?'bg-success':($c['estado']=='finalizada'?'bg-secondary':'bg-warning') ?>">
            <?= ucfirst($c['estado']) ?>
        </span></div>
        <hr>
        <a href="clase_editar.php?id=<?= $c['id'] ?>" class="btn btn-outline-secondary btn-sm"><span class="mdi mdi-pencil"></span> Editar</a>
        <a href="clase_eliminar.php?id=<?= $c['id'] ?>" class="btn btn-outline-danger btn-sm" onclick="return confirm('¿Eliminar clase?');"><span class="mdi mdi-delete"></span> Eliminar</a>
    </div>

    <!-- Inscriptos a la clase -->
    <div class="card p-4 col-md-8 mx-auto mt-4" data-aos="fade-up">
        <div class="fw-bold fs-5 mb-2">Inscriptos (<?= count($inscriptos) ?>)</div>
        <?php if (count($inscriptos) === 0): ?>
            <div class="alert alert-info">No hay inscriptos en esta clase.</div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-striped align-middle">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Teléfono</th>
                        <th>Acción</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach($inscriptos as $al): ?>
                    <tr>
                        <td><?= htmlspecialchars($al['nombre']) ?></td>
                        <td><?= htmlspecialchars($al['telefono']) ?></td>
                        <td>
                            <form method="post" style="display:inline" onsubmit="return confirm('¿Dar de baja a este alumno?');">
                                <input type="hidden" name="reserva_id" value="<?= $al['reserva_id'] ?>">
                                <button type="submit" name="eliminar_reserva" class="btn btn-danger btn-sm">
                                    <span class="mdi mdi-account-remove"></span> Dar de baja
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php require "../layout/footer.php"; ?>