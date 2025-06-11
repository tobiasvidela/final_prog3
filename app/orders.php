<?php
session_start();
require_once 'conexion.php';

// Verificar que el usuario es cliente
if (!isset($_SESSION['user']) || $_SESSION['user']['rol'] !== 'cliente') {
    header('Location: index.php');
    exit;
}

// Obtener pedidos del cliente
try {
    $stmt = $pdo->prepare('CALL sp_obtener_pedidos_cliente(?)');
    $stmt->execute([$_SESSION['user']['id_usuario']]);
    $orders = $stmt->fetchAll();
    $stmt->closeCursor();
} catch (PDOException $e) {
    $error = 'Error al obtener pedidos: ' . $e->getMessage();
}

// Obtener detalles del pedido seleccionado (por defecto, el más reciente)
$selected_order_id = $_GET['order_id'] ?? (!empty($orders) ? $orders[0]['id_pedido'] : null);
$order_details = [];
if ($selected_order_id) {
    try {
        $stmt = $pdo->prepare('CALL sp_obtener_detalles_pedido(?)');
        $stmt->execute([$selected_order_id]);
        $order_details = $stmt->fetchAll();
        $stmt->closeCursor();
    } catch (PDOException $e) {
        $error = 'Error al obtener detalles: ' . $e->getMessage();
    }
}

// Manejar solicitud AJAX para detalles
if (isset($_GET['action']) && $_GET['action'] === 'get_details') {
    try {
        $stmt = $pdo->prepare('CALL sp_obtener_detalles_pedido(?)');
        $stmt->execute([$_GET['id_pedido']]);
        $details = $stmt->fetchAll();
        $stmt->closeCursor();
        header('Content-Type: application/json');
        echo json_encode($details);
        exit;
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Pedidos</title>
    <link rel="stylesheet" href="styles/global.css">
    <link rel="stylesheet" href="styles/client.css">
    <script src="scripts/management.js" defer></script>
</head>
<body class="client-page">
    <div class="container orders-container">
        <h1>Mis Pedidos</h1>
        <a href="client.php">Volver a Productos</a> | <a href="logout.php">Cerrar Sesión</a>
        <?php if (isset($error)): ?>
            <p class="error"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>
        <div class="orders-layout">
            <div class="sidebar">
                <h2>Selecciona un Pedido</h2>
                <ul>
                    <?php foreach ($orders as $order): ?>
                        <li>
                            <a href="?order_id=<?php echo $order['id_pedido']; ?>" onclick="showOrderDetails(<?php echo $order['id_pedido']; ?>, event)">
                                Pedido #<?php echo htmlspecialchars($order['id_pedido']); ?> - <?php echo htmlspecialchars($order['fecha']); ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <div class="main-content">
                <?php if ($selected_order_id && !empty($order_details)): ?>
                    <h2>Detalles del Pedido #<?php echo htmlspecialchars($selected_order_id); ?></h2>
                    <table id="detailsTable">
                        <tr>
                            <th>Producto</th>
                            <th>Cantidad</th>
                            <th>Precio Unitario</th>
                            <th>Subtotal</th>
                        </tr>
                        <?php foreach ($order_details as $detail): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($detail['nombre']); ?></td>
                                <td><?php echo htmlspecialchars($detail['cantidad']); ?></td>
                                <td><?php echo htmlspecialchars(number_format($detail['precio_unitario'], 2)); ?></td>
                                <td><?php echo htmlspecialchars(number_format($detail['precio_total_producto'], 2)); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                <?php elseif (empty($orders)): ?>
                    <p>No tienes pedidos registrados.</p>
                <?php else: ?>
                    <p>Selecciona un pedido para ver los detalles.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
