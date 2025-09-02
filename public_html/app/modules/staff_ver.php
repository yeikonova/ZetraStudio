<?php
session_start();
if (!isset($_SESSION['staff_id'])) header('Location: /login.php');
$title = "Ver Staff";
require "../layout/header.php";
require "../layout/sidebar.php";
$id = $_GET['id'] ?? 0;
$config = require __DIR__ . '/../config/config.php';
$pdo = new PDO("mysql:host={$config['db']['host']};dbname={$config['db']['name']};charset={$config['db']['charset']}", $config['db']['user'], $config['db']['pass']);
$stmt = $pdo->prepare("SELECT id, usuario FROM staff WHERE id=?");
$stmt->execute([$id]);
$s = $stmt->fetch();
if (!$s) die("No existe el staff.");
?>
<div class="main-content">
    <h2>Ficha Staff</h2>
    <div class="card p-4 col-md-6 mx-auto">
        <div class="fw-bold fs-5"><?= htmlspecialchars($s['usuario']) ?></div>
        <hr>
        <a href="staff_editar.php?id=<?= $s['id'] ?>" class="btn btn-outline-secondary btn-sm"><span class="mdi mdi-pencil"></span> Editar</a>
        <a href="staff_eliminar.php?id=<?= $s['id'] ?>" class="btn btn-outline-danger btn-sm" onclick="return confirm('¿Eliminar staff?');"><span class="mdi mdi-delete"></span> Eliminar</a>
    </div>
</div>
<?php require "../layout/footer.php"; ?>