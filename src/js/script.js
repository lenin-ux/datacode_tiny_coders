document.addEventListener('DOMContentLoaded', function () {

    const formLogin = document.getElementById('formLogin');

    if (formLogin) {
        formLogin.addEventListener('submit', function (e) {
            e.preventDefault();

            const errorDiv = document.getElementById('loginError');
            errorDiv.style.display = 'none';

            const formData = new FormData(formLogin);

            fetch('/api/login.php', {   // <- ruta absoluta
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    errorDiv.textContent = data.message;
                    errorDiv.style.display = 'block';
                }
            })
            .catch(() => {
                errorDiv.textContent = 'Error de conexión con el servidor';
                errorDiv.style.display = 'block';
            });
        });
    }

    const btnLogout = document.getElementById('btnLogout');
    if (btnLogout) {
        btnLogout.addEventListener('click', function () {
            fetch('/api/logout.php').then(() => location.reload());  // <- ruta absoluta
        });
    }

});