<?php
session_start();
if (!isset($_SESSION['staff_id'])) header('Location: /login.php');
$title = "Nuevo Pago";
require "../layout/header.php";
require "../layout/sidebar.php";
$config = require __DIR__ . '/../config/config.php';
$pdo = new PDO("mysql:host={$config['db']['host']};dbname={$config['db']['name']};charset={$config['db']['charset']}", $config['db']['user'], $config['db']['pass']);
$alumnos = $pdo->query("SELECT id, nombre FROM alumnos ORDER BY nombre ASC")->fetchAll();
$error = '';
if ($_SERVER['REQUEST_METHOD']=='POST') {
    $alumno_id = $_POST['alumno_id'] ?? '';
    $fecha_pago = $_POST['fecha_pago'] ?? '';
    $monto = $_POST['monto'] ?? '';
    $metodo = $_POST['metodo'] ?? '';
    $referencia = $_POST['referencia'] ?? '';
    $observaciones = $_POST['observaciones'] ?? '';
    if ($alumno_id && $fecha_pago && $monto) {
        $periodo = date('Y-m', strtotime($fecha_pago));
        $stmt = $pdo->prepare("INSERT INTO pagos (alumno_id, fecha_pago, monto, metodo, referencia, observaciones, periodo) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$alumno_id, $fecha_pago, $monto, $metodo, $referencia, $observaciones, $periodo]);
        echo "<script>showToast('Pago creado correctamente.', true, ()=>window.location='pagos.php'); setTimeout(()=>window.location='pagos.php', 2500);</script>"; exit;
    } else {
        $error = "Los campos marcados son obligatorios.";
    }
}
?>
<div class="main-content">
    <h2>Nuevo Pago</h2>
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
            <label class="form-label">Fecha de pago</label>
            <input type="date" class="form-control" name="fecha_pago" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Monto</label>
            <input type="number" class="form-control" name="monto" step="0.01" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Método</label>
            <input type="text" class="form-control" name="metodo">
        </div>
        <div class="mb-3">
            <label class="form-label">Referencia</label>
            <input type="text" class="form-control" name="referencia">
        </div>
        <div class="mb-3">
            <label class="form-label">Observaciones</label>
            <input type="text" class="form-control" name="observaciones">
        </div>
        <div class="d-flex flex-column flex-md-row gap-2 mt-3">
            <button class="btn btn-success flex-fill" type="submit">Crear Pago</button>
            <a href="pagos.php" class="btn btn-secondary flex-fill">Cancelar</a>
        </div>
    </form>
</div>
<?php require "../layout/footer.php"; ?>