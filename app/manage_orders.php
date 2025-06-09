<?php
session_start();
require_once 'conexion.php';

// Verificar que el usuario es administrador
if (!isset($_SESSION['user']) || $_SESSION['user']['rol'] !== 'admin') {
    header('Location: index.php');
    exit;
}

// Obtener todos los pedidos
try {
    $stmt = $pdo->query("SELECT p.id_pedido, p.descripcion, p.estado, p.precio_total, p.fecha, c.nombre, c.apellido
                         FROM pedidos p
                         JOIN clientes c ON p.id_usuario = c.id_usuario
                         ORDER BY p.fecha DESC");
    $orders = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = 'Error al obtener pedidos: ' . $e->getMessage();
}

// Procesar acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['action'])) {
            if ($_POST['action'] === 'update_status') {
                $stmt = $pdo->prepare('CALL sp_actualizar_estado_pedido(?, ?)');
                $stmt->execute([$_POST['id_pedido'], $_POST['estado']]);
            } elseif ($_POST['action'] === 'delete') {
                $stmt = $pdo->prepare('DELETE FROM pedidos WHERE id_pedido = ?');
                $stmt->execute([$_POST['id_pedido']]);
            }
            header('Location: manage_orders.php');
            exit;
        }
    } catch (PDOException $e) {
        $error = 'Error al procesar la acción: ' . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestionar Pedidos</title>
    <link rel="stylesheet" href="styles/global.css">
    <link rel="stylesheet" href="styles/admin.css">
    <script src="scripts/management.js" defer></script>
</head>
<body class="admin-page">
    <div class="container">
        <h1>Gestionar Pedidos</h1>
        <a href="admin.php">Volver al Panel</a>
        <?php if (isset($error)): ?>
            <p class="error"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>
        <h2>Lista de Pedidos</h2>
        <table>
            <tr>
                <th>ID Pedido</th>
                <th>Cliente</th>
                <th>Descripción</th>
                <th>Estado</th>
                <th>Precio Total</th>
                <th>Fecha</th>
                <th>Acciones</th>
            </tr>
            <?php foreach ($orders as $order): ?>
                <tr>
                    <td><?php echo htmlspecialchars($order['id_pedido']); ?></td>
                    <td><?php echo htmlspecialchars($order['nombre'] . ' ' . $order['apellido']); ?></td>
                    <td><?php echo htmlspecialchars($order['descripcion']); ?></td>
                    <td>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="action" value="update_status">
                            <input type="hidden" name="id_pedido" value="<?php echo $order['id_pedido']; ?>">
                            <select name="estado" onchange="this.form.submit()">
                                <option value="pendiente" <?php echo $order['estado'] === 'pendiente' ? 'selected' : ''; ?>>Pendiente</option>
                                <option value="armado" <?php echo $order['estado'] === 'armado' ? 'selected' : ''; ?>>Armado</option>
                                <option value="enviado" <?php echo $order['estado'] === 'enviado' ? 'selected' : ''; ?>>Enviado</option>
                                <option value="cancelado" <?php echo $order['estado'] === 'cancelado' ? 'selected' : ''; ?>>Cancelado</option>
                            </select>
                        </form>
                    </td>
                    <td><?php echo htmlspecialchars(number_format($order['precio_total'], 2)); ?></td>
                    <td><?php echo htmlspecialchars($order['fecha']); ?></td>
                    <td>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id_pedido" value="<?php echo $order['id_pedido']; ?>">
                            <button type="submit" class="delete-btn" onclick="return confirm('¿Confirmar eliminación?');">Eliminar</button>
                        </form>
                        <button onclick="showOrderDetails(<?php echo $order['id_pedido']; ?>)">Ver Detalles</button>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
        <div id="orderDetails" style="display:none;">
            <h2>Detalles del Pedido</h2>
            <table id="detailsTable">
                <tr>
                    <th>Producto</th>
                    <th>Cantidad</th>
                    <th>Precio Unitario</th>
                    <th>Subtotal</th>
                </tr>
            </table>
        </div>
    </div>
</body>
</html>
