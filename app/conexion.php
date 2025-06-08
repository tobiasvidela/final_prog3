<?php
try {
    $dsn = 'mysql:host=db;dbname=db_final;charset=utf8';
    $pdo = new PDO($dsn, 'appuser', 'appuserpassword', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    die('Error de conexión a la base de datos: ' . $e->getMessage());
}
?>
