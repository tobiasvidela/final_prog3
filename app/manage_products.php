<?php
session_start();
require_once 'conexion.php';

// Verificar que el usuario es administrador
if (!isset($_SESSION['user']) || $_SESSION['user']['rol'] !== 'admin') {
    header('Location: index.php');
    exit;
}

// Obtener todos los productos
try {
    $stmt = $pdo->query('CALL sp_obtener_productos()');
    $products = $stmt->fetchAll();
    $stmt->closeCursor();
} catch (PDOException $e) {
    $error = 'Error al obtener productos: ' . $e->getMessage();
}

// Procesar acciones CRUD
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['action'])) {
            if ($_POST['action'] === 'create') {
                $stmt = $pdo->prepare('CALL sp_crear_producto(?, ?, ?, ?)');
                $stmt->execute([
                    $_POST['nombre'],
                    $_POST['precio'],
                    $_POST['cantidad_stock'],
                    $_POST['proveedor']
                ]);
            } elseif ($_POST['action'] === 'update') {
                $stmt = $pdo->prepare('CALL sp_actualizar_producto(?, ?, ?, ?, ?)');
                $stmt->execute([
                    $_POST['id_producto'],
                    $_POST['nombre'],
                    $_POST['precio'],
                    $_POST['cantidad_stock'],
                    $_POST['proveedor']
                ]);
            } elseif ($_POST['action'] === 'delete') {
                $stmt = $pdo->prepare('CALL sp_eliminar_producto(?)');
                $stmt->execute([$_POST['id_producto']]);
            }
            header('Location: manage_products.php');
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
    <title>Gestionar Productos</title>
    <link rel="stylesheet" href="styles/global.css">
    <link rel="stylesheet" href="styles/admin.css">
    <script src="scripts/management.js" defer></script>
</head>
<body class="admin-page">
    <div class="container">
        <h1>Gestionar Productos</h1>
        <a href="admin.php">Volver al Panel</a>
        <?php if (isset($error)): ?>
            <p class="error"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>
        <!-- Botón para abrir el modal de creación -->
        <button onclick="openProductModalForCreate()">Agregar Producto</button>
        <h2>Lista de Productos</h2>
        <table>
            <tr>
                <th>ID</th>
                <th>Nombre</th>
                <th>Precio</th>
                <th>Stock</th>
                <th>Proveedor</th>
                <th>Acciones</th>
            </tr>
            <?php foreach ($products as $product): ?>
                <tr>
                    <td><?php echo htmlspecialchars($product['id_producto']); ?></td>
                    <td><?php echo htmlspecialchars($product['nombre']); ?></td>
                    <td><?php echo htmlspecialchars(number_format($product['precio'], 2)); ?></td>
                    <td><?php echo htmlspecialchars($product['cantidad_stock']); ?></td>
                    <td><?php echo htmlspecialchars($product['proveedor']); ?></td>
                    <td>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id_producto" value="<?php echo $product['id_producto']; ?>">
                            <button type="submit" class="delete-btn" onclick="return confirm('¿Confirmar eliminación?');">Eliminar</button>
                        </form>
                        <button onclick='fillProductUpdateForm(<?php echo json_encode($product); ?>)'>Editar</button>
                        <!-- <button onclick="fillProductUpdateForm(<?php echo htmlspecialchars(json_encode($product)); ?>)">Editar</button> -->
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
        <!-- Modal -->
        <div id="productModal" class="modal hidden">
            <div class="modal-content">
                <span class="close-button" onclick="closeProductModal()">&times;</span>
                <h2 id="productModalTitle">Agregar/Editar Producto</h2>
                <form method="POST" id="productForm">
                    <input type="hidden" name="action" id="productAction" value="create">
                    <input type="hidden" name="id_producto" id="productIdProducto">
                    <div class="form-group">
                        <label for="nombre">Nombre:</label>
                        <input type="text" id="nombre" name="nombre" required>
                    </div>
                    <div class="form-group">
                        <label for="precio">Precio:</label>
                        <input type="number" id="precio" name="precio" step="0.01" required>
                    </div>
                    <div class="form-group">
                        <label for="cantidad_stock">Stock:</label>
                        <input type="number" id="cantidad_stock" name="cantidad_stock" required>
                    </div>
                    <div class="form-group">
                        <label for="proveedor">Proveedor:</label>
                        <input type="text" id="proveedor" name="proveedor" required>
                    </div>
                    <button type="submit">Guardar</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
