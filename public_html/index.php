<?php
session_start();
if (isset($_SESSION['staff_id'])) {
    header('Location: dashboard.php');
} else {
    header('Location: login.php');
}
exit;