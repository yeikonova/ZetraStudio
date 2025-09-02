<?php
session_start();
if (!isset($_SESSION['staff_id'])) header('Location: /login.php');
$title = "Ver alumno";
require "../layout/header.php";
require "../layout/sidebar.php";
$id = $_GET['id'] ?? 0;
$config = require __DIR__ . '/../config/config.php';
$pdo = new PDO("mysql:host={$config['db']['host']};dbname={$config['db']['name']};charset={$config['db']['charset']}", $config['db']['user'], $config['db']['pass']);
$a = $pdo->prepare("SELECT * FROM alumnos WHERE id=?");
$a->execute([$id]);
$a = $a->fetch();
if (!$a) die("No existe el alumno.");
?>
<div class="main-content">
    <h2 data-aos="fade-right">Ficha de Alumno</h2>
    <div class="card p-4 col-md-6 mx-auto" data-aos="fade-in">
        <div class="d-flex align-items-center mb-3">
            <img src="<?= $a['avatar'] ?? '/app/layout/avatar_default.png' ?>" class="avatar me-3" alt="<?= $a['nombre'] ?>">
            <div>
                <div class="fw-bold fs-5"><?= htmlspecialchars($a['nombre']) ?></div>
                <small class="text-muted"><?= htmlspecialchars($a['telefono']) ?></small>
                <div>
                    <span class="badge <?= $a['estado']=='activo'?'bg-success':'bg-warning' ?>">
                        <?= ucfirst($a['estado']) ?>
                    </span>
                </div>
            </div>
        </div>
        <hr>
        <div class="mb-2">
            <a href="alumno_editar.php?id=<?= $a['id'] ?>" class="btn btn-outline-secondary btn-sm"><span class="mdi mdi-pencil"></span> Editar</a>
            <a href="alumno_eliminar.php?id=<?= $a['id'] ?>" class="btn btn-outline-danger btn-sm" onclick="return confirm('¿Eliminar alumno?');"><span class="mdi mdi-delete"></span> Eliminar</a>
        </div>
        <div class="mt-3">
            <a href="javascript:void(0)" onclick="window.open('https://wa.me/<?= $a['telefono'] ?>','_blank')" class="btn btn-success btn-sm"><span class="mdi mdi-whatsapp"></span> WhatsApp</a>
        </div>
    </div>
</div>
<?php require "../layout/footer.php"; ?>