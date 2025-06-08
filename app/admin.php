<?php
session_start();
require_once 'conexion.php';

// Verificar que el usuario está autenticado y es administrador
if (!isset($_SESSION['user']) || $_SESSION['user']['rol'] !== 'admin') {
    header('Location: index.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administrador</title>
    <link rel="stylesheet" href="styles/global.css">
</head>
<body class="admin-page">
    <div class="container">
        <h1>Bienvenido, <?php echo htmlspecialchars($_SESSION['user']['username']); ?>!</h1>
        <p>Desde aquí puedes gestionar tus clientes y sus pedidos.</p>
        <a href="index.php">Cerrar Sesión</a>
    </div>
</body>
</html>
