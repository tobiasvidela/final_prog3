-- Datos iniciales: Usuarios
INSERT INTO usuarios (username, password, rol)
VALUES
    ('admin', 'admin123', 'admin'),
    ('cliente1', 'cliente123', 'cliente');

-- Datos iniciales: Clientes
INSERT INTO clientes (id_usuario, nombre, apellido, email)
VALUES
    ((SELECT id_usuario FROM usuarios WHERE username = 'cliente1'), 'Juan', 'Pérez', 'juan.perez@email.com');

-- Datos iniciales: Productos
INSERT INTO productos (nombre, precio, cantidad_stock, proveedor)
VALUES
    ('AJOS AGROECO X KG', 4000.00, 10, 'FLIA SOLORZANO'),
    ('MANZANAS GRANNY AGROECO X 13 KG', 19000.00, 20, 'FLIA ROSSO'),
    ('PAPAS BLANCAS ORG X 17 KG', 16000.00, 50, 'PUENTE BLANCO');

-- Datos iniciales: Pedidos de prueba
INSERT INTO pedidos (id_usuario, descripcion, precio_total, fecha)
VALUES
    ((SELECT id_usuario FROM usuarios WHERE username = 'cliente1'), 'Pedido de prueba 1', 1250.00, '2025-06-05 09:00:00'),
    ((SELECT id_usuario FROM usuarios WHERE username = 'cliente1'), 'Pedido de prueba 2', 650.00, '2025-06-05 09:30:00');

-- Datos iniciales: Detalles de pedidos
INSERT INTO detalles_pedidos (id_pedido, id_producto, cantidad, precio_unitario, precio_total_producto)
VALUES
    (1, 1, 5, 20000.00, 20000.00),
    (1, 3, 2, 32000.00, 32000.00),
    (2, 2, 1, 19000.00, 19000.00),
    (2, 3, 1, 16000.00, 16000.00);
