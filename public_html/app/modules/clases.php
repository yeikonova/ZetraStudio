<?php
session_start();
if (!isset($_SESSION['staff_id'])) header('Location: /login.php');
$title = "Clases";
require "../layout/header.php";
require "../layout/sidebar.php";
$config = require __DIR__ . '/../config/config.php';
$pdo = new PDO("mysql:host={$config['db']['host']};dbname={$config['db']['name']};charset={$config['db']['charset']}", $config['db']['user'], $config['db']['pass']);
$clases = $pdo->query("SELECT * FROM clases ORDER BY horario DESC")->fetchAll();
?>
<div class="main-content">
    <h2 class="mb-4" data-aos="fade-right">Clases</h2>
    <div class="row mb-4">
        <div class="col-md-6">
            <input class="form-control" id="buscaClase" placeholder="Buscar clase..." onkeyup="filtrarClases()">
        </div>
        <div class="col-md-6 text-end">
            <a href="clase_nueva.php" class="btn btn-primary"><span class="mdi mdi-plus"></span> Nueva clase</a>
        </div>
    </div>
    <div class="row g-3" id="listaClases">
        <?php foreach($clases as $c): ?>
        <div class="col-md-4" data-nombre="<?= strtolower($c['nombre']) ?>">
            <div class="card p-3" data-aos="fade-up">
                <div class="fw-bold fs-5"><?= htmlspecialchars($c['nombre']) ?></div>
                <div>Horario: <?= date('d/m/Y H:i', strtotime($c['horario'])) ?></div>
                <div>Cupo: <?= $c['cupo'] ?></div>
                <div>Estado: <span class="badge <?= $c['estado']=='activa'?'bg-success':($c['estado']=='finalizada'?'bg-secondary':'bg-warning') ?>">
                    <?= ucfirst($c['estado']) ?>
                </span></div>
                <div class="mt-2 d-flex gap-2">
                    <a href="clase_ver.php?id=<?= $c['id'] ?>" class="btn btn-outline-primary btn-sm"><span class="mdi mdi-eye"></span> Ver</a>
                    <a href="clase_editar.php?id=<?= $c['id'] ?>" class="btn btn-outline-secondary btn-sm"><span class="mdi mdi-pencil"></span> Editar</a>
                    <a href="clase_eliminar.php?id=<?= $c['id'] ?>" class="btn btn-outline-danger btn-sm" onclick="return confirm('¿Eliminar clase?');"><span class="mdi mdi-delete"></span></a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<script>
function filtrarClases() {
    let filtro = document.getElementById('buscaClase').value.toLowerCase();
    document.querySelectorAll('#listaClases [data-nombre]').forEach(card => {
        card.style.display = card.dataset.nombre.includes(filtro) ? '' : 'none';
    });
}
</script>
<?php require "../layout/footer.php"; ?>