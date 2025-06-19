<?php
session_start();
require_once 'conexion.php';

// Verificar que el usuario es cliente
if (!isset($_SESSION['user']) || $_SESSION['user']['rol'] !== 'cliente') {
    header('Location: index.php');
    exit;
}

// Obtener nombre del cliente
try {
    $stmt = $pdo->prepare('SELECT nombre FROM clientes WHERE id_usuario = ?');
    $stmt->execute([$_SESSION['user']['id_usuario']]);
    $cliente = $stmt->fetch(PDO::FETCH_ASSOC);
    $nombre_cliente = $cliente ? htmlspecialchars($cliente['nombre']) : 'Cliente';
} catch (PDOException $e) {
    $error = 'Error al obtener nombre del cliente: ' . $e->getMessage();
    $nombre_cliente = 'Cliente';
}

// Función para crear un pedido
function createOrder($pdo, $id_usuario, $descripcion, $cart, $products) {
    try {
        // Validar usuario
        $stmt = $pdo->prepare('SELECT 1 FROM clientes WHERE id_usuario = ?');
        $stmt->execute([$id_usuario]);
        if (!$stmt->fetch()) {
            throw new Exception('Usuario no es un cliente válido.');
        }

        // Validar entrada
        if (empty($cart)) {
            throw new Exception('Selecciona al menos un producto.');
        }
        if (empty(trim($descripcion))) {
            throw new Exception('La descripción del pedido es requerida.');
        }

        // Mapear productos por ID para acceso rápido
        $product_map = [];
        foreach ($products as $product) {
            $product_map[$product['id_producto']] = $product;
        }

        // Calcular precio total y validar stock
        $precio_total = 0.0;
        $validated_cart = [];
        foreach ($cart as $item) {
            $id = $item['id'];
            $cantidad = $item['cantidad'];
            if (!isset($product_map[$id])) {
                continue; // Skip invalid products (shouldn't happen)
            }
            $product = $product_map[$id];
            if ($cantidad > $product['cantidad_stock']) {
                throw new Exception('Stock insuficiente para ' . htmlspecialchars($product['nombre']));
            }
            $precio_subtotal = $product['precio'] * $cantidad;
            $validated_cart[] = [
                'id' => $id,
                'cantidad' => $cantidad,
                'precio_unitario' => $product['precio'],
                'precio_subtotal' => $precio_subtotal
            ];
            $precio_total += $precio_subtotal;
        }

        // Iniciar transacción
        $pdo->beginTransaction();

        // Insertar pedido
        $stmt = $pdo->prepare('INSERT INTO pedidos (id_usuario, descripcion, precio_total) VALUES (?, ?, ?)');
        $stmt->execute([$id_usuario, $descripcion, $precio_total]);
        $id_pedido = $pdo->lastInsertId();

        // Insertar detalles y actualizar stock
        $stmt_detalle = $pdo->prepare('INSERT INTO detalles_pedidos (id_pedido, id_producto, cantidad, precio_unitario, precio_total_producto) VALUES (?, ?, ?, ?, ?)');
        $stmt_stock = $pdo->prepare('UPDATE productos SET cantidad_stock = cantidad_stock - ? WHERE id_producto = ?');
        foreach ($validated_cart as $item) {
            $stmt_detalle->execute([$id_pedido, $item['id'], $item['cantidad'], $item['precio_unitario'], $item['precio_subtotal']]);
            $stmt_stock->execute([$item['cantidad'], $item['id']]);
        }

        // Confirmar transacción
        $pdo->commit();
        return true;
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

// Obtener productos disponibles
try {
    $stmt = $pdo->query('CALL sp_obtener_productos()');
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stmt->closeCursor();
} catch (PDOException $e) {
    $error = 'Error al obtener productos: ' . $e->getMessage();
}

// Procesar creación de pedido
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_order'])) {
    try {
        $descripcion = trim($_POST['descripcion'] ?? '');
        $cart = [];

        // Construir carrito desde productos mostrados
        foreach ($products as $product) {
            $key = 'quantity_' . $product['id_producto'];
            if (isset($_POST[$key]) && is_numeric($_POST[$key])) {
                $quantity = (int)$_POST[$key];
                if ($quantity > 0) {
                    $cart[] = [
                        'id' => $product['id_producto'],
                        'cantidad' => $quantity
                    ];
                }
            }
        }

        if (empty($cart)) {
            $error = 'Selecciona al menos un producto.';
        } elseif (createOrder($pdo, $_SESSION['user']['id_usuario'], $descripcion, $cart, $products)) {
            $success = 'Pedido creado con éxito.';
        }
    } catch (Exception $e) {
        $error = 'Error al crear el pedido: ' . $e->getMessage();
    }
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
    <script src="scripts/validations.js" defer></script>
</head>
<body class="client-page">
    <div class="container">
        <h1>Hola, <?php echo htmlspecialchars($nombre_cliente); ?>!</h1>
        <nav>
            <ul>
                <li><a href="orders.php">Ver Mis Pedidos</a></li>
                <li><a href="logout.php">Cerrar Sesión</a></li>
            </ul>
        </nav>
        <h2>Productos Disponibles</h2>
        <?php if (isset($error)): ?>
            <p class="error"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>
        <?php if (isset($success)): ?>
            <p class="success"><?php echo htmlspecialchars($success); ?></p>
        <?php endif; ?>
        <form method="POST" id="orderForm">
            <div class="products-grid">
                <?php foreach ($products as $product): ?>
                    <div class="product-card">
                        <h3><?php echo htmlspecialchars($product['nombre']); ?></h3>
                        <p>Precio: $<?php echo htmlspecialchars(number_format($product['precio'], 2)); ?></p>
                        <p>Stock: <?php echo htmlspecialchars($product['cantidad_stock']); ?></p>
                        <?php echo "<label for=quantity_{$product['id_producto']}>Cantidad:</label>"; ?>
                        <?php echo "<input type='number' id='quantity_{$product['id_producto']}' name='quantity_{$product['id_producto']}' min='0' max='{$product['cantidad_stock']}' value='0'>"; ?>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="form-group">
                <label for="descripcion">Descripción del Pedido:</label>
                <textarea id="descripcion" name="descripcion" required></textarea>
            </div>
            <button type="submit" name="create_order">Crear Pedido</button>
        </form>
    </div>
</body>
</html>
