<?php
session_start();
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/src/includes/header.php';
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

<!-- Título grande debajo del carrusel -->
<div class="text-center py-5">
    <h1 class="titulo-principal">DATACODE 2.0</h1>
    <p class="text-muted fs-5">Actividades</p>
</div>

<?php require_once __DIR__ . '/src/includes/footer.php'; ?>