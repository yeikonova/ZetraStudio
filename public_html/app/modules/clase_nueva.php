<?php
session_start();
if (!isset($_SESSION['staff_id'])) header('Location: /login.php');
$title = "Nueva clase";
require "../layout/header.php";
require "../layout/sidebar.php";
$error = '';
if ($_SERVER['REQUEST_METHOD']=='POST') {
    $nombre = $_POST['nombre'] ?? '';
    $horario = $_POST['horario'] ?? '';
    $cupo = $_POST['cupo'] ?? 20;
    $estado = $_POST['estado'] ?? 'activa';
    if ($nombre && $horario) {
        $config = require __DIR__ . '/../config/config.php';
        $pdo = new PDO("mysql:host={$config['db']['host']};dbname={$config['db']['name']};charset={$config['db']['charset']}", $config['db']['user'], $config['db']['pass']);
        $stmt = $pdo->prepare("INSERT INTO clases (nombre, horario, cupo, estado) VALUES (?, ?, ?, ?)");
        $stmt->execute([$nombre, $horario, $cupo, $estado]);
        echo "<script>
        showToast('Clase creada correctamente.', true, ()=>window.location='clases.php');
        setTimeout(()=>window.location='clases.php', 2500);
        </script>";
        exit;
    } else {
        $error = "Todos los campos son obligatorios.";
    }
}
?>
<div class="main-content">
    <h2>Nueva Clase</h2>
    <?php if($error): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>
    <form class="card p-4 col-md-6 mx-auto" method="POST">
        <div class="mb-3">
            <label class="form-label">Nombre</label>
            <input type="text" class="form-control" name="nombre" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Horario</label>
            <input type="datetime-local" class="form-control" name="horario" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Cupo</label>
            <input type="number" class="form-control" name="cupo" value="20" min="1" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Estado</label>
            <select class="form-select" name="estado">
                <option value="activa">Activa</option>
                <option value="cancelada">Cancelada</option>
                <option value="finalizada">Finalizada</option>
            </select>
        </div>
        <div class="d-flex flex-column flex-md-row gap-2 mt-3">
            <button class="btn btn-success flex-fill" type="submit">Crear Clase</button>
            <a href="clases.php" class="btn btn-secondary flex-fill">Cancelar</a>
        </div>
    </form>
</div>
<?php require "../layout/footer.php"; ?>