document.getElementById('loginForm').addEventListener('submit', function(event) {
    const username = document.getElementById('username').value.trim();
    const password = document.getElementById('password').value.trim();

    if (!username || !password) {
        event.preventDefault();
        alert('Por favor, completa todos los campos.');
    }
});

document.getElementById('orderForm')?.addEventListener('submit', function(event) {
    const quantities = document.querySelectorAll('input[name^="quantity_"]');
    let hasSelection = false;
    quantities.forEach(input => {
        if (parseInt(input.value) > 0) hasSelection = true;
    });
    const descripcion = document.getElementById('descripcion')?.value.trim();
    if (!hasSelection || !descripcion) {
        event.preventDefault();
        alert('Selecciona al menos un producto y proporciona una descripción.');
    }
});
