<?php
session_start();
require_once 'conexion.php';

// Verificar que el usuario es administrador
if (!isset($_SESSION['user']) || $_SESSION['user']['rol'] !== 'admin') {
    header('Location: index.php');
    exit;
}

$success = null; // Variable para mensajes de éxito

// Procesar acciones CRUD
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['action'])) {
            if ($_POST['action'] === 'create') {
                $stmt = $pdo->prepare('CALL sp_crear_cliente(?, ?, ?, ?, ?)');
                $stmt->execute([
                    $_POST['username'],
                    $_POST['password'],
                    $_POST['nombre'],
                    $_POST['apellido'],
                    $_POST['email']
                ]);
                $success = 'Cliente creado exitosamente.'; // Mensaje de éxito
            } elseif ($_POST['action'] === 'update') {
                $stmt = $pdo->prepare('CALL sp_actualizar_cliente(?, ?, ?, ?, ?, ?)');
                $stmt->execute([
                    $_POST['id_usuario'],
                    $_POST['username'],
                    $_POST['password'],
                    $_POST['nombre'],
                    $_POST['apellido'],
                    $_POST['email']
                ]);
                $success = 'Cliente actualizado exitosamente.'; // Mensaje de éxito
            } elseif ($_POST['action'] === 'delete') {
                $stmt = $pdo->prepare('CALL sp_eliminar_cliente(?)');
                $stmt->execute([$_POST['id_usuario']]);
                $success = 'Cliente eliminado exitosamente.'; // Mensaje de éxito
            }
            // No redirigir con header('Location:') si queremos mostrar el mensaje de éxito
            // y luego recargar los clientes.
        }
    } catch (PDOException $e) {
        $error = 'Error al procesar la acción: ' . $e->getMessage();
    }
}

// Obtener todos los clientes (se ejecuta después de procesar el POST para reflejar cambios)
try {
    $stmt = $pdo->query('CALL sp_obtener_clientes()');
    $clients = $stmt->fetchAll();
    $stmt->closeCursor();
} catch (PDOException $e) {
    $error = 'Error al obtener clientes: ' . $e->getMessage();
}

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestionar Clientes</title>
    <link rel="stylesheet" href="styles/global.css">
    <link rel="stylesheet" href="styles/admin.css">
    <link rel="stylesheet" href="styles/manage_clients.css">
    <script src="scripts/management.js" defer></script>
    <script src="scripts/admin.js" defer></script>
</head>
<body class="admin-page">
    <div class="container">
        <h1>Gestionar Clientes</h1>
        <nav>
            <ul class="navbar">
                <li><a href="admin.php">Volver al Panel</a></li>
                <li><a onclick="openModalForCreate()" >Agregar Cliente</a></li>
            </ul>
        </nav>
        
        <?php if (isset($error)): ?>
            <p class="error"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>
        <?php if (isset($success)): ?>
            <p class="success"><?php echo htmlspecialchars($success); ?></p>
        <?php endif; ?>

        <div class="controls-bar">
            <input type="text" id="searchClients" placeholder="Buscar cliente por nombre, apellido, usuario o email..." class="search-bar">
        </div>
        
        <h2>Lista de Clientes</h2>
        <table>
            <thead> <tr>
                    <th>ID</th>
                    <th>Usuario</th>
                    <th>Nombre</th>
                    <th>Apellido</th>
                    <th>Email</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody> <?php foreach ($clients as $client): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($client['id_usuario']); ?></td>
                        <td><?php echo htmlspecialchars($client['username']); ?></td>
                        <td><?php echo htmlspecialchars($client['nombre']); ?></td>
                        <td><?php echo htmlspecialchars($client['apellido']); ?></td>
                        <td><?php echo htmlspecialchars($client['email']); ?></td>
                        <td class="actions"> <form method="POST" style="display:inline;">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id_usuario" value="<?php echo $client['id_usuario']; ?>">
                                <button type="submit" class="delete-btn" onclick="return confirm('¿Confirmar eliminación del cliente <?php echo htmlspecialchars($client['username']); ?>?');">Eliminar</button>
                            </form>
                            <button onclick="fillUpdateForm(<?php echo htmlspecialchars(json_encode($client)); ?>)" class="edit-btn">Editar</button> </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div id="clientModal" class="modal hidden">
            <div class="modal-content">
                <span class="close-button" onclick="closeModal()">&times;</span>
                <h2 id="modalTitle">Agregar/Editar Cliente</h2>
                <form method="POST" id="clientForm">
                    <input type="hidden" name="action" id="clientAction" value="create">
                    <input type="hidden" name="id_usuario" id="clientIdUsuario">
                    <div class="form-group">
                        <label for="username">Usuario:</label>
                        <input type="text" id="username" name="username" required>
                    </div>
                    <div class="form-group">
                        <label for="password">Contraseña:</label>
                        <input type="text" id="password" name="password" required>
                    </div>
                    <div class="form-group">
                        <label for="nombre">Nombre:</label>
                        <input type="text" id="nombre" name="nombre" required>
                    </div>
                    <div class="form-group">
                        <label for="apellido">Apellido:</label>
                        <input type="text" id="apellido" name="apellido" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Email:</label>
                        <input type="email" id="email" name="email" required>
                    </div>
                    <button type="submit">Guardar</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
