<?php
session_start();
if (!isset($_SESSION['staff_id'])) header('Location: /login.php');
$title = "Solicitudes de Rutina";
require "../layout/header.php";
require "../layout/sidebar.php";

$config = require __DIR__ . '/../config/config.php';
$pdo = new PDO(
    "mysql:host={$config['db']['host']};dbname={$config['db']['name']};charset={$config['db']['charset']}",
    $config['db']['user'],
    $config['db']['pass']
);

// Cambiar estado de solicitud a 'atendida' si es solicitado (GET)
if (isset($_GET['atender']) && is_numeric($_GET['atender'])) {
    $id = intval($_GET['atender']);
    $pdo->prepare("UPDATE solicitudes SET estado='atendida' WHERE id=?")->execute([$id]);
    header("Location: solicitudes.php");
    exit;
}

// Obtener todas las solicitudes, orden más recientes primero
$sql = "SELECT s.*, a.nombre, a.telefono 
        FROM solicitudes s 
        JOIN alumnos a ON s.alumno_id=a.id
        ORDER BY s.fecha DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$solicitudes = $stmt->fetchAll();

function estadoBadge($estado) {
    switch($estado) {
        case 'pendiente': return '<span class="badge bg-warning text-dark">Pendiente</span>';
        case 'atendida': return '<span class="badge bg-success">Atendida</span>';
        default: return '<span class="badge bg-secondary">'.htmlspecialchars($estado).'</span>';
    }
}
function tipoTexto($tipo) {
    return $tipo === 'actualizacion' ? 'Actualización de rutina' : 'Nueva rutina';
}
?>
<div class="main-content">
    <h2 class="mb-4"><span class="mdi mdi-file-document-edit"></span> Solicitudes de Rutina</h2>
    <?php if (empty($solicitudes)): ?>
        <div class="alert alert-info">No hay solicitudes registradas.</div>
    <?php else: ?>
    <div class="table-responsive">
        <table class="table table-hover bg-white rounded shadow-sm align-middle">
            <thead class="table-light">
                <tr>
                    <th>Alumno</th>
                    <th>Teléfono</th>
                    <th>Tipo</th>
                    <th>Mensaje</th>
                    <th>Fecha</th>
                    <th>Estado</th>
                    <th style="width:110px;">Atender</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($solicitudes as $s): ?>
                <tr>
                    <td><?= htmlspecialchars($s['nombre']) ?></td>
                    <td><?= htmlspecialchars($s['telefono']) ?></td>
                    <td><?= tipoTexto($s['tipo']) ?></td>
                    <td><?= $s['mensaje'] ? nl2br(htmlspecialchars($s['mensaje'])) : '<span class="text-muted">Sin mensaje</span>' ?></td>
                    <td><?= date('d/m/Y H:i', strtotime($s['fecha'])) ?></td>
                    <td><?= estadoBadge($s['estado']) ?></td>
                    <td>
                        <?php if($s['estado']=='pendiente'): ?>
                            <a href="?atender=<?= $s['id'] ?>" class="btn btn-success btn-sm" onclick="return confirm('¿Marcar como atendida?');"><span class="mdi mdi-check"></span> Atendida</a>
                        <?php else: ?>
                            <span class="text-muted">—</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>
<?php require "../layout/footer.php"; ?>