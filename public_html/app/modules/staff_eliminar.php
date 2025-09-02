<?php
session_start();
if (!isset($_SESSION['staff_id'])) header('Location: /login.php');
$id = $_GET['id'] ?? 0;
$config = require __DIR__ . '/../config/config.php';
$pdo = new PDO("mysql:host={$config['db']['host']};dbname={$config['db']['name']};charset={$config['db']['charset']}", $config['db']['user'], $config['db']['pass']);
$pdo->prepare("DELETE FROM staff WHERE id=?")->execute([$id]);
header('Location: staff.php');
exit;