<?php
session_start();
if (!isset($_SESSION['staff_id'])) header('Location: /login.php');
$title = "Editar rutina";
require "../layout/header.php";
require "../layout/sidebar.php";
$id = $_GET['id'] ?? 0;
$config = require __DIR__ . '/../config/config.php';
$pdo = new PDO("mysql:host={$config['db']['host']};dbname={$config['db']['name']};charset={$config['db']['charset']}", $config['db']['user'], $config['db']['pass']);
$alumnos = $pdo->query("SELECT id, nombre FROM alumnos ORDER BY nombre ASC")->fetchAll();
$stmt = $pdo->prepare("SELECT * FROM rutinas WHERE id=?");
$stmt->execute([$id]);
$r = $stmt->fetch();
if (!$r) die("No existe la rutina.");
if ($_SERVER['REQUEST_METHOD']=='POST') {
    $alumno_id = $_POST['alumno_id'] ?? '';
    $descripcion = $_POST['descripcion'] ?? '';
    $fecha_inicio = $_POST['fecha_inicio'] ?? '';
    $fecha_fin = $_POST['fecha_fin'] ?? '';
    $estado = $_POST['estado'] ?? 'activa';
    $stmt = $pdo->prepare("UPDATE rutinas SET alumno_id=?, descripcion=?, fecha_inicio=?, fecha_fin=?, estado=? WHERE id=?");
    $stmt->execute([$alumno_id, $descripcion, $fecha_inicio, $fecha_fin, $estado, $id]);
    echo "<script>
    showToast('Rutina actualizada correctamente.', true, ()=>window.location='rutinas.php');
    setTimeout(()=>window.location='rutinas.php', 2500);
    </script>";
    exit;
}
?>
<div class="main-content">
    <h2>Editar Rutina</h2>
    <form class="card p-4 col-md-6 mx-auto" method="POST">
        <div class="mb-3">
            <label class="form-label">Alumno</label>
            <select class="form-select" name="alumno_id" required>
                <?php foreach($alumnos as $a): ?>
                    <option value="<?= $a['id'] ?>"<?= $a['id']==$r['alumno_id']?' selected':'' ?>><?= htmlspecialchars($a['nombre']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label">Descripción</label>
            <textarea class="form-control" name="descripcion" required><?= htmlspecialchars($r['descripcion']) ?></textarea>
        </div>
        <div class="mb-3">
            <label class="form-label">Fecha inicio</label>
            <input type="date" class="form-control" name="fecha_inicio" value="<?= $r['fecha_inicio'] ?>" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Fecha fin</label>
            <input type="date" class="form-control" name="fecha_fin" value="<?= $r['fecha_fin'] ?>">
        </div>
        <div class="mb-3">
            <label class="form-label">Estado</label>
            <select class="form-select" name="estado">
                <option value="activa"<?= $r['estado']=='activa'?' selected':'' ?>>Activa</option>
                <option value="finalizada"<?= $r['estado']=='finalizada'?' selected':'' ?>>Finalizada</option>
            </select>
        </div>
        <div class="d-flex flex-column flex-md-row gap-2 mt-3">
            <button class="btn btn-success flex-fill" type="submit">Guardar Cambios</button>
            <a href="rutinas.php" class="btn btn-secondary flex-fill">Cancelar</a>
        </div>
    </form>
</div>
<?php require "../layout/footer.php"; ?>