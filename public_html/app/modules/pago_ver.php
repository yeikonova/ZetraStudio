<?php
session_start();
if (!isset($_SESSION['staff_id'])) header('Location: /login.php');
$title = "Ver Pago";
require "../layout/header.php";
require "../layout/sidebar.php";
$id = $_GET['id'] ?? 0;
$config = require __DIR__ . '/../config/config.php';
$pdo = new PDO("mysql:host={$config['db']['host']};dbname={$config['db']['name']};charset={$config['db']['charset']}", $config['db']['user'], $config['db']['pass']);
$stmt = $pdo->prepare("SELECT p.*, a.nombre as alumno FROM pagos p JOIN alumnos a ON p.alumno_id=a.id WHERE p.id=?");
$stmt->execute([$id]);
$p = $stmt->fetch();
if (!$p) die("No existe el pago.");
?>
<div class="main-content">
    <h2>Ficha Pago</h2>
    <div class="card p-4 col-md-6 mx-auto">
        <div><b>Alumno:</b> <?= htmlspecialchars($p['alumno']) ?></div>
        <div><b>Monto:</b> $<?= number_format($p['monto'],2) ?></div>
        <div><b>Periodo:</b> <?= htmlspecialchars($p['periodo'] ?? '—') ?></div>
        <div><b>Fecha:</b> <?= $p['fecha_pago'] ?></div>
        <div><b>Método:</b> <?= htmlspecialchars($p['metodo']) ?></div>
        <div><b>Referencia:</b> <?= htmlspecialchars($p['referencia']) ?></div>
        <div><b>Observaciones:</b> <?= htmlspecialchars($p['observaciones']) ?></div>
        <hr>
        <a href="pago_eliminar.php?id=<?= $p['id'] ?>" class="btn btn-outline-danger btn-sm" onclick="return confirm('¿Eliminar pago?');"><span class="mdi mdi-delete"></span> Eliminar</a>
    </div>
</div>
<?php require "../layout/footer.php"; ?>