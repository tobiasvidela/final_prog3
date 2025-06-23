<?php
session_start();
require_once 'conexion.php';

// Verificar que el usuario es administrador
if (!isset($_SESSION['user']) || $_SESSION['user']['rol'] !== 'admin') {
    header('Location: index.php');
    exit;
}

// Obtener pedidos pendientes
try {
    $stmt = $pdo->query("SELECT p.id_pedido, p.descripcion, p.precio_total, p.fecha, c.nombre, c.apellido
                         FROM pedidos p
                         JOIN clientes c ON p.id_usuario = c.id_usuario
                         WHERE p.estado = 'pendiente'
                         ORDER BY p.fecha DESC");
    $pending_orders = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = 'Error al obtener pedidos: ' . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administrador</title>
    <link rel="stylesheet" href="styles/global.css">
    <link rel="stylesheet" href="styles/admin.css">
</head>
<body class="admin-page">
    <div class="container">
        <h1>Bienvenido, Administrador!</h1>
        <nav>
            <ul>
                <li><a href="manage_clients.php">Gestionar Clientes</a></li>
                <li><a href="manage_orders.php">Gestionar Pedidos</a></li>
                <li><a href="manage_products.php">Gestionar Productos</a></li>
                <li><a href="logout.php">Cerrar Sesión</a></li>
            </ul>
        </nav>
        <h2>Pedidos Pendientes</h2>
        <p>[cambiar formularios a modales]</p>
        <p>[agregar filtros de búsqueda]</p>
        <p>[mejorar css]</p>
        <?php if (isset($error)): ?>
            <p class="error"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>
        <?php if (empty($pending_orders)): ?>
            <p>No hay pedidos pendientes.</p>
        <?php else: ?>
            <table>
                <tr>
                    <th>ID Pedido</th>
                    <th>Cliente</th>
                    <th>Descripción</th>
                    <th>Precio Total</th>
                    <th>Fecha</th>
                </tr>
                <?php foreach ($pending_orders as $order): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($order['id_pedido']); ?></td>
                        <td><?php echo htmlspecialchars($order['nombre'] . ' ' . $order['apellido']); ?></td>
                        <td><?php echo htmlspecialchars($order['descripcion']); ?></td>
                        <td><?php echo htmlspecialchars(number_format($order['precio_total'], 2)); ?></td>
                        <td><?php echo htmlspecialchars($order['fecha']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        <?php endif; ?>
    </div>
</body>
</html>
