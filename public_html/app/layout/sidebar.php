<div class="sidebar" id="sidebar">
    <h4 class="mb-4 text-center fw-bold"><span class="mdi mdi-dumbbell"></span> ZetraStudio</h4>
    <a href="/dashboard.php" class="nav-link<?= basename($_SERVER['PHP_SELF'])=='dashboard.php'?' active':'' ?>"><span class="mdi mdi-view-dashboard"></span><span>Inicio</span></a>
    <a href="/app/modules/alumnos.php" class="nav-link<?= basename($_SERVER['PHP_SELF'])=='alumnos.php'?' active':'' ?>"><span class="mdi mdi-account-group"></span><span>Alumnos</span></a>
    <a href="/app/modules/rutinas.php" class="nav-link<?= basename($_SERVER['PHP_SELF'])=='rutinas.php'?' active':'' ?>"><span class="mdi mdi-run"></span><span>Rutinas</span></a>
    <a href="/app/modules/clases.php" class="nav-link<?= basename($_SERVER['PHP_SELF'])=='clases.php'?' active':'' ?>"><span class="mdi mdi-calendar-multiselect"></span><span>Clases</span></a>
    <a href="/app/modules/reservas.php" class="nav-link<?= basename($_SERVER['PHP_SELF'])=='reservas.php'?' active':'' ?>"><span class="mdi mdi-calendar-check"></span><span>Reservas</span></a>
    <a href="/app/modules/solicitudes.php" class="nav-link<?= basename($_SERVER['PHP_SELF'])=='solicitudes.php'?' active':'' ?>"><span class="mdi mdi-file-document-edit"></span><span>Solicitudes</span></a>
    <a href="/app/modules/pagos.php" class="nav-link<?= basename($_SERVER['PHP_SELF'])=='pagos.php'?' active':'' ?>"><span class="mdi mdi-credit-card"></span><span>Pagos</span></a>
    <a href="/app/modules/gestionar_pagos.php" class="nav-link<?= strpos($_SERVER['PHP_SELF'], 'gestionar_pagos.php')!==false ? ' active' : '' ?>">
        <span class="mdi mdi-credit-card-settings"></span>
        <span>Administracion</span>
    <a href="/app/modules/staff.php" class="nav-link<?= basename($_SERVER['PHP_SELF'])=='staff.php'?' active':'' ?>"><span class="mdi mdi-account-tie"></span><span>Staff</span></a>
    <a href="/logout.php" class="nav-link mt-auto"><span class="mdi mdi-logout"></span><span>Salir</span></a>
    

</div>