// assets/js/main.js
document.addEventListener('DOMContentLoaded', function() {
    // Toggle sidebar en móviles
    const menuToggle = document.createElement('div');
    menuToggle.className = 'menu-toggle';
    menuToggle.innerHTML = '<i class="fas fa-bars"></i>';
    menuToggle.addEventListener('click', function() {
        document.querySelector('.sidebar').classList.toggle('active');
    });
    
    const headerContainer = document.querySelector('.header-container');
    if (headerContainer) {
        headerContainer.insertBefore(menuToggle, headerContainer.firstChild);
    }
    
    // Confirmación antes de acciones importantes
    const deleteButtons = document.querySelectorAll('a[onclick*="confirm"]');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            if (!confirm(this.getAttribute('data-confirm') || '¿Estás seguro?')) {
                e.preventDefault();
            }
        });
    });
    
    // Actualizar edad de ovejas en tiempo real al editar fecha de nacimiento
    const fechaNacimiento = document.getElementById('fecha_nacimiento');
    if (fechaNacimiento) {
        fechaNacimiento.addEventListener('change', function() {
            const fecha = new Date(this.value);
            const hoy = new Date();
            const diffTime = hoy - fecha;
            const diffDays = Math.floor(diffTime / (1000 * 60 * 60 * 24));
            const diffMonths = Math.floor(diffDays / 30);
            const diffYears = Math.floor(diffDays / 365);
            
            document.getElementById('edad_dias').textContent = diffDays;
            document.getElementById('edad_meses').textContent = diffMonths;
            document.getElementById('edad_anos').textContent = diffYears;
        });
    }
});