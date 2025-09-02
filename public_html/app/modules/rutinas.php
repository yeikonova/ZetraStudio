<?php
session_start();
if (!isset($_SESSION['staff_id'])) header('Location: /login.php');
$title = "Rutinas";
require "../layout/header.php";
require "../layout/sidebar.php";
$config = require __DIR__ . '/../config/config.php';
$pdo = new PDO("mysql:host={$config['db']['host']};dbname={$config['db']['name']};charset={$config['db']['charset']}", $config['db']['user'], $config['db']['pass']);
$rutinas = $pdo->query("SELECT r.*, a.nombre AS alumno FROM rutinas r JOIN alumnos a ON r.alumno_id = a.id ORDER BY r.fecha_inicio DESC")->fetchAll();
?>
<div class="main-content">
    <h2 class="mb-4" data-aos="fade-right">Rutinas</h2>
    <div class="row mb-4">
        <div class="col-md-6">
            <input class="form-control" id="buscaRutina" placeholder="Buscar alumno..." onkeyup="filtrarRutinas()">
        </div>
        <div class="col-md-6 text-end">
            <a href="rutina_nueva.php" class="btn btn-primary"><span class="mdi mdi-plus"></span> Nueva rutina</a>
        </div>
    </div>
    <div class="row g-3" id="listaRutinas">
        <?php foreach($rutinas as $r): ?>
        <div class="col-md-4" data-nombre="<?= strtolower($r['alumno']) ?>">
            <div class="card p-3" data-aos="fade-up">
                <div class="fw-bold"><?= htmlspecialchars($r['alumno']) ?></div>
                <div><?= nl2br(htmlspecialchars($r['descripcion'])) ?></div>
                <div>Inicio: <?= $r['fecha_inicio'] ?></div>
                <div>Fin: <?= $r['fecha_fin'] ?></div>
                <div>Estado: <span class="badge <?= $r['estado']=='activa'?'bg-success':'bg-secondary' ?>">
                    <?= ucfirst($r['estado']) ?>
                </span></div>
                <div class="mt-2 d-flex gap-2">
                    <a href="rutina_ver.php?id=<?= $r['id'] ?>" class="btn btn-outline-primary btn-sm"><span class="mdi mdi-eye"></span> Ver</a>
                    <a href="rutina_editar.php?id=<?= $r['id'] ?>" class="btn btn-outline-secondary btn-sm"><span class="mdi mdi-pencil"></span> Editar</a>
                    <a href="rutina_eliminar.php?id=<?= $r['id'] ?>" class="btn btn-outline-danger btn-sm" onclick="return confirm('¿Eliminar rutina?');"><span class="mdi mdi-delete"></span></a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<script>
function filtrarRutinas() {
    let filtro = document.getElementById('buscaRutina').value.toLowerCase();
    document.querySelectorAll('#listaRutinas [data-nombre]').forEach(card => {
        card.style.display = card.dataset.nombre.includes(filtro) ? '' : 'none';
    });
}
</script>
<?php require "../layout/footer.php"; ?>