-- Crear la base de datos
CREATE DATABASE IF NOT EXISTS db_final;
USE db_final;

-- Tabla de usuarios
CREATE TABLE usuarios (
    id_usuario INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    rol ENUM('admin', 'cliente') NOT NULL
);

-- Tabla de clientes (especialización de usuarios)
CREATE TABLE clientes (
    id_usuario INT PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL,
    apellido VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE CASCADE
);

-- Tabla de productos
CREATE TABLE productos (
    id_producto INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    precio DECIMAL(10, 2) NOT NULL,
    cantidad_stock INT NOT NULL,
    proveedor VARCHAR(100) NOT NULL
);

-- Tabla de pedidos
CREATE TABLE pedidos (
    id_pedido INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL,
    estado ENUM('pendiente', 'armado', 'enviado', 'cancelado') NOT NULL DEFAULT 'pendiente',
    descripcion VARCHAR(255) NOT NULL,
    precio_total DECIMAL(10, 2) NOT NULL DEFAULT 0.0,
    fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_usuario) REFERENCES clientes(id_usuario)
);

-- Tabla de detalles de pedidos
CREATE TABLE detalles_pedidos (
    id_detalle INT AUTO_INCREMENT PRIMARY KEY,
    id_pedido INT NOT NULL,
    id_producto INT NOT NULL,
    cantidad INT NOT NULL,
    precio_unitario DECIMAL(10, 2) NOT NULL,
    precio_total_producto DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (id_pedido) REFERENCES pedidos(id_pedido),
    FOREIGN KEY (id_producto) REFERENCES productos(id_producto)
);

-- Stored Procedure: Autenticar usuario
DELIMITER //
CREATE PROCEDURE sp_autenticar_usuario (
    IN p_username VARCHAR(50),
    IN p_password VARCHAR(255)
)
BEGIN
    SELECT username, password, rol
    FROM usuarios
    WHERE username = p_username AND password = p_password;
END //
DELIMITER ;

-- Stored Procedure: Crear pedido
DELIMITER //
CREATE PROCEDURE sp_crear_pedido (
    IN p_id_usuario INT,
    IN p_descripcion VARCHAR(255),
    IN p_productos JSON -- Lista de productos: [{"id_producto": INT, "cantidad": INT}, ...]
)
BEGIN
    DECLARE v_precio_total DECIMAL(10, 2) DEFAULT 0.0;
    DECLARE v_id_pedido INT;
    DECLARE v_id_producto INT;
    DECLARE v_cantidad INT;
    DECLARE v_precio DECIMAL(10, 2);
    DECLARE v_stock INT;
    DECLARE v_index INT DEFAULT 0;
    DECLARE v_length INT;

    -- Iniciar transacción
    START TRANSACTION;

    -- Verificar que el usuario es un cliente
    IF NOT EXISTS (SELECT 1 FROM clientes WHERE id_usuario = p_id_usuario) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'El usuario no es un cliente válido';
    END IF;

    -- Insertar pedido
    INSERT INTO pedidos (id_usuario, descripcion, precio_total)
    VALUES (p_id_usuario, p_descripcion, 0.0);
    SET v_id_pedido = LAST_INSERT_ID();

    -- Procesar productos desde JSON
    SET v_length = JSON_LENGTH(p_productos);
    WHILE v_index < v_length DO
        SET v_id_producto = JSON_UNQUOTE(JSON_EXTRACT(p_productos, CONCAT('$[', v_index, '].id_producto')));
        SET v_cantidad = JSON_UNQUOTE(JSON_EXTRACT(p_productos, CONCAT('$[', v_index, '].cantidad')));
        
        -- Verificar stock
        SELECT cantidad_stock, precio INTO v_stock, v_precio
        FROM productos
        WHERE id_producto = v_id_producto;
        
        IF v_cantidad <= 0 OR v_cantidad > v_stock THEN
            ROLLBACK;
            SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = CONCAT('Stock insuficiente para el producto con ID ', v_id_producto);
        END IF;
        
        -- Insertar detalle del pedido
        INSERT INTO detalles_pedidos (id_pedido, id_producto, cantidad, precio_unitario, precio_total_producto)
        VALUES (v_id_pedido, v_id_producto, v_cantidad, v_precio, v_precio * v_cantidad);
        
        -- Actualizar stock
        UPDATE productos
        SET cantidad_stock = cantidad_stock - v_cantidad
        WHERE id_producto = v_id_producto;
        
        -- Sumar al precio total
        SET v_precio_total = v_precio_total + (v_precio * v_cantidad);
        
        SET v_index = v_index + 1;
    END WHILE;

    -- Actualizar precio total del pedido
    UPDATE pedidos
    SET precio_total = v_precio_total
    WHERE id_pedido = v_id_pedido;

    COMMIT;
END //
DELIMITER ;

-- Stored Procedure: Actualizar estado de pedido
DELIMITER //
CREATE PROCEDURE sp_actualizar_estado_pedido (
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
CREATE PROCEDURE sp_obtener_pedidos_cliente (
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
CREATE PROCEDURE sp_obtener_detalles_pedido (
    IN p_id_pedido INT
)
BEGIN
    SELECT p.nombre, dp.cantidad, dp.precio_unitario, dp.precio_total_producto
    FROM detalles_pedidos dp
    JOIN productos p ON dp.id_producto = p.id_producto
    WHERE dp.id_pedido = p_id_pedido;
END //
DELIMITER ;
