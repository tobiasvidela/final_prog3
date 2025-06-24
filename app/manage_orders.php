<?php
session_start();
require_once 'conexion.php';

// Verificar que el usuario es administrador
if (!isset($_SESSION['user']) || $_SESSION['user']['rol'] !== 'admin') {
    header('Location: index.php');
    exit;
}

// Procesar acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $action = $_POST['action'] ?? '';
        $id_pedido = $_POST['id_pedido'] ?? 0;

        if ($action === 'update_status' && !empty($_POST['estado'])) {
            $stmt = $pdo->prepare('CALL sp_actualizar_estado_pedido(?, ?)');
            $stmt->execute([$id_pedido, $_POST['estado']]);
            $success = 'Estado del pedido actualizado con éxito.';
        } elseif ($action === 'delete') {
            $pdo->beginTransaction();
            // Con ON DELETE CASCADE, no es necesario eliminar detalles manualmente
            $stmt = $pdo->prepare('DELETE FROM pedidos WHERE id_pedido = ?');
            $stmt->execute([$id_pedido]);
            $pdo->commit();
            $success = 'Pedido eliminado con éxito.';
        }
    } catch (PDOException $e) {
        $pdo->rollBack();
        $error = 'Error al procesar la acción: ' . $e->getMessage();
    }
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

// Mapa de estados a íconos y tooltips
$status_map = [
    'pendiente' => ['icon' => '⏳', 'tooltip' => 'Pedido pendiente'],
    'armado' => ['icon' => '📦', 'tooltip' => 'Pedido armado'],
    'enviado' => ['icon' => '🚚', 'tooltip' => 'Pedido enviado'],
    'cancelado' => ['icon' => '❌', 'tooltip' => 'Pedido cancelado']
];

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
    <script src="scripts/admin.js" defer></script>
</head>
<body class="admin-page">
    <div class="container">
        <h1>Gestionar Pedidos</h1>
        <a href="admin.php">Volver al Panel de Admin</a>
        <?php if (isset($error)): ?>
            <p class="error"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>
        <?php if (isset($success)): ?>
            <p class="success"><?php echo htmlspecialchars($success); ?></p>
        <?php endif; ?>
        <table>
            <tr>
                <th>ID Pedido</th>
                <th>Usuario</th>
                <th>Estado</th>
                <th>Descripción</th>
                <th>Precio Total</th>
                <th>Fecha</th>
                <th>Acciones</th>
            </tr>
            <?php foreach ($orders as $order): ?>
                <tr>
                    <td><?php echo htmlspecialchars($order['id_pedido']); ?></td>
                    <td><?php echo htmlspecialchars($order['nombre']); ?></td>
                    <td>
                        <span class="status-icon" title="<?php echo $status_map[$order['estado']]['tooltip']; ?>">
                            <?php echo $status_map[$order['estado']]['icon']; ?>
                        </span>
                    </td>
                    <td><?php echo htmlspecialchars($order['descripcion']); ?></td>
                    <td><?php echo htmlspecialchars(number_format($order['precio_total'], 2)); ?></td>
                    <td><?php echo htmlspecialchars($order['fecha']); ?></td>
                    <td>
                        <button onclick='openOrderModal(<?php echo json_encode([
                            "id_pedido" => $order["id_pedido"],
                            "estado" => $order["estado"]
                        ]); ?>)'>Actualizar</button>

                        <form method="POST" style="display:inline;" onsubmit="return confirm('¿Estás seguro de eliminar este pedido?');">
                            <input type="hidden" name="id_pedido" value="<?php echo $order['id_pedido']; ?>">
                            <input type="hidden" name="action" value="delete">
                            <button type="submit" class="delete-btn">Eliminar</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>
    <!-- Modal para actualizar estado -->
    <div id="orderModal" class="modal hidden">
        <div class="modal-content">
            <span class="close-button" onclick="closeOrderModal()">&times;</span>
            <h2>Actualizar Estado del Pedido</h2>
            <form method="POST">
                <input type="hidden" name="action" value="update_status">
                <input type="hidden" name="id_pedido" id="modalIdPedido">
                <label for="modalEstado">Estado:</label>
                <select name="estado" id="modalEstado" required>
                    <option value="pendiente">Pendiente</option>
                    <option value="armado">Armado</option>
                    <option value="enviado">Enviado</option>
                    <option value="cancelado">Cancelado</option>
                </select>
                <br><br>
                <button type="submit">Actualizar</button>
            </form>
        </div>
    </div>
</body>
</html>
