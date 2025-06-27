// scripts/orders.js

// Acceder a las variables globales definidas por PHP
const statusMap = window.statusMap || {};
const initialSelectedOrderId = window.initialSelectedOrderId || null;

function showOrderDetails(orderId, event) {
    if (event) {
        event.preventDefault(); // Evita que el enlace recargue la página
    }

    // Quitar clase 'active' de todos los enlaces y añadirla al clicado
    document.querySelectorAll('.sidebar ul li a').forEach(link => {
        link.classList.remove('active');
    });
    const currentLink = document.querySelector(`.sidebar ul li a[href*="order_id=${orderId}"]`);
    if (currentLink) {
        currentLink.classList.add('active');
    }

    fetch(`orders.php?action=get_details&id_pedido=${orderId}`)
        .then(response => {
            if (!response.ok) {
                return response.json().then(err => { 
                    throw new Error(err.error || 'Error desconocido al cargar los detalles del pedido.'); 
                }).catch(() => {
                    throw new Error(`Error de red o servidor al cargar detalles del pedido (HTTP ${response.status}).`);
                });
            }
            return response.json();
        })
        .then(data => {
            const detailsContentDiv = document.getElementById('orderDetailsContent');
            const orderDescriptionP = document.getElementById('orderDescription');
            const orderTotalPriceP = document.getElementById('orderTotalPrice');
            const detailsTable = document.getElementById('detailsTable');

            // --- INICIO DE CAMBIO CRÍTICO ---
            let h2Element = detailsContentDiv.querySelector('h2');
            if (!h2Element) {
                h2Element = document.createElement('h2');
                detailsContentDiv.prepend(h2Element);
            }

            // 1. Crear o asegurar la existencia del span del ícono
            // Si el span ya fue renderizado por PHP, lo obtenemos. Si no, lo creamos.
            let orderStatusSpan = h2Element.querySelector('#orderStatusIcon');
            if (!orderStatusSpan) {
                orderStatusSpan = document.createElement('span');
                orderStatusSpan.id = 'orderStatusIcon';
                orderStatusSpan.className = 'status-icon'; // Asegura la clase base
            }

            // 2. Actualizar solo el texto del H2, SIN borrar el span
            // Esto se logra manipulando los nodos hijos o el textContent antes de añadir el span.
            // La forma más simple y segura es vaciar el H2 y añadir el texto y luego el span.
            h2Element.innerHTML = ''; // Vacía el h2, pero lo hacemos temporalmente.
            h2Element.textContent = `Detalles del Pedido #${orderId}`; // Añade solo el texto

            // 3. Volver a añadir el span al H2 (si aún no está o fue borrado)
            h2Element.appendChild(orderStatusSpan);
            // --- FIN DE CAMBIO CRÍTICO ---


            // Actualizar descripción y precio total
            if (orderDescriptionP) { 
                if (data.info && data.info.descripcion) {
                    orderDescriptionP.textContent = `Descripción: ${data.info.descripcion}`;
                    orderDescriptionP.style.display = 'block'; 
                } else {
                    orderDescriptionP.style.display = 'none'; 
                }
            }
            
            if (orderTotalPriceP) { 
                if (data.info && data.info.precio_total) {
                    orderTotalPriceP.textContent = `Precio Total: $${parseFloat(data.info.precio_total).toFixed(2)}`;
                    orderTotalPriceP.style.display = 'block'; 
                } else {
                    orderTotalPriceP.style.display = 'none'; 
                }
            }

            // Actualizar icono de estado (esta parte ya estaba bien, pero ahora la referencia de orderStatusSpan es correcta)
            if (orderStatusSpan) {
                const fetchedEstado = data.info ? String(data.info.estado).trim().toLowerCase() : null; // Normalizar el estado

                if (fetchedEstado && statusMap[fetchedEstado]) {
                    const statusInfo = statusMap[fetchedEstado];
                    orderStatusSpan.innerHTML = statusInfo.icon;
                    orderStatusSpan.title = statusInfo.tooltip;
                    orderStatusSpan.className = 'status-icon'; // Reiniciar clases
                    if (statusInfo.class) {
                        orderStatusSpan.classList.add(statusInfo.class);
                    }
                    orderStatusSpan.style.display = 'inline-block';
                } else {
                    orderStatusSpan.style.display = 'none'; // Ocultar si no hay estado válido
                }
            }

            // Llenar la tabla de detalles
            if (detailsTable) { 
                let tableHTML = `
                    <tr>
                        <th>Producto</th>
                        <th>Cantidad</th>
                        <th>Precio Unitario</th>
                        <th>Subtotal</th>
                    </tr>
                `;
                if (data.details && data.details.length > 0) {
                    data.details.forEach(detail => {
                        tableHTML += `
                            <tr>
                                <td>${detail.nombre}</td>
                                <td>${detail.cantidad}</td>
                                <td>$${parseFloat(detail.precio_unitario).toFixed(2)}</td>
                                <td>$${parseFloat(detail.precio_total_producto).toFixed(2)}</td>
                            </tr>
                        `;
                    });
                } else {
                    tableHTML += `<tr><td colspan="4">No hay detalles para este pedido.</td></tr>`;
                }
                detailsTable.innerHTML = tableHTML;
            }
        })
        .catch(error => {
            console.error('Error en la carga de detalles del pedido:', error);
            alert('No se pudieron cargar los detalles del pedido: ' + error.message);
            const detailsContentDiv = document.getElementById('orderDetailsContent');
            if (detailsContentDiv) {
                detailsContentDiv.innerHTML = '<p class="error">Error al cargar los detalles del pedido. Por favor, intenta de nuevo. Detalles: ' + error.message + '</p>';
            }
        });
}

// Cargar los detalles del pedido inicial (el más reciente) y marcarlo como activo al cargar la página
document.addEventListener('DOMContentLoaded', () => {
    if (initialSelectedOrderId) {
        showOrderDetails(initialSelectedOrderId, null);
    } else {
        const detailsContentDiv = document.getElementById('orderDetailsContent');
        if (detailsContentDiv && detailsContentDiv.innerHTML.trim() === '') {
            detailsContentDiv.innerHTML = '<p class="initial-msg">Selecciona un pedido para ver los detalles.</p>';
        }
    }
});
