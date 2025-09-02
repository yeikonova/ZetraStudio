<?php
session_start();
if (!isset($_SESSION['staff_id'])) header('Location: /login.php');
$title = "Gestionar Pagos";
require "../layout/header.php";
require "../layout/sidebar.php";

$error = '';
$success = '';

$config = require __DIR__ . '/../config/config.php';
$host = $config['db']['host'];
$db   = $config['db']['name'];
$user = $config['db']['user'];
$pass = $config['db']['pass'];
$charset = $config['db']['charset'];

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];
$pdo = new PDO($dsn, $user, $pass, $options);

// Crear tabla configuración si no existe
$pdo->exec("CREATE TABLE IF NOT EXISTS configuracion (
    id INT AUTO_INCREMENT PRIMARY KEY,
    clave VARCHAR(50) UNIQUE NOT NULL,
    valor VARCHAR(255) NOT NULL
)");

// Si no existe cuota_general, se crea
$stmt = $pdo->prepare("SELECT valor FROM configuracion WHERE clave = 'cuota_general' LIMIT 1");
$stmt->execute();
if (!$stmt->fetch()) {
    $pdo->prepare("INSERT INTO configuracion (clave, valor) VALUES ('cuota_general', '1200')")->execute();
}

// Actualizar cuota general
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nueva_cuota'])) {
    $cuota = floatval($_POST['nueva_cuota']);
    $pdo->prepare("UPDATE configuracion SET valor = ? WHERE clave = 'cuota_general'")->execute([$cuota]);
    $success = "Cuota general actualizada a $" . number_format($cuota, 2);
}

// Obtener cuota actual
$stmt = $pdo->prepare("SELECT valor FROM configuracion WHERE clave = 'cuota_general' LIMIT 1");
$stmt->execute();
$cuota_actual = $stmt->fetchColumn();

// Crear columna descuento en alumnos si no existe
try {
    $pdo->query("SELECT descuento FROM alumnos LIMIT 1");
} catch (PDOException $e) {
    $pdo->exec("ALTER TABLE alumnos ADD COLUMN descuento DECIMAL(10,2) DEFAULT 0");
}

// Actualizar descuento
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['alumno_id']) && isset($_POST['descuento'])) {
    $alumno_id = intval($_POST['alumno_id']);
    $descuento = floatval($_POST['descuento']);
    $pdo->prepare("UPDATE alumnos SET descuento = ? WHERE id = ?")->execute([$descuento, $alumno_id]);
    $success = "Descuento actualizado para el alumno.";
}

// Listar alumnos y sus descuentos
$stmt = $pdo->prepare("SELECT id, nombre, descuento FROM alumnos ORDER BY nombre");
$stmt->execute();
$alumnos = $stmt->fetchAll();
?>

<div class="main-content">
    <h2 class="mb-4"><span class="mdi mdi-credit-card-settings"></span> Gestionar Pagos</h2>
    <?php if($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>
    <div class="row">
        <div class="col-md-5">
            <form class="card p-4 mb-4" method="POST">
                <h4 class="mb-3"><span class="mdi mdi-cash-multiple"></span> Cuota General</h4>
                <div class="mb-3">
                    <label for="nueva_cuota" class="form-label">Monto actual de la cuota</label>
                    <input type="number" step="0.01" min="0" class="form-control" id="nueva_cuota" name="nueva_cuota" value="<?= htmlspecialchars($cuota_actual) ?>" required>
                </div>
                <button type="submit" class="btn btn-primary"><span class="mdi mdi-check"></span> Actualizar cuota</button>
            </form>
        </div>
        <div class="col-md-7">
            <div class="card p-4 mb-4">
                <h4 class="mb-3"><span class="mdi mdi-account-cash"></span> Descuentos por Usuario</h4>
                <?php if (empty($alumnos)): ?>
                    <div class="alert alert-info">No hay alumnos registrados.</div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th>Nombre</th>
                                <th style="width:120px;">Descuento ($)</th>
                                <th style="width:110px;">Actualizar</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($alumnos as $alumno): ?>
                            <tr>
                                <form method="post">
                                    <td><?= htmlspecialchars($alumno['nombre']) ?></td>
                                    <td>
                                        <input type="number" step="0.01" min="0" class="form-control" name="descuento" value="<?= htmlspecialchars($alumno['descuento']) ?>" required>
                                    </td>
                                    <td>
                                        <input type="hidden" name="alumno_id" value="<?= $alumno['id'] ?>">
                                        <button type="submit" class="btn btn-success btn-sm"><span class="mdi mdi-content-save"></span> Guardar</button>
                                    </td>
                                </form>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php require "../layout/footer.php"; ?>