<?php
session_start();
if (!isset($_SESSION['staff_id'])) header('Location: /login.php');
$title = "Reservas";
require "../layout/header.php";
require "../layout/sidebar.php";
$config = require __DIR__ . '/../config/config.php';
$pdo = new PDO("mysql:host={$config['db']['host']};dbname={$config['db']['name']};charset={$config['db']['charset']}", $config['db']['user'], $config['db']['pass']);
$reservas = $pdo->query("SELECT r.*, a.nombre as alumno, c.nombre as clase FROM reservas r JOIN alumnos a ON r.alumno_id=a.id JOIN clases c ON r.clase_id=c.id ORDER BY r.fecha_reserva DESC")->fetchAll();
?>
<div class="main-content">
    <h2 class="mb-4" data-aos="fade-right">Reservas</h2>
    <div class="row mb-4">
        <div class="col-md-6">
            <input class="form-control" id="buscaReserva" placeholder="Buscar alumno o clase..." onkeyup="filtrarReservas()">
        </div>
        <div class="col-md-6 text-end">
            <a href="reserva_nueva.php" class="btn btn-primary"><span class="mdi mdi-plus"></span> Nueva reserva</a>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-hover bg-white rounded shadow-sm">
            <thead>
                <tr>
                    <th>Alumno</th>
                    <th>Clase</th>
                    <th>Fecha reserva</th>
                    <th>Estado</th>
                    <th></th>
                </tr>
            </thead>
            <tbody id="listaReservas">
                <?php foreach($reservas as $r): ?>
                <tr data-info="<?= strtolower($r['alumno'].' '.$r['clase']) ?>">
                    <td><?= htmlspecialchars($r['alumno']) ?></td>
                    <td><?= htmlspecialchars($r['clase']) ?></td>
                    <td><?= $r['fecha_reserva'] ?></td>
                    <td><span class="badge <?= $r['estado']=='reservada'?'bg-success':($r['estado']=='asistida'?'bg-primary':'bg-warning') ?>">
                        <?= ucfirst($r['estado']) ?></span>
                    </td>
                    <td>
                        <a href="reserva_ver.php?id=<?= $r['id'] ?>" class="btn btn-outline-primary btn-sm"><span class="mdi mdi-eye"></span></a>
                        <a href="reserva_eliminar.php?id=<?= $r['id'] ?>" class="btn btn-outline-danger btn-sm" onclick="return confirm('¿Eliminar reserva?');"><span class="mdi mdi-delete"></span></a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<script>
function filtrarReservas() {
    let filtro = document.getElementById('buscaReserva').value.toLowerCase();
    document.querySelectorAll('#listaReservas tr[data-info]').forEach(row => {
        row.style.display = row.dataset.info.includes(filtro) ? '' : 'none';
    });
}
</script>
<?php require "../layout/footer.php"; ?>