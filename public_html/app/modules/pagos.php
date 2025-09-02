<?php
session_start();
if (!isset($_SESSION['staff_id'])) header('Location: /login.php');
$title = "Pagos";
require "../layout/header.php";
require "../layout/sidebar.php";
$config = require __DIR__ . '/../config/config.php';
$pdo = new PDO("mysql:host={$config['db']['host']};dbname={$config['db']['name']};charset={$config['db']['charset']}", $config['db']['user'], $config['db']['pass']);
$pagos = $pdo->query("SELECT p.*, a.nombre as alumno FROM pagos p JOIN alumnos a ON p.alumno_id=a.id ORDER BY p.fecha_pago DESC")->fetchAll();
?>
<div class="main-content">
    <h2 class="mb-4" data-aos="fade-right">Pagos</h2>
    <div class="row mb-4">
        <div class="col-md-6">
            <input class="form-control" id="buscaPago" placeholder="Buscar alumno..." onkeyup="filtrarPagos()">
        </div>
        <div class="col-md-6 text-end">
            <a href="pago_nuevo.php" class="btn btn-primary"><span class="mdi mdi-plus"></span> Nuevo pago</a>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-hover bg-white rounded shadow-sm">
            <thead>
                <tr>
                    <th>Alumno</th>
                    <th>Monto</th>
                    <th>Periodo</th>
                    <th>Método</th>
                    <th>Fecha</th>
                    <th>Observaciones</th>
                    <th></th>
                </tr>
            </thead>
            <tbody id="listaPagos">
                <?php foreach($pagos as $p): ?>
                <tr data-nombre="<?= strtolower($p['alumno']) ?>">
                    <td><?= htmlspecialchars($p['alumno']) ?></td>
                    <td>$<?= number_format($p['monto'],2) ?></td>
                    <td><?= htmlspecialchars($p['periodo'] ?? '—') ?></td>
                    <td><?= htmlspecialchars($p['metodo']) ?></td>
                    <td><?= $p['fecha_pago'] ?></td>
                    <td><?= htmlspecialchars($p['observaciones']) ?></td>
                    <td>
                        <a href="pago_ver.php?id=<?= $p['id'] ?>" class="btn btn-outline-primary btn-sm"><span class="mdi mdi-eye"></span></a>
                        <a href="pago_eliminar.php?id=<?= $p['id'] ?>" class="btn btn-outline-danger btn-sm" onclick="return confirm('¿Eliminar pago?');"><span class="mdi mdi-delete"></span></a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<script>
function filtrarPagos() {
    let filtro = document.getElementById('buscaPago').value.toLowerCase();
    document.querySelectorAll('#listaPagos tr[data-nombre]').forEach(row => {
        row.style.display = row.dataset.nombre.includes(filtro) ? '' : 'none';
    });
}
</script>
<?php require "../layout/footer.php"; ?>