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

    // ------------------------------------------------------------
    // Validacion visual de formularios (Bootstrap): a cualquier form
    // que se envie por POST (excepto el login, que ya tiene su propio
    // manejo) se le aplica la retroalimentacion visual estandar de
    // Bootstrap (bordes rojos/verdes) si el navegador detecta campos
    // invalidos, sin cambiar el comportamiento normal del formulario.
    // ------------------------------------------------------------
    document.querySelectorAll('form[method="POST"], form[method="post"]').forEach(function (form) {
        if (form.id === 'formLogin' || form.hasAttribute('data-ajax-votar') || form.hasAttribute('data-ajax-comentar')) {
            return;
        }
        form.setAttribute('novalidate', 'novalidate');
        form.addEventListener('submit', function (e) {
            if (!form.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
            }
            form.classList.add('was-validated');
        });
    });

    // ------------------------------------------------------------
    // Votar por una propuesta sin recargar la pagina (AJAX).
    // ------------------------------------------------------------
    document.querySelectorAll('form[data-ajax-votar]').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();

            const boton = document.getElementById('btnVotar');
            const texto = document.getElementById('textoVotar');
            if (boton) boton.disabled = true;

            fetch(form.getAttribute('action') || window.location.href, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: new FormData(form)
            })
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    const numVotosSpan = document.getElementById('numVotos');
                    if (numVotosSpan) numVotosSpan.textContent = data.numVotos;
                    if (texto) texto.textContent = data.yaVote ? 'Ya votaste' : 'Votar por esta propuesta';
                    if (boton) boton.disabled = data.yaVote;
                    if (!data.success && data.message) {
                        alert(data.message);
                    }
                })
                .catch(function () {
                    if (boton) boton.disabled = false;
                    alert('No se pudo registrar tu voto, intenta de nuevo.');
                });
        });
    });

    // ------------------------------------------------------------
    // Comentar una propuesta sin recargar la pagina (AJAX).
    // ------------------------------------------------------------
    document.querySelectorAll('form[data-ajax-comentar]').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();

            const textarea = document.getElementById('textoComentarioInput');
            const lista = document.getElementById('listaComentarios');
            const sinComentarios = document.getElementById('sinComentarios');

            fetch(form.getAttribute('action') || window.location.href, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: new FormData(form)
            })
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    if (data.success && data.comentario) {
                        const div = document.createElement('div');
                        div.className = 'border-bottom pb-2 mb-2';
                        const p = document.createElement('p');
                        p.className = 'mb-0';
                        p.textContent = data.comentario;
                        div.appendChild(p);
                        if (lista) lista.appendChild(div);
                        if (sinComentarios) sinComentarios.hidden = true;
                        if (textarea) textarea.value = '';
                    } else if (data.message) {
                        alert(data.message);
                    }
                })
                .catch(function () {
                    alert('No se pudo enviar tu comentario, intenta de nuevo.');
                });
        });
    });

    // ------------------------------------------------------------
    // Listados imprimibles: permite elegir un solo listado (Talleres,
    // Torneos, Hackathones o Eventos) en vez de imprimir todo junto.
    // Oculta las demas secciones solo para la impresion (con la clase
    // d-print-none de Bootstrap) y las vuelve a mostrar despues.
    // ------------------------------------------------------------
    window.imprimirListado = function (seccion) {
        var secciones = document.querySelectorAll('.listado-seccion');
        secciones.forEach(function (s) {
            if (seccion !== 'todo' && s.getAttribute('data-seccion') !== seccion) {
                s.classList.add('d-print-none');
            }
        });
        window.print();
    };

    window.addEventListener('afterprint', function () {
        document.querySelectorAll('.listado-seccion').forEach(function (s) {
            s.classList.remove('d-print-none');
        });
    });

    const btnLogout = document.getElementById('btnLogout');
    if (btnLogout) {
        btnLogout.addEventListener('click', function () {
            fetch('/api/logout.php').then(() => location.reload());  // <- ruta absoluta
        });
    }

});