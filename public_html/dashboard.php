<?php
session_start();
if (!isset($_SESSION['staff_id'])) header('Location: /login.php');
$title = "Dashboard";
require "app/layout/header.php";
require "app/layout/sidebar.php";
$config = require __DIR__ . '/app/config/config.php';

try {
    $pdo = new PDO(
        "mysql:host={$config['db']['host']};dbname={$config['db']['name']};charset={$config['db']['charset']}",
        $config['db']['user'],
        $config['db']['pass'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    // 1. Total alumnos activos
    $alumnos_activos = $pdo->query("SELECT COUNT(*) FROM alumnos WHERE estado='activo'")->fetchColumn();

    // 2. Total alumnos cuota al día
    $alumnos_cuota = $pdo->query("SELECT COUNT(*) FROM alumnos WHERE estado='cuota_al_dia'")->fetchColumn();

    // 3. Total alumnos pendientes
    $alumnos_pendientes = $pdo->query("SELECT COUNT(*) FROM alumnos WHERE estado='pendiente'")->fetchColumn();

    // 4. Clases activas
    $clases_activas = $pdo->query("SELECT COUNT(*) FROM clases WHERE estado='activa'")->fetchColumn();

    // 5. Clases finalizadas hoy
    $clases_finalizadas_hoy = $pdo->query("SELECT COUNT(*) FROM clases WHERE estado='finalizada' AND DATE(horario) = CURDATE()")->fetchColumn();

    // 6. Reservas activas
    $reservas_activas = $pdo->query("SELECT COUNT(*) FROM reservas WHERE estado='reservada'")->fetchColumn();

    // 7. Asistencias registradas hoy
    $asistencias_hoy = $pdo->query("
        SELECT COUNT(*) FROM reservas 
        WHERE estado='asistida' AND DATE(fecha_reserva) = CURDATE()
    ")->fetchColumn();

    // 8. Pagos realizados este mes
    $pagos_mes = $pdo->query("
        SELECT COUNT(*) FROM pagos
        WHERE MONTH(fecha_pago) = MONTH(CURDATE()) 
          AND YEAR(fecha_pago) = YEAR(CURDATE())
    ")->fetchColumn();

    // 9. Total pagado este mes
    $total_mes = $pdo->query("
        SELECT COALESCE(SUM(monto),0) FROM pagos
        WHERE MONTH(fecha_pago) = MONTH(CURDATE()) 
          AND YEAR(fecha_pago) = YEAR(CURDATE())
    ")->fetchColumn();

    // 10. Solicitudes pendientes
    $solicitudes_pendientes = $pdo->query("SELECT COUNT(*) FROM solicitudes WHERE estado='pendiente'")->fetchColumn();

    // 11. Solicitudes atendidas hoy
    $solicitudes_atendidas_hoy = $pdo->query("
        SELECT COUNT(*) FROM solicitudes 
        WHERE estado='atendida' AND DATE(fecha) = CURDATE()
    ")->fetchColumn();

    // 12. Staff registrados
    $staff_registrados = $pdo->query("SELECT COUNT(*) FROM staff")->fetchColumn();

    // 13. Últimas 5 reservas
    $ultimas_reservas = $pdo->query("
        SELECT r.*, a.nombre AS alumno, c.nombre AS clase
        FROM reservas r
        JOIN alumnos a ON a.id = r.alumno_id
        JOIN clases c ON c.id = r.clase_id
        ORDER BY r.fecha_reserva DESC
        LIMIT 5
    ")->fetchAll(PDO::FETCH_ASSOC);

    // 14. Últimos 5 pagos
    $ultimos_pagos = $pdo->query("
        SELECT p.*, a.nombre AS alumno
        FROM pagos p
        JOIN alumnos a ON a.id = p.alumno_id
        ORDER BY p.fecha_pago DESC, p.id DESC
        LIMIT 5
    ")->fetchAll(PDO::FETCH_ASSOC);

    // 15. Top alumnos deudores (sin pago del mes actual)
    $top_deudores = $pdo->query("
        SELECT a.id, a.nombre, a.telefono
        FROM alumnos a
        WHERE a.estado='activo'
          AND NOT EXISTS (
              SELECT 1 FROM pagos p
              WHERE p.alumno_id = a.id
                AND p.periodo = DATE_FORMAT(CURDATE(), '%Y-%m')
          )
        LIMIT 5
    ")->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    echo "<div class='alert alert-danger'>Error de conexión o consulta: " . htmlspecialchars($e->getMessage()) . "</div>";
}
?>
<div class="main-content">
    <div class="row mb-4">
        <div class="col">
            <h2>Dashboard principal</h2>
            <p class="text-muted">Panel de métricas generales del gimnasio</p>
        </div>
    </div>
    <div class="row g-4">
        <div class="col-md-3 col-6">
            <div class="card shadow-sm border-0 text-center">
                <div class="card-body">
                    <div class="fs-3 mb-2 text-success"><span class="mdi mdi-account-group"></span></div>
                    <div class="fs-4 fw-bold"><?= $alumnos_activos ?></div>
                    <div class="text-muted">Alumnos activos</div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card shadow-sm border-0 text-center">
                <div class="card-body">
                    <div class="fs-3 mb-2 text-info"><span class="mdi mdi-cash-check"></span></div>
                    <div class="fs-4 fw-bold"><?= $alumnos_cuota ?></div>
                    <div class="text-muted">Cuota al día</div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card shadow-sm border-0 text-center">
                <div class="card-body">
                    <div class="fs-3 mb-2 text-danger"><span class="mdi mdi-account-alert"></span></div>
                    <div class="fs-4 fw-bold"><?= $alumnos_pendientes ?></div>
                    <div class="text-muted">Alumnos pendientes</div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card shadow-sm border-0 text-center">
                <div class="card-body">
                    <div class="fs-3 mb-2 text-primary"><span class="mdi mdi-calendar-clock"></span></div>
                    <div class="fs-4 fw-bold"><?= $clases_activas ?></div>
                    <div class="text-muted">Clases activas</div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card shadow-sm border-0 text-center">
                <div class="card-body">
                    <div class="fs-3 mb-2 text-secondary"><span class="mdi mdi-calendar-check"></span></div>
                    <div class="fs-4 fw-bold"><?= $clases_finalizadas_hoy ?></div>
                    <div class="text-muted">Clases finalizadas hoy</div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card shadow-sm border-0 text-center">
                <div class="card-body">
                    <div class="fs-3 mb-2 text-warning"><span class="mdi mdi-calendar-star"></span></div>
                    <div class="fs-4 fw-bold"><?= $reservas_activas ?></div>
                    <div class="text-muted">Reservas activas</div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card shadow-sm border-0 text-center">
                <div class="card-body">
                    <div class="fs-3 mb-2 text-success"><span class="mdi mdi-calendar-check-outline"></span></div>
                    <div class="fs-4 fw-bold"><?= $asistencias_hoy ?></div>
                    <div class="text-muted">Asistencias hoy</div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card shadow-sm border-0 text-center">
                <div class="card-body">
                    <div class="fs-3 mb-2 text-info"><span class="mdi mdi-cash-multiple"></span></div>
                    <div class="fs-4 fw-bold"><?= $pagos_mes ?></div>
                    <div class="text-muted">Pagos este mes</div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card shadow-sm border-0 text-center">
                <div class="card-body">
                    <div class="fs-3 mb-2 text-success"><span class="mdi mdi-cash"></span></div>
                    <div class="fs-4 fw-bold">$<?= number_format($total_mes,2,',','.') ?></div>
                    <div class="text-muted">Total recaudado mes</div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card shadow-sm border-0 text-center">
                <div class="card-body">
                    <div class="fs-3 mb-2 text-warning"><span class="mdi mdi-email-alert"></span></div>
                    <div class="fs-4 fw-bold"><?= $solicitudes_pendientes ?></div>
                    <div class="text-muted">Solicitudes pendientes</div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card shadow-sm border-0 text-center">
                <div class="card-body">
                    <div class="fs-3 mb-2 text-secondary"><span class="mdi mdi-email-check"></span></div>
                    <div class="fs-4 fw-bold"><?= $solicitudes_atendidas_hoy ?></div>
                    <div class="text-muted">Solicitudes atendidas hoy</div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card shadow-sm border-0 text-center">
                <div class="card-body">
                    <div class="fs-3 mb-2 text-dark"><span class="mdi mdi-account-tie"></span></div>
                    <div class="fs-4 fw-bold"><?= $staff_registrados ?></div>
                    <div class="text-muted">Staff registrados</div>
                </div>
            </div>
        </div>
    </div>
    <hr class="my-4">
    <div class="row">
        <div class="col-md-6 mb-4">
            <h5 class="mb-3"><span class="mdi mdi-clock-outline"></span> Últimas reservas</h5>
            <div class="table-responsive">
            <table class="table table-sm table-hover table-bordered align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Alumno</th>
                        <th>Clase</th>
                        <th>Fecha</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach($ultimas_reservas as $r): ?>
                    <tr>
                        <td><?= htmlspecialchars($r['alumno']) ?></td>
                        <td><?= htmlspecialchars($r['clase']) ?></td>
                        <td><?= date('d/m/Y H:i', strtotime($r['fecha_reserva'])) ?></td>
                        <td>
                            <?php
                            $color = match($r['estado']) {
                                'reservada' => 'primary',
                                'asistida' => 'success',
                                'cancelada' => 'danger',
                                default => 'secondary'
                            };
                            ?>
                            <span class="badge bg-<?= $color ?>"><?= ucfirst($r['estado']) ?></span>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            </div>
        </div>
        <div class="col-md-6 mb-4">
            <h5 class="mb-3"><span class="mdi mdi-cash-multiple"></span> Últimos pagos</h5>
            <div class="table-responsive">
            <table class="table table-sm table-hover table-bordered align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Alumno</th>
                        <th>Monto</th>
                        <th>Método</th>
                        <th>Fecha</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach($ultimos_pagos as $p): ?>
                    <tr>
                        <td><?= htmlspecialchars($p['alumno']) ?></td>
                        <td>$<?= number_format($p['monto'],2,',','.') ?></td>
                        <td><?= htmlspecialchars($p['metodo']) ?></td>
                        <td><?= date('d/m/Y', strtotime($p['fecha_pago'])) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            </div>
        </div>
        <div class="col-md-6 mb-4">
            <h5 class="mb-3"><span class="mdi mdi-account-alert"></span> Top 5 deudores (sin pago este mes)</h5>
            <div class="table-responsive">
            <table class="table table-sm table-hover table-bordered align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Nombre</th>
                        <th>Teléfono</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach($top_deudores as $d): ?>
                    <tr>
                        <td><?= htmlspecialchars($d['nombre']) ?></td>
                        <td><?= htmlspecialchars($d['telefono']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            </div>
        </div>
    </div>
</div>
<?php require "app/layout/footer.php"; ?>