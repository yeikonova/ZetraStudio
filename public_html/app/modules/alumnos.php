<?php
session_start();
if (!isset($_SESSION['staff_id'])) header('Location: /login.php');
$title = "Alumnos";
require "../layout/header.php";
require "../layout/sidebar.php";
$config = require __DIR__ . '/../config/config.php';
$pdo = new PDO("mysql:host={$config['db']['host']};dbname={$config['db']['name']};charset={$config['db']['charset']}", $config['db']['user'], $config['db']['pass']);
$alumnos = $pdo->query("SELECT * FROM alumnos ORDER BY nombre ASC")->fetchAll();
?>
<div class="main-content">
    <h2 class="mb-4" data-aos="fade-right">Alumnos</h2>
    <div class="row mb-4">
        <div class="col-md-6">
            <input class="form-control" id="buscaAlumno" placeholder="Buscar alumno..." onkeyup="filtrarAlumnos()">
        </div>
        <div class="col-md-6 text-end">
            <a href="alumno_nuevo.php" class="btn btn-primary"><span class="mdi mdi-plus"></span> Nuevo alumno</a>
        </div>
    </div>
    <div class="row g-3" id="listaAlumnos">
        <?php foreach($alumnos as $a): ?>
        <div class="col-md-4" data-nombre="<?= strtolower($a['nombre']) ?>">
            <div class="card p-3" data-aos="fade-up">
                <div class="d-flex align-items-center mb-2">
                    <img src="<?= $a['avatar'] ?? '/app/layout/avatar_default.png' ?>" class="avatar me-3" alt="<?= $a['nombre'] ?>">
                    <div>
                        <div class="fw-bold"><?= htmlspecialchars($a['nombre']) ?></div>
                        <small class="<?= $a['estado']=='activo'?'text-success':'text-warning' ?>"><?= $a['estado']=='activo'?'Socio activo':'Pendiente' ?></small>
                    </div>
                </div>
                <div class="d-flex gap-2">
                    <a href="alumno_ver.php?id=<?= $a['id'] ?>" class="btn btn-outline-primary btn-sm"><span class="mdi mdi-eye"></span> Ver</a>
                    <a href="alumno_editar.php?id=<?= $a['id'] ?>" class="btn btn-outline-secondary btn-sm"><span class="mdi mdi-pencil"></span> Editar</a>
                    <a href="alumno_eliminar.php?id=<?= $a['id'] ?>" class="btn btn-outline-danger btn-sm" onclick="return confirm('¿Eliminar alumno?');"><span class="mdi mdi-delete"></span></a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<script>
function filtrarAlumnos() {
    let filtro = document.getElementById('buscaAlumno').value.toLowerCase();
    document.querySelectorAll('#listaAlumnos [data-nombre]').forEach(card => {
        card.style.display = card.dataset.nombre.includes(filtro) ? '' : 'none';
    });
}
</script>
<?php require "../layout/footer.php"; ?>