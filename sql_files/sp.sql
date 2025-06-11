-- Stored Procedure: Autenticar usuario
DELIMITER //
CREATE PROCEDURE IF NOT EXISTS sp_autenticar_usuario (
    IN p_username VARCHAR(50),
    IN p_password VARCHAR(255)
)
BEGIN
    SELECT username, password, rol
    FROM usuarios
    WHERE username = p_username AND password = p_password;
END //
DELIMITER ;

-- Stored Procedure: Actualizar estado de pedido
DELIMITER //
CREATE PROCEDURE IF NOT EXISTS sp_actualizar_estado_pedido (
    IN p_id_pedido INT,
    IN p_estado ENUM('pendiente', 'armado', 'enviado', 'cancelado')
)
BEGIN
    UPDATE pedidos
    SET estado = p_estado
    WHERE id_pedido = p_id_pedido;
END //
DELIMITER ;

-- Stored Procedure: Obtener pedidos por cliente
DELIMITER //
CREATE PROCEDURE IF NOT EXISTS sp_obtener_pedidos_cliente (
    IN p_id_usuario INT
)
BEGIN
    SELECT id_pedido, estado, descripcion, precio_total, fecha
    FROM pedidos
    WHERE id_usuario = p_id_usuario
    ORDER BY fecha DESC;
END //
DELIMITER ;

-- Stored Procedure: Obtener detalles de un pedido
DELIMITER //
CREATE PROCEDURE IF NOT EXISTS sp_obtener_detalles_pedido (
    IN p_id_pedido INT
)
BEGIN
    SELECT p.nombre, dp.cantidad, dp.precio_unitario, dp.precio_total_producto
    FROM detalles_pedidos dp
    JOIN productos p ON dp.id_producto = p.id_producto
    WHERE dp.id_pedido = p_id_pedido;
END //
DELIMITER ;

-- Stored Procedure: Crear cliente
DELIMITER //
CREATE PROCEDURE IF NOT EXISTS sp_crear_cliente (
    IN p_username VARCHAR(50),
    IN p_password VARCHAR(255),
    IN p_nombre VARCHAR(50),
    IN p_apellido VARCHAR(50),
    IN p_email VARCHAR(100)
)
BEGIN
    DECLARE v_id_usuario INT;
    START TRANSACTION;
    INSERT INTO usuarios (username, password, rol)
    VALUES (p_username, p_password, 'cliente');
    SET v_id_usuario = LAST_INSERT_ID();
    INSERT INTO clientes (id_usuario, nombre, apellido, email)
    VALUES (v_id_usuario, p_nombre, p_apellido, p_email);
    COMMIT;
END //
DELIMITER ;

-- Stored Procedure: Actualizar cliente
DELIMITER //
CREATE PROCEDURE IF NOT EXISTS sp_actualizar_cliente (
    IN p_id_usuario INT,
    IN p_username VARCHAR(50),
    IN p_password VARCHAR(255),
    IN p_nombre VARCHAR(50),
    IN p_apellido VARCHAR(50),
    IN p_email VARCHAR(100)
)
BEGIN
    START TRANSACTION;
    UPDATE usuarios
    SET username = p_username, password = p_password
    WHERE id_usuario = p_id_usuario;
    UPDATE clientes
    SET nombre = p_nombre, apellido = p_apellido, email = p_email
    WHERE id_usuario = p_id_usuario;
    COMMIT;
END //
DELIMITER ;

-- Stored Procedure: Eliminar cliente
DELIMITER //
CREATE PROCEDURE IF NOT EXISTS sp_eliminar_cliente (
    IN p_id_usuario INT
)
BEGIN
    DELETE FROM usuarios WHERE id_usuario = p_id_usuario; -- CASCADE elimina el cliente
END //
DELIMITER ;

-- Stored Procedure: Obtener clientes
DELIMITER //
CREATE PROCEDURE IF NOT EXISTS sp_obtener_clientes ()
BEGIN
    SELECT u.id_usuario, u.username, u.password, c.nombre, c.apellido, c.email
    FROM usuarios u
    JOIN clientes c ON u.id_usuario = c.id_usuario
    WHERE u.rol = 'cliente';
END //
DELIMITER ;

-- Stored Procedure: Crear producto
DELIMITER //
CREATE PROCEDURE IF NOT EXISTS sp_crear_producto (
    IN p_nombre VARCHAR(100),
    IN p_precio DECIMAL(10, 2),
    IN p_cantidad_stock INT,
    IN p_proveedor VARCHAR(100)
)
BEGIN
    INSERT INTO productos (nombre, precio, cantidad_stock, proveedor)
    VALUES (p_nombre, p_precio, p_cantidad_stock, p_proveedor);
END //
DELIMITER ;

-- Stored Procedure: Actualizar producto
DELIMITER //
CREATE PROCEDURE IF NOT EXISTS sp_actualizar_producto (
    IN p_id_producto INT,
    IN p_nombre VARCHAR(100),
    IN p_precio DECIMAL(10, 2),
    IN p_cantidad_stock INT,
    IN p_proveedor VARCHAR(100)
)
BEGIN
    UPDATE productos
    SET nombre = p_nombre, precio = p_precio, cantidad_stock = p_cantidad_stock, proveedor = p_proveedor
    WHERE id_producto = p_id_producto;
END //
DELIMITER ;

-- Stored Procedure: Eliminar producto
DELIMITER //
CREATE PROCEDURE IF NOT EXISTS sp_eliminar_producto (
    IN p_id_producto INT
)
BEGIN
    DELETE FROM productos WHERE id_producto = p_id_producto;
END //
DELIMITER ;

-- Stored Procedure: Obtener productos
DELIMITER //
CREATE PROCEDURE IF NOT EXISTS sp_obtener_productos ()
BEGIN
    SELECT id_producto, nombre, precio, cantidad_stock, proveedor
    FROM productos;
END //
DELIMITER ;
