<?php
session_start();
if (!isset($_SESSION['staff_id'])) header('Location: /login.php');
$title = "Ver rutina";
require "../layout/header.php";
require "../layout/sidebar.php";
$id = $_GET['id'] ?? 0;
$config = require __DIR__ . '/../config/config.php';
$pdo = new PDO("mysql:host={$config['db']['host']};dbname={$config['db']['name']};charset={$config['db']['charset']}", $config['db']['user'], $config['db']['pass']);
$stmt = $pdo->prepare("SELECT r.*, a.nombre AS alumno FROM rutinas r JOIN alumnos a ON r.alumno_id = a.id WHERE r.id=?");
$stmt->execute([$id]);
$r = $stmt->fetch();
if (!$r) die("No existe la rutina.");
?>
<div class="main-content">
    <h2 data-aos="fade-right">Ficha de Rutina</h2>
    <div class="card p-4 col-md-6 mx-auto" data-aos="fade-in">
        <div class="fw-bold"><?= htmlspecialchars($r['alumno']) ?></div>
        <div><?= nl2br(htmlspecialchars($r['descripcion'])) ?></div>
        <div>Inicio: <?= $r['fecha_inicio'] ?></div>
        <div>Fin: <?= $r['fecha_fin'] ?></div>
        <div>Estado: <span class="badge <?= $r['estado']=='activa'?'bg-success':'bg-secondary' ?>">
            <?= ucfirst($r['estado']) ?>
        </span></div>
        <hr>
        <a href="rutina_editar.php?id=<?= $r['id'] ?>" class="btn btn-outline-secondary btn-sm"><span class="mdi mdi-pencil"></span> Editar</a>
        <a href="rutina_eliminar.php?id=<?= $r['id'] ?>" class="btn btn-outline-danger btn-sm" onclick="return confirm('¿Eliminar rutina?');"><span class="mdi mdi-delete"></span> Eliminar</a>
    </div>
</div>
<?php require "../layout/footer.php"; ?>