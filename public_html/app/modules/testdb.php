<?php
echo "<pre>=== TEST DEL SISTEMA ===\n";
$config = require __DIR__ . '/app/config/config.php';
try {
    $pdo = new PDO("mysql:host={$config['db']['host']};dbname={$config['db']['name']};charset={$config['db']['charset']}",
        $config['db']['user'], $config['db']['pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
    echo "✅ Conexión a base de datos exitosa\n";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>