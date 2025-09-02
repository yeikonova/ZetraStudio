<?php
session_start();
if (!isset($_SESSION['staff_id'])) header('Location: /login.php');
$title = "Ver Reserva";
require "../layout/header.php";
require "../layout/sidebar.php";
$id = $_GET['id'] ?? 0;
$config = require __DIR__ . '/../config/config.php';
$pdo = new PDO("mysql:host={$config['db']['host']};dbname={$config['db']['name']};charset={$config['db']['charset']}", $config['db']['user'], $config['db']['pass']);
$stmt = $pdo->prepare("SELECT r.*, a.nombre as alumno, c.nombre as clase FROM reservas r JOIN alumnos a ON r.alumno_id=a.id JOIN clases c ON r.clase_id=c.id WHERE r.id=?");
$stmt->execute([$id]);
$r = $stmt->fetch();
if (!$r) die("No existe la reserva.");
?>
<div class="main-content">
    <h2>Ficha Reserva</h2>
    <div class="card p-4 col-md-6 mx-auto">
        <div><b>Alumno:</b> <?= htmlspecialchars($r['alumno']) ?></div>
        <div><b>Clase:</b> <?= htmlspecialchars($r['clase']) ?></div>
        <div><b>Estado:</b> <?= ucfirst($r['estado']) ?></div>
        <div><b>Fecha reserva:</b> <?= $r['fecha_reserva'] ?></div>
        <hr>
        <a href="reserva_eliminar.php?id=<?= $r['id'] ?>" class="btn btn-outline-danger btn-sm" onclick="return confirm('¿Eliminar reserva?');"><span class="mdi mdi-delete"></span> Eliminar</a>
    </div>
</div>
<?php require "../layout/footer.php"; ?>