function fillUpdateForm(client) {
    document.getElementById('clientAction').value = 'update';
    document.getElementById('clientIdUsuario').value = client.id_usuario;
    document.getElementById('username').value = client.username;
    document.getElementById('password').value = client.password;
    document.getElementById('nombre').value = client.nombre;
    document.getElementById('apellido').value = client.apellido;
    document.getElementById('email').value = client.email;
}

function fillProductUpdateForm(product) {
    document.getElementById('productAction').value = 'update';
    document.getElementById('productIdProducto').value = product.id_producto;
    document.getElementById('nombre').value = product.nombre;
    document.getElementById('precio').value = product.precio;
    document.getElementById('cantidad_stock').value = product.cantidad_stock;
    document.getElementById('proveedor').value = product.proveedor;
}

function showOrderDetails(orderId) {
    fetch('manage_orders.php?action=get_details&id_pedido=' + orderId)
        .then(response => response.json())
        .then(data => {
            const table = document.getElementById('detailsTable');
            table.innerHTML = '<tr><th>Producto</th><th>Cantidad</th><th>Precio Unitario</th><th>Subtotal</th></tr>';
            data.forEach(item => {
                const row = `<tr>
                    <td>${item.nombre}</td>
                    <td>${item.cantidad}</td>
                    <td>${parseFloat(item.precio_unitario).toFixed(2)}</td>
                    <td>${parseFloat(item.precio_total_producto).toFixed(2)}</td>
                </tr>`;
                table.innerHTML += row;
            });
            document.getElementById('orderDetails').style.display = 'block';
        })
        .catch(error => alert('Error al obtener detalles: ' + error));
}
