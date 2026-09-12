<?php
session_start();
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/src/includes/header.php';

// Contadores reales para mostrar en las tarjetas de actividades de inicio
$totalTalleres    = (int) $pdo->query("SELECT COUNT(*) FROM talleres")->fetchColumn();
$totalTorneos     = (int) $pdo->query("SELECT COUNT(*) FROM torneos")->fetchColumn();
$totalHackathones = (int) $pdo->query("SELECT COUNT(*) FROM hackathones")->fetchColumn();
?>

<!-- Carrusel de imágenes -->
<div id="carruselPrincipal" class="carousel slide" data-bs-ride="carousel">   

    <!-- Indicadores (puntitos abajo) -->
    <div class="carousel-indicators">
        <button type="button" data-bs-target="#carruselPrincipal" data-bs-slide-to="0" class="active"></button>
        <button type="button" data-bs-target="#carruselPrincipal" data-bs-slide-to="1"></button>
        <button type="button" data-bs-target="#carruselPrincipal" data-bs-slide-to="2"></button>
    </div>

    <div class="carousel-inner">
        <div class="carousel-item active">
            <img src="src/img/slide1.jpg" class="d-block w-100 carousel-img" alt="Slide 1">
        </div>
        <div class="carousel-item">
            <img src="src/img/slide2.jpg" class="d-block w-100 carousel-img" alt="Slide 2">
        </div>
        <div class="carousel-item">
            <img src="src/img/slide3.jpg" class="d-block w-100 carousel-img" alt="Slide 3">
        </div>
    </div>

    <!-- Flecha izquierda -->
    <button class="carousel-control-prev" type="button" data-bs-target="#carruselPrincipal" data-bs-slide="prev">
        <span class="carousel-control-prev-icon"></span>
    </button>

    <!-- Flecha derecha -->
    <button class="carousel-control-next" type="button" data-bs-target="#carruselPrincipal" data-bs-slide="next">
        <span class="carousel-control-next-icon"></span>
    </button>
</div>

<!-- Presentación / bienvenida debajo del carrusel -->
<div class="container">
    <div class="text-center py-5">
        <h1 class="titulo-principal">DATACODE 2.0</h1>
        <p class="lead text-muted mx-auto" style="max-width: 720px;">
            La plataforma para organizar y vivir la Jornada Académica de la carrera de
            Ingeniería de Software: talleres, torneos y hackathones, todo en un solo
            lugar, para que alumnos, docentes y coordinación tengan siempre a la mano
            lo que necesitan.
        </p>
    </div>

    <div class="text-center mb-4">
        <h2 class="seccion-inicio-titulo mb-2">¿Qué puedes hacer en la Jornada?</h2>
        <p class="text-muted">Estas son las tres actividades principales del evento.</p>
    </div>

    <div class="row row-cols-1 row-cols-md-3 g-4 mb-5 justify-content-center">
        <div class="col">
            <div class="card actividad-card text-center p-4">
                <div class="card-body d-flex flex-column align-items-center">
                    <div class="actividad-icono"><i class="bi bi-tools"></i></div>
                    <h4 class="text-vino">Talleres</h4>
                    <span class="actividad-contador"><?= $totalTalleres ?> disponibles</span>
                    <p class="text-muted">
                        Sesiones prácticas sobre un tema específico, impartidas por un
                        Docente responsable. Los alumnos se inscriben según el aula,
                        la fecha y el horario disponibles.
                    </p>
                    <a href="/talleres/ver-talleres.php" class="btn btn-outline-secondary btn-sm mt-auto">
                        Ver talleres <i class="bi bi-arrow-right ms-1"></i>
                    </a>
                </div>
            </div>
        </div>

        <div class="col">
            <div class="card actividad-card text-center p-4">
                <div class="card-body d-flex flex-column align-items-center">
                    <div class="actividad-icono"><i class="bi bi-trophy"></i></div>
                    <h4 class="text-vino">Torneos</h4>
                    <span class="actividad-contador"><?= $totalTorneos ?> disponibles</span>
                    <p class="text-muted">
                        Competencias individuales con su propio formato y reglas, para
                        que los alumnos midan sus habilidades y se diviertan durante
                        la jornada.
                    </p>
                    <a href="/torneos/ver-torneos.php" class="btn btn-outline-secondary btn-sm mt-auto">
                        Ver torneos <i class="bi bi-arrow-right ms-1"></i>
                    </a>
                </div>
            </div>
        </div>

        <div class="col">
            <div class="card actividad-card text-center p-4">
                <div class="card-body d-flex flex-column align-items-center">
                    <div class="actividad-icono"><i class="bi bi-cpu"></i></div>
                    <h4 class="text-vino">Hackathones</h4>
                    <span class="actividad-contador"><?= $totalHackathones ?> disponibles</span>
                    <p class="text-muted">
                        Retos de desarrollo a contrarreloj, con rúbrica de evaluación
                        y entregables definidos, para poner a prueba lo aprendido
                        durante la carrera.
                    </p>
                    <a href="/hackathones/ver-hackathones.php" class="btn btn-outline-secondary btn-sm mt-auto">
                        Ver hackathones <i class="bi bi-arrow-right ms-1"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/src/includes/footer.php'; ?>