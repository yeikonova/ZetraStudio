<?php
session_start();
if (!isset($_SESSION['staff_id'])) header('Location: /login.php');
$title = "Editar Staff";
require "../layout/header.php";
require "../layout/sidebar.php";
$id = $_GET['id'] ?? 0;
$config = require __DIR__ . '/../config/config.php';
$pdo = new PDO("mysql:host={$config['db']['host']};dbname={$config['db']['name']};charset={$config['db']['charset']}", $config['db']['user'], $config['db']['pass']);
$stmt = $pdo->prepare("SELECT id, usuario FROM staff WHERE id=?");
$stmt->execute([$id]);
$s = $stmt->fetch();
if (!$s) die("No existe el staff.");
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $usuario = $_POST['usuario'] ?? '';
    $password = $_POST['password'] ?? '';
    if ($usuario) {
        if ($password) {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $upd = $pdo->prepare("UPDATE staff SET usuario=?, password=? WHERE id=?");
            $upd->execute([$usuario, $hash, $id]);
        } else {
            $upd = $pdo->prepare("UPDATE staff SET usuario=? WHERE id=?");
            $upd->execute([$usuario, $id]);
        }
        echo "<script>showToast('Staff actualizado.', true, ()=>window.location='staff.php'); setTimeout(()=>window.location='staff.php', 2500);</script>"; exit;
    }
}
?>
<div class="main-content">
    <h2>Editar Staff</h2>
    <form class="card p-4 col-md-6 mx-auto" method="POST">
        <div class="mb-3">
            <label class="form-label">Usuario</label>
            <input type="text" class="form-control" name="usuario" value="<?= htmlspecialchars($s['usuario']) ?>" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Nueva Contraseña (dejar vacío para no cambiar)</label>
            <input type="password" class="form-control" name="password">
        </div>
        <div class="d-flex flex-column flex-md-row gap-2 mt-3">
            <button class="btn btn-success flex-fill" type="submit">Guardar Cambios</button>
            <a href="staff.php" class="btn btn-secondary flex-fill">Cancelar</a>
        </div>
    </form>
</div>
<?php require "../layout/footer.php"; ?>