<?php
session_start();
if (!isset($_SESSION['staff_id'])) header('Location: /login.php');
$title = "Nueva Reserva";
require "../layout/header.php";
require "../layout/sidebar.php";
$config = require __DIR__ . '/../config/config.php';
$pdo = new PDO("mysql:host={$config['db']['host']};dbname={$config['db']['name']};charset={$config['db']['charset']}", $config['db']['user'], $config['db']['pass']);
$alumnos = $pdo->query("SELECT id, nombre FROM alumnos ORDER BY nombre ASC")->fetchAll();
$clases = $pdo->query("SELECT id, nombre FROM clases ORDER BY nombre ASC")->fetchAll();
$error = '';
if ($_SERVER['REQUEST_METHOD']=='POST') {
    $alumno_id = $_POST['alumno_id'] ?? '';
    $clase_id = $_POST['clase_id'] ?? '';
    $estado = $_POST['estado'] ?? 'reservada';
    if ($alumno_id && $clase_id) {
        $stmt = $pdo->prepare("INSERT INTO reservas (alumno_id, clase_id, estado) VALUES (?, ?, ?)");
        $stmt->execute([$alumno_id, $clase_id, $estado]);
        echo "<script>showToast('Reserva creada correctamente.', true, ()=>window.location='reservas.php'); setTimeout(()=>window.location='reservas.php', 2500);</script>"; exit;
    } else {
        $error = "Todos los campos son obligatorios.";
    }
}
?>
<div class="main-content">
    <h2>Nueva Reserva</h2>
    <?php if($error): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>
    <form class="card p-4 col-md-6 mx-auto" method="POST">
        <div class="mb-3">
            <label class="form-label">Alumno</label>
            <select class="form-select" name="alumno_id" required>
                <option value="">Selecciona...</option>
                <?php foreach($alumnos as $a): ?>
                    <option value="<?= $a['id'] ?>"><?= htmlspecialchars($a['nombre']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label">Clase</label>
            <select class="form-select" name="clase_id" required>
                <option value="">Selecciona...</option>
                <?php foreach($clases as $c): ?>
                    <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['nombre']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label">Estado</label>
            <select class="form-select" name="estado">
                <option value="reservada">Reservada</option>
                <option value="asistida">Asistida</option>
                <option value="cancelada">Cancelada</option>
            </select>
        </div>
        <div class="d-flex flex-column flex-md-row gap-2 mt-3">
            <button class="btn btn-success flex-fill" type="submit">Crear Reserva</button>
            <a href="reservas.php" class="btn btn-secondary flex-fill">Cancelar</a>
        </div>
    </form>
</div>
<?php require "../layout/footer.php"; ?>