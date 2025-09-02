<?php
session_start();
if (!isset($_SESSION['staff_id'])) header('Location: /login.php');
$title = "Editar clase";
require "../layout/header.php";
require "../layout/sidebar.php";
$id = $_GET['id'] ?? 0;
$config = require __DIR__ . '/../config/config.php';
$pdo = new PDO("mysql:host={$config['db']['host']};dbname={$config['db']['name']};charset={$config['db']['charset']}", $config['db']['user'], $config['db']['pass']);
$stmt = $pdo->prepare("SELECT * FROM clases WHERE id=?");
$stmt->execute([$id]);
$c = $stmt->fetch();
if (!$c) die("No existe la clase.");
if ($_SERVER['REQUEST_METHOD']=='POST') {
    $nombre = $_POST['nombre'] ?? '';
    $horario = $_POST['horario'] ?? '';
    $cupo = $_POST['cupo'] ?? 20;
    $estado = $_POST['estado'] ?? 'activa';
    $stmt = $pdo->prepare("UPDATE clases SET nombre=?, horario=?, cupo=?, estado=? WHERE id=?");
    $stmt->execute([$nombre, $horario, $cupo, $estado, $id]);
    echo "<script>
    showToast('Clase actualizada correctamente.', true, ()=>window.location='clases.php');
    setTimeout(()=>window.location='clases.php', 2500);
    </script>";
    exit;
}
?>
<div class="main-content">
    <h2>Editar Clase</h2>
    <form class="card p-4 col-md-6 mx-auto" method="POST">
        <div class="mb-3">
            <label class="form-label">Nombre</label>
            <input type="text" class="form-control" name="nombre" value="<?= htmlspecialchars($c['nombre']) ?>" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Horario</label>
            <input type="datetime-local" class="form-control" name="horario" value="<?= date('Y-m-d\TH:i', strtotime($c['horario'])) ?>" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Cupo</label>
            <input type="number" class="form-control" name="cupo" value="<?= $c['cupo'] ?>" min="1" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Estado</label>
            <select class="form-select" name="estado">
                <option value="activa"<?= $c['estado']=='activa'?' selected':'' ?>>Activa</option>
                <option value="cancelada"<?= $c['estado']=='cancelada'?' selected':'' ?>>Cancelada</option>
                <option value="finalizada"<?= $c['estado']=='finalizada'?' selected':'' ?>>Finalizada</option>
            </select>
        </div>
        <div class="d-flex flex-column flex-md-row gap-2 mt-3">
            <button class="btn btn-success flex-fill" type="submit">Guardar Cambios</button>
            <a href="clases.php" class="btn btn-secondary flex-fill">Cancelar</a>
        </div>
    </form>
</div>
<?php require "../layout/footer.php"; ?>