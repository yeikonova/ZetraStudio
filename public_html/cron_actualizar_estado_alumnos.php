<?php
// CRON para actualizar automáticamente el estado de todos los alumnos según pagos del mes actual
$config = require __DIR__ . '/../config/config.php';
$pdo = new PDO(
    "mysql:host={$config['db']['host']};dbname={$config['db']['name']};charset={$config['db']['charset']}",
    $config['db']['user'],
    $config['db']['pass']
);

$alumnos = $pdo->query("SELECT id FROM alumnos")->fetchAll(PDO::FETCH_COLUMN);
$periodo_actual = date('Y-m');

foreach ($alumnos as $alumno_id) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM pagos WHERE alumno_id=? AND periodo=?");
    $stmt->execute([$alumno_id, $periodo_actual]);
    $al_dia = $stmt->fetchColumn() > 0;
    if ($al_dia) {
        $pdo->prepare("UPDATE alumnos SET estado='cuota_al_dia' WHERE id=?")->execute([$alumno_id]);
    } else {
        $pdo->prepare("UPDATE alumnos SET estado='pendiente' WHERE id=?")->execute([$alumno_id]);
    }
}
echo "Estados actualizados: " . date('Y-m-d H:i:s') . "\n";