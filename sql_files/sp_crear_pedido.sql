DELIMITER //
CREATE PROCEDURE sp_crear_pedido (
    IN p_id_usuario INT,
    IN p_descripcion VARCHAR(255),
    IN p_productos JSON
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
    DECLARE v_valid TINYINT DEFAULT 1;
    DECLARE v_error_msg VARCHAR(255);

    -- Validar usuario
    IF NOT EXISTS (SELECT 1 FROM clientes WHERE id_usuario = p_id_usuario) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'El usuario no es un cliente válido';
    END IF;

    -- Validar JSON
    SET v_length = JSON_LENGTH(p_productos);
    IF v_length = 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'El pedido debe contener al menos un producto';
    END IF;

    START TRANSACTION;

    -- Validar todos los productos antes de realizar cambios
    WHILE v_index < v_length DO
        -- Extraer y convertir valores JSON
        SET v_id_producto = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_productos, CONCAT('$[', v_index, '].id_producto'))) AS SIGNED);
        SET v_cantidad = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_productos, CONCAT('$[', v_index, '].cantidad'))) AS SIGNED);

        -- Verificar si los valores son válidos
        IF v_id_producto IS NULL OR v_cantidad IS NULL THEN
            SET v_valid = 0;
            SET v_error_msg = CONCAT('Datos JSON inválidos en el índice ', v_index);
            LEAVE WHILE;
        END IF;

        -- Verificar existencia del producto
        IF NOT EXISTS (SELECT 1 FROM productos WHERE id_producto = v_id_producto) THEN
            SET v_valid = 0;
            SET v_error_msg = CONCAT('Producto con ID ', v_id_producto, ' no existe');
            LEAVE WHILE;
        END IF;

        -- Verificar cantidad y stock
        SELECT cantidad_stock, precio INTO v_stock, v_precio
        FROM productos
        WHERE id_producto = v_id_producto;

        IF v_cantidad <= 0 OR v_cantidad > v_stock THEN
            SET v_valid = 0;
            SET v_error_msg = CONCAT('Cantidad inválida o stock insuficiente para el producto con ID ', v_id_producto);
            LEAVE WHILE;
        END IF;

        SET v_index = v_index + 1;
    END WHILE;

    IF v_valid = 0 THEN
        ROLLBACK;
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = v_error_msg;
    END IF;

    -- Crear el pedido
    INSERT INTO pedidos (id_usuario, descripcion, precio_total)
    VALUES (p_id_usuario, p_descripcion, 0.0);
    SET v_id_pedido = LAST_INSERT_ID();

    -- Procesar productos
    SET v_index = 0;
    WHILE v_index < v_length DO
        SET v_id_producto = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_productos, CONCAT('$[', v_index, '].id_producto'))) AS SIGNED);
        SET v_cantidad = CAST(JSON_UNQUOTE(JSON_EXTRACT(p_productos, CONCAT('$[', v_index, '].cantidad'))) AS SIGNED);
        
        SELECT cantidad_stock, precio INTO v_stock, v_precio
        FROM productos
        WHERE id_producto = v_id_producto;
        
        -- Insertar detalle
        INSERT INTO detalles_pedidos (id_pedido, id_producto, cantidad, precio_unitario, precio_total_producto)
        VALUES (v_id_pedido, v_id_producto, v_cantidad, v_precio, v_precio * v_cantidad);
        
        -- Actualizar stock
        UPDATE productos
        SET cantidad_stock = cantidad_stock - v_cantidad
        WHERE id_producto = v_id_producto;
        
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
