// Clients/Clientes Searching behavior
document.addEventListener("DOMContentLoaded", () => {
    const input = document.getElementById("searchClients");
    if (!input) return;

    const rows = document.querySelectorAll("table tr:not(:first-child)");

    input.addEventListener("input", () => {
        const value = input.value.toLowerCase();

        rows.forEach(row => {
            const text = row.innerText.toLowerCase();
            const visible = text.includes(value);
            row.style.display = visible ? "" : "none";
        });
    });
});

// Products/Productos Searching behavior
document.addEventListener("DOMContentLoaded", () => {
    const searchInput = document.getElementById("searchProducts");
    const proveedorSelect = document.getElementById("filterProveedor");
    const rows = document.querySelectorAll("table tr:not(:first-child)");

    function filtrar() {
        const searchValue = searchInput.value.toLowerCase();
        const proveedorValue = proveedorSelect.value.toLowerCase();

        rows.forEach(row => {
            const nombre = row.cells[1].innerText.toLowerCase();
            const proveedor = row.cells[4].innerText.toLowerCase();

            const coincideNombre = nombre.includes(searchValue);
            const coincideProveedor = proveedorValue === "" || proveedor === proveedorValue;

            row.style.display = (coincideNombre && coincideProveedor) ? "" : "none";
        });
    }

    searchInput.addEventListener("input", filtrar);
    proveedorSelect.addEventListener("change", filtrar);
});

// Orders/Pedidos Searching behavior
document.addEventListener("DOMContentLoaded", function () {
    const searchInput = document.getElementById("searchInput");
    const estadoFilter = document.getElementById("estadoFilter");
    const fechaDesde = document.getElementById("fechaDesde");
    const fechaHasta = document.getElementById("fechaHasta");
    const rows = document.querySelectorAll("table tr:not(:first-child)");

    function aplicarFiltros() {
        const texto = searchInput.value.toLowerCase();
        const estado = estadoFilter.value;
        const desde = fechaDesde.value;
        const hasta = fechaHasta.value;

        rows.forEach(row => {
            const cells = row.querySelectorAll("td");
            const user = cells[1]?.innerText.toLowerCase();
            const descripcion = cells[3]?.innerText.toLowerCase();
            const fecha = cells[5]?.innerText;

            // Leer tooltip del icono de estado (en td[2] => el tercer td)
            const estadoTooltip = cells[2]?.querySelector('span')?.getAttribute('title')?.toLowerCase() || "";

            let visible = true;

            // Filtro por texto libre
            if (texto && !(`${user} ${descripcion}`.includes(texto))) {
                visible = false;
            }

            // Filtro por estado (comprobamos si el tooltip contiene la palabra exacta)
            const tooltipToEstado = {
                "pedido pendiente": "pendiente",
                "pedido armado": "armado",
                "pedido enviado": "enviado",
                "pedido cancelado": "cancelado"
            };

            const estadoDelPedido = tooltipToEstado[estadoTooltip] || "";

            if (estado && estado !== estadoDelPedido) {
                visible = false;
            }

            // Filtro por fecha
            if (fecha) {
                const fechaPedido = new Date(fecha);
                if (desde && new Date(desde) > fechaPedido) visible = false;
                if (hasta && new Date(hasta) < fechaPedido) visible = false;
            }

            row.style.display = visible ? "" : "none";
        });
    }

    searchInput.addEventListener("input", aplicarFiltros);
    estadoFilter.addEventListener("change", aplicarFiltros);
    fechaDesde.addEventListener("change", aplicarFiltros);
    fechaHasta.addEventListener("change", aplicarFiltros);
});
