<?php
session_start();
if (!isset($_SESSION['staff_id'])) header('Location: /login.php');
$title = "Nuevo Staff";
require "../layout/header.php";
require "../layout/sidebar.php";
$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $usuario = $_POST['usuario'] ?? '';
    $password = $_POST['password'] ?? '';
    if ($usuario && $password) {
        $config = require __DIR__ . '/../config/config.php';
        $pdo = new PDO("mysql:host={$config['db']['host']};dbname={$config['db']['name']};charset={$config['db']['charset']}", $config['db']['user'], $config['db']['pass']);
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO staff (usuario, password) VALUES (?, ?)");
        $stmt->execute([$usuario, $hash]);
        echo "<script>showToast('Staff creado correctamente.', true, ()=>window.location='staff.php'); setTimeout(()=>window.location='staff.php', 2500);</script>"; exit;
    } else {
        $error = "Todos los campos son obligatorios.";
    }
}
?>
<div class="main-content">
    <h2>Nuevo Staff</h2>
    <?php if($error): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>
    <form class="card p-4 col-md-6 mx-auto" method="POST">
        <div class="mb-3">
            <label class="form-label">Usuario</label>
            <input type="text" class="form-control" name="usuario" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Contraseña</label>
            <input type="password" class="form-control" name="password" required>
        </div>
        <div class="d-flex flex-column flex-md-row gap-2 mt-3">
            <button class="btn btn-success flex-fill" type="submit">Crear Staff</button>
            <a href="staff.php" class="btn btn-secondary flex-fill">Cancelar</a>
        </div>
    </form>
</div>
<?php require "../layout/footer.php"; ?>s