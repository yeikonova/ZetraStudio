<?php
session_start();
if (!isset($_SESSION['staff_id'])) header('Location: /login.php');
$title = "Staff";
require "../layout/header.php";
require "../layout/sidebar.php";
$config = require __DIR__ . '/../config/config.php';
$pdo = new PDO("mysql:host={$config['db']['host']};dbname={$config['db']['name']};charset={$config['db']['charset']}", $config['db']['user'], $config['db']['pass']);
$staff = $pdo->query("SELECT id, usuario FROM staff ORDER BY usuario ASC")->fetchAll();
?>
<div class="main-content">
    <h2 class="mb-4" data-aos="fade-right">Staff</h2>
    <div class="row mb-4">
        <div class="col-md-6 text-end">
            <a href="staff_nuevo.php" class="btn btn-primary"><span class="mdi mdi-plus"></span> Nuevo staff</a>
        </div>
    </div>
    <div class="row g-3" id="listaStaff">
        <?php foreach($staff as $s): ?>
        <div class="col-md-4">
            <div class="card p-3" data-aos="fade-up">
                <div class="fw-bold fs-5"><?= htmlspecialchars($s['usuario']) ?></div>
                <div class="mt-2 d-flex gap-2">
                    <a href="staff_ver.php?id=<?= $s['id'] ?>" class="btn btn-outline-primary btn-sm"><span class="mdi mdi-eye"></span> Ver</a>
                    <a href="staff_editar.php?id=<?= $s['id'] ?>" class="btn btn-outline-secondary btn-sm"><span class="mdi mdi-pencil"></span> Editar</a>
                    <a href="staff_eliminar.php?id=<?= $s['id'] ?>" class="btn btn-outline-danger btn-sm" onclick="return confirm('¿Eliminar staff?');"><span class="mdi mdi-delete"></span></a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php require "../layout/footer.php"; ?>