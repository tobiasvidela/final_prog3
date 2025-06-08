<?php
session_start();
require_once 'conexion.php';

// Verificar que el usuario está autenticado y es cliente
if (!isset($_SESSION['user']) || $_SESSION['user']['rol'] !== 'cliente') {
    header('Location: index.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Cliente</title>
    <link rel="stylesheet" href="styles/global.css">
    <link rel="stylesheet" href="styles/client.css">
</head>
<body class="client-page">
    <div class="container client-bg">
        <h1>Bienvenido, <?php echo htmlspecialchars($_SESSION['user']['username']); ?>!</h1>
        <p>Esta es tu área personal para realizar pedidos.</p>
        <a href="index.php">Cerrar Sesión</a>
    </div>
</body>
</html>
