// Clientes 
function openModalForCreate() {
    document.getElementById('clientForm').reset();
    document.getElementById('clientAction').value = 'create';
    document.getElementById('modalTitle').textContent = 'Agregar Cliente';
    document.getElementById('clientModal').classList.remove('hidden');
}

function fillUpdateForm(client) {
    document.getElementById('clientAction').value = 'update';
    document.getElementById('modalTitle').textContent = 'Editar Cliente';
    document.getElementById('clientIdUsuario').value = client.id_usuario;
    document.getElementById('username').value = client.username;
    document.getElementById('password').value = client.password;
    document.getElementById('nombre').value = client.nombre;
    document.getElementById('apellido').value = client.apellido;
    document.getElementById('email').value = client.email;
    document.getElementById('clientModal').classList.remove('hidden');
}

function closeModal() {
    document.getElementById('clientModal').classList.add('hidden');
}

// Orders/Pedidos
function openOrderModal(order) {
    document.getElementById('modalIdPedido').value = order.id_pedido;
    document.getElementById('modalEstado').value = order.estado;
    document.getElementById('orderModal').classList.remove('hidden');
}

function closeOrderModal() {
    document.getElementById('orderModal').classList.add('hidden');
}

// Products
function openProductModalForCreate() {
    document.getElementById('productForm').reset();
    document.getElementById('productAction').value = 'create';
    document.getElementById('productModalTitle').textContent = 'Agregar Producto';
    document.getElementById('productModal').classList.remove('hidden');
}

function fillProductUpdateForm(product) {
    document.getElementById('productAction').value = 'update';
    document.getElementById('productIdProducto').value = product.id_producto;
    document.getElementById('nombre').value = product.nombre;
    document.getElementById('precio').value = product.precio;
    document.getElementById('cantidad_stock').value = product.cantidad_stock;
    document.getElementById('proveedor').value = product.proveedor;
    document.getElementById('productModalTitle').textContent = 'Editar Producto';
    document.getElementById('productModal').classList.remove('hidden');
}

function closeProductModal() {
    document.getElementById('productModal').classList.add('hidden');
}

