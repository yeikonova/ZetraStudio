<?php
session_start();
if (!isset($_SESSION['staff_id'])) header('Location: /login.php');
$title = "Editar Alumno";
require "../layout/header.php";
require "../layout/sidebar.php";
$id = $_GET['id'] ?? 0;
$config = require __DIR__ . '/../config/config.php';
$pdo = new PDO("mysql:host={$config['db']['host']};dbname={$config['db']['name']};charset={$config['db']['charset']}", $config['db']['user'], $config['db']['pass']);
$stmt = $pdo->prepare("SELECT * FROM alumnos WHERE id=?");
$stmt->execute([$id]);
$a = $stmt->fetch();
if (!$a) die("No existe el alumno.");
if ($_SERVER['REQUEST_METHOD']=='POST') {
    $nombre = $_POST['nombre'] ?? '';
    $telefono = $_POST['telefono'] ?? '';
    $estado = $_POST['estado'] ?? 'activo';
    $avatar = $a['avatar'];
    if (isset($_FILES['avatar_file']) && $_FILES['avatar_file']['size'] > 0) {
        $dest = '/uploads/avatars/' . uniqid() . '_' . basename($_FILES['avatar_file']['name']);
        $realPath = $_SERVER['DOCUMENT_ROOT'] . $dest;
        if (!is_dir($_SERVER['DOCUMENT_ROOT'].'/uploads/avatars')) {
            mkdir($_SERVER['DOCUMENT_ROOT'].'/uploads/avatars', 0777, true);
        }
        move_uploaded_file($_FILES['avatar_file']['tmp_name'], $realPath);
        $avatar = $dest;
    }
    if ($nombre && $telefono) {
        $upd = $pdo->prepare("UPDATE alumnos SET nombre=?, telefono=?, estado=?, avatar=? WHERE id=?");
        $upd->execute([$nombre, $telefono, $estado, $avatar, $id]);
        echo "<script>
        showToast('Alumno actualizado correctamente.', true, ()=>window.location='alumnos.php');
        setTimeout(()=>window.location='alumnos.php', 2500);
        </script>";
        exit;
    } else {
        $error = "Todos los campos son obligatorios.";
    }
}
?>
<div class="main-content">
    <h2>Editar Alumno</h2>
    <form class="card p-4 col-md-6 mx-auto" method="POST" enctype="multipart/form-data" style="min-width:320px;">
        <div class="mb-3">
            <label class="form-label">Nombre</label>
            <input type="text" class="form-control" name="nombre" value="<?= htmlspecialchars($a['nombre']) ?>" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Teléfono</label>
            <input type="text" class="form-control" name="telefono" value="<?= htmlspecialchars($a['telefono']) ?>" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Estado</label>
            <select class="form-select" name="estado">
                <option value="activo"<?= $a['estado']=='activo'?' selected':'' ?>>Activo</option>
                <option value="pendiente"<?= $a['estado']=='pendiente'?' selected':'' ?>>Pendiente</option>
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label">Foto de perfil</label>
            <input type="file" class="form-control" name="avatar_file" accept="image/*">
            <?php if ($a['avatar']): ?>
                <img src="<?= htmlspecialchars($a['avatar']) ?>" width="80" class="mt-2 rounded">
            <?php endif; ?>
        </div>
        <div class="d-flex flex-column flex-md-row gap-2 mt-3">
            <button class="btn btn-success flex-fill" type="submit">Guardar Cambios</button>
            <a href="alumnos.php" class="btn btn-secondary flex-fill">Cancelar</a>
        </div>
    </form>
</div>
<?php require "../layout/footer.php"; ?>