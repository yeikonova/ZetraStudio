<?php
session_start();
if (!isset($_SESSION['staff_id'])) header('Location: /login.php');
$title = "Nueva rutina";
require "../layout/header.php";
require "../layout/sidebar.php";
$config = require __DIR__ . '/../config/config.php';
$pdo = new PDO("mysql:host={$config['db']['host']};dbname={$config['db']['name']};charset={$config['db']['charset']}", $config['db']['user'], $config['db']['pass']);
$alumnos = $pdo->query("SELECT id, nombre FROM alumnos ORDER BY nombre ASC")->fetchAll();
$error = '';
if ($_SERVER['REQUEST_METHOD']=='POST') {
    $alumno_id = $_POST['alumno_id'] ?? '';
    $descripcion = $_POST['descripcion'] ?? '';
    $fecha_inicio = $_POST['fecha_inicio'] ?? '';
    $fecha_fin = $_POST['fecha_fin'] ?? '';
    $estado = $_POST['estado'] ?? 'activa';
    if ($alumno_id && $descripcion && $fecha_inicio) {
        $stmt = $pdo->prepare("INSERT INTO rutinas (alumno_id, descripcion, fecha_inicio, fecha_fin, estado) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$alumno_id, $descripcion, $fecha_inicio, $fecha_fin, $estado]);
        echo "<script>
        showToast('Rutina creada correctamente.', true, ()=>window.location='rutinas.php');
        setTimeout(()=>window.location='rutinas.php', 2500);
        </script>";
        exit;
    } else {
        $error = "Los campos marcados son obligatorios.";
    }
}
?>
<div class="main-content">
    <h2>Nueva Rutina</h2>
    <?php if($error): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>
    <form class="card p-4 col-md-6 mx-auto" method="POST">
        <div class="mb-3">
            <label class="form-label">Alumno</label>
            <select class="form-select" name="alumno_id" required>
                <option value="">Selecciona...</option>
                <?php foreach($alumnos as $a): ?>
                    <option value="<?= $a['id'] ?>"><?= htmlspecialchars($a['nombre']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label">Descripción</label>
            <textarea class="form-control" name="descripcion" required></textarea>
        </div>
        <div class="mb-3">
            <label class="form-label">Fecha inicio</label>
            <input type="date" class="form-control" name="fecha_inicio" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Fecha fin</label>
            <input type="date" class="form-control" name="fecha_fin">
        </div>
        <div class="mb-3">
            <label class="form-label">Estado</label>
            <select class="form-select" name="estado">
                <option value="activa">Activa</option>
                <option value="finalizada">Finalizada</option>
            </select>
        </div>
        <div class="d-flex flex-column flex-md-row gap-2 mt-3">
            <button class="btn btn-success flex-fill" type="submit">Crear Rutina</button>
            <a href="rutinas.php" class="btn btn-secondary flex-fill">Cancelar</a>
        </div>
    </form>
</div>
<?php require "../layout/footer.php"; ?>