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
    // Modificado para ordenar por fecha en DESC para mostrar el más reciente primero
    $stmt = $pdo->prepare('SELECT id_pedido, fecha, descripcion, precio_total, estado FROM pedidos WHERE id_usuario = ? ORDER BY fecha DESC');
    $stmt->execute([$_SESSION['user']['id_usuario']]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stmt->closeCursor();
} catch (PDOException $e) {
    $error = 'Error al obtener pedidos: ' . $e->getMessage();
}

// Obtener detalles del pedido seleccionado (por defecto, el más reciente)
// Usamos 'fecha' en la URL para seleccionar el pedido, o el más reciente si no hay selección
$selected_order_id = $_GET['order_id'] ?? (!empty($orders) ? $orders[0]['id_pedido'] : null);
$order_details = [];
$order_status = null;
$order_description = null; // Para mostrar la descripción del pedido seleccionado
$order_total_price = null; // Para mostrar el precio total del pedido seleccionado

if ($selected_order_id) {
    try {
        // Obtener detalles del pedido
        $stmt = $pdo->prepare('CALL sp_obtener_detalles_pedido(?)');
        $stmt->execute([$selected_order_id]);
        $order_details = $stmt->fetchAll(PDO::FETCH_ASSOC); // Fetch as associative array
        $stmt->closeCursor();

        // Obtener estado, descripción y precio total del pedido seleccionado
        $stmt = $pdo->prepare('SELECT estado, descripcion, precio_total FROM pedidos WHERE id_pedido = ? AND id_usuario = ?');
        $stmt->execute([$selected_order_id, $_SESSION['user']['id_usuario']]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($order) {
            $order_status = $order['estado'];
            $order_description = $order['descripcion'];
            $order_total_price = $order['precio_total'];
        }
    } catch (PDOException $e) {
        $error = 'Error al obtener detalles: ' . $e->getMessage();
    }
}

// Manejar solicitud AJAX para detalles
if (isset($_GET['action']) && $_GET['action'] === 'get_details') {
    try {
        $id_pedido_ajax = $_GET['id_pedido'];
        $stmt = $pdo->prepare('CALL sp_obtener_detalles_pedido(?)');
        $stmt->execute([$id_pedido_ajax]);
        $details = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        // También obtenemos el estado, descripción y precio total para la respuesta AJAX
        $stmt = $pdo->prepare('SELECT estado, descripcion, precio_total FROM pedidos WHERE id_pedido = ? AND id_usuario = ?');
        $stmt->execute([$id_pedido_ajax, $_SESSION['user']['id_usuario']]);
        $order_info_ajax = $stmt->fetch(PDO::FETCH_ASSOC);
        
        header('Content-Type: application/json');
        echo json_encode(['details' => $details, 'info' => $order_info_ajax]);
        exit;
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
        exit;
    }
}

// Mapa de estados a íconos y tooltips
$status_map = [
    'pendiente' => ['icon' => '⏳', 'tooltip' => 'Pedido pendiente', 'class' => 'pendiente'],
    'armado' => ['icon' => '📦', 'tooltip' => 'Pedido armado', 'class' => 'armado'],
    'enviado' => ['icon' => '🚚', 'tooltip' => 'Pedido enviado', 'class' => 'enviado'],
    'cancelado' => ['icon' => '❌', 'tooltip' => 'Pedido cancelado', 'class' => 'cancelado']
];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Pedidos</title>
    <link rel="stylesheet" href="styles/global.css">
    <link rel="stylesheet" href="styles/client.css">

    <script>
        // statusMap se define aquí para que orders.js pueda accederlo
        window.statusMap = <?php echo json_encode($status_map); ?>;
        // El ID del pedido inicialmente seleccionado (el más reciente por defecto)
        window.initialSelectedOrderId = "<?php echo htmlspecialchars($selected_order_id); ?>";
    </script>
    <script src="scripts/orders.js" defer></script>
</head>
<body class="client-page">
    <div class="container orders-container">
        <h1>Mis Pedidos</h1>
        <nav>
            <ul class="navbar">
                <li><a href="client.php">Volver a Productos</a></li>
                <li><a href="logout.php">Cerrar Sesión</a></li>
            </ul>
        </nav>

        <?php if (isset($error)): ?>
            <p class="error"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>

        <div class="orders-layout">
            <div class="sidebar">
                <h2>Historial de Pedidos</h2>
                <ul class="order-list">
                    <?php if (!empty($orders)): ?>
                        <?php foreach ($orders as $order): ?>
                            <?php
                                // Obtener información del estado para este pedido
                                $current_order_status_info = $status_map[$order['estado']] ?? null;
                                $icon = $current_order_status_info['icon'] ?? '';
                                $class = $current_order_status_info['class'] ?? '';
                                $tooltip = $current_order_status_info['tooltip'] ?? '';
                            ?>
                            <li>
                                <a href="?order_id=<?php echo $order['id_pedido']; ?>" 
                                   onclick="showOrderDetails(<?php echo $order['id_pedido']; ?>, event)"
                                   class="<?php echo ($selected_order_id == $order['id_pedido']) ? 'active' : ''; ?>">
                                    #<?php echo htmlspecialchars($order['id_pedido']); ?>
                                    <span class="status-icon <?php echo htmlspecialchars($class); ?>" title="<?php echo htmlspecialchars($tooltip); ?>">
                                        <?php echo htmlspecialchars($icon); ?>
                                    </span>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="no-orders-msg">No tienes pedidos registrados.</p>
                    <?php endif; ?>
                </ul>
            </div>
            <div class="main-content">
                <div id="orderDetailsContent">
                    <?php if ($selected_order_id && !empty($order_details)): ?>
                        <h2>
                            Detalles del Pedido #<?php echo htmlspecialchars($selected_order_id); ?>
                            <span id="orderStatusIcon" class="status-icon <?php echo $status_map[$order_status]['class']; ?>" title="<?php echo $status_map[$order_status]['tooltip']; ?>">
                                <?php echo $status_map[$order_status]['icon']; ?>
                            </span>
                        </h2>
                        <p id="orderDescription">Descripción: <?php echo htmlspecialchars($order_description); ?></p>
                        <p id="orderTotalPrice">Precio Total: $<?php echo htmlspecialchars(number_format($order_total_price, 2)); ?></p>
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
                                    <td>$<?php echo htmlspecialchars(number_format($detail['precio_unitario'], 2)); ?></td>
                                    <td>$<?php echo htmlspecialchars(number_format($detail['precio_total_producto'], 2)); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </table>
                    <?php elseif (empty($orders)): ?>
                        <p class="initial-msg">No tienes pedidos registrados.</p>
                    <?php else: ?>
                        <p class="initial-msg">Selecciona un pedido para ver los detalles.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
