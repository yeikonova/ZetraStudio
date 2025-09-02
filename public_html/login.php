<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


session_start();
require_once __DIR__ . '/app/config/config.php';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = $_POST['usuario'] ?? '';
    $pass = $_POST['password'] ?? '';
    $config = require __DIR__ . '/app/config/config.php';
    $pdo = new PDO("mysql:host={$config['db']['host']};dbname={$config['db']['name']};charset={$config['db']['charset']}", $config['db']['user'], $config['db']['pass']);
    $stmt = $pdo->prepare("SELECT id, password FROM staff WHERE usuario=?");
    $stmt->execute([$user]);
    $row = $stmt->fetch();
    if ($row && password_verify($pass, $row['password'])) {
        $_SESSION['staff_id'] = $row['id'];
        header("Location: dashboard.php");
        exit;
    } else {
        $error = "Usuario o contraseña incorrectos";
    }
}
$title = "Login Staff";
require "app/layout/header.php";
?>
<div class="d-flex justify-content-center align-items-center" style="height:100vh;">
    <form class="card p-4 shadow-lg" method="POST" style="min-width: 330px;" data-aos="fade-in">
        <h4 class="mb-4 text-primary"><span class="mdi mdi-shield-account"></span> Acceso Staff</h4>
        <?php if($error): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>
        <div class="mb-3">
            <label class="form-label">Usuario</label>
            <input type="text" class="form-control" name="usuario" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Contraseña</label>
            <input type="password" class="form-control" name="password" required>
        </div>
        <button class="btn btn-primary w-100" type="submit">Ingresar</button>
    </form>
</div>
<?php require "app/layout/footer.php"; ?>