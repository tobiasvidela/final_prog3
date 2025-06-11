<?php
session_start();
require_once 'conexion.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    try {
        $stmt = $pdo->prepare('CALL sp_autenticar_usuario(?, ?)');
        $stmt->execute([$username, $password]);
        $user = $stmt->fetch();

        if ($user) {
            // Obtener id_usuario con una consulta adicional
            $stmt = $pdo->prepare('SELECT id_usuario FROM usuarios WHERE username = ?');
            $stmt->execute([$username]);
            $id_usuario = $stmt->fetchColumn();

            if ($id_usuario !== false) {
                // Almacenar datos del usuario en la sesión
                $_SESSION['user'] = [
                    'id_usuario' => $id_usuario,
                    'username' => $user['username'],
                    'rol' => $user['rol']
                ];

                // Redirigir según el rol
                if ($user['rol'] === 'cliente') {
                    header('Location: client.php');
                } elseif ($user['rol'] === 'admin') {
                    header('Location: admin.php');
                }
                exit;
            } else {
                $error = 'No se encontró el ID de usuario.';
            }
        } else {
            $error = 'Acceso inválido. Por favor, inténtelo otra vez.';
        }
    } catch (PDOException $e) {
        $error = 'Error de consulta: ' . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión</title>
    <link rel="stylesheet" href="styles/global.css">
    <script src="scripts/validations.js" defer></script>
</head>
<body>
    <div class="container">
        <h1>Iniciar Sesión</h1>
        <?php if (isset($error)): ?>
            <p class="error"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>
        <div class="form-container">
            <form id="loginForm" method="POST">
                <div class="form-group">
                    <label for="username">Usuario:</label>
                    <input type="text" id="username" name="username" required>
                </div>
                <div class="form-group">
                    <label for="password">Contraseña:</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <button type="submit">Iniciar Sesión</button>
            </form>
        </div>
    </div>
</body>
</html>
