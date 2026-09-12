<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DATACODE</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link href="/src/css/style.css" rel="stylesheet">
</head>
<body>

<!-- Barra de navegación -->
<nav class="navbar navbar-expand-lg navbar-dark shadow-sm" style="background-color: #9C2C53;">
    <div class="container-fluid">
        <a class="navbar-brand" href="/index.php">
            <img src="/src/img/logo.png" alt="DATACODE" height="40">
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMenu">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarMenu">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item dropdown hover-dropdown">
    <a class="nav-link dropdown-toggle" href="#" role="button">Talleres</a>
    <ul class="dropdown-menu">
        <li><a class="dropdown-item" href="/talleres/ver-talleres.php">Ver talleres disponibles</a></li>

        <?php if (isset($_SESSION['usuario_id'])): ?>
            <?php if ($_SESSION['rol_id'] == 1): ?>
                <li><a class="dropdown-item" href="/talleres/propuestas.php">Propuestas (Foro)</a></li>
            <?php endif; ?>
            <?php if ($_SESSION['rol_id'] == 2): ?>
                <li><a class="dropdown-item" href="/talleres/propuestas.php">Propuestas (Foro)</a></li>
                <li><a class="dropdown-item" href="/talleres/nueva-propuesta.php">Proponer nuevo taller</a></li>
            <?php endif; ?>
            <?php if ($_SESSION['rol_id'] == 3): ?>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="/talleres/publicar-taller.php">Publicar taller</a></li>
                <li><a class="dropdown-item" href="/talleres/modificar-taller.php">Modificar talleres</a></li>
                <li><a class="dropdown-item" href="/talleres/crear-disponibilidad.php">Administrar disponibilidades</a></li>
                <li><a class="dropdown-item" href="/usuarios/integrar-alumnos.php">Integrar alumnos al sistema</a></li>
            <?php endif; ?>
        <?php endif; ?>
    </ul>
</li>
                <!-- Torneos y Hackathon igual que antes -->
            </ul>

            <div class="dropdown login-dropdown">
                <button class="btn login-icon-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-person-circle"></i>
                </button>
                <div class="dropdown-menu dropdown-menu-end login-panel-pop p-3">
                    <?php if (isset($_SESSION['usuario_id'])): ?>
                        <div class="text-center mb-3">
                            <i class="bi bi-person-circle" style="font-size: 3rem; color:#9C2C53;"></i>
                            <h6 class="mb-0 mt-1"><?= htmlspecialchars($_SESSION['nombre']) ?></h6>
                        </div>
                        <a href="/perfil.php" class="dropdown-item"><i class="bi bi-person me-2"></i>Ver perfil</a>
                        <a href="/cambiar-foto.php" class="dropdown-item"><i class="bi bi-camera me-2"></i>Cambiar foto de perfil</a>
                        <a href="/cambiar-password.php" class="dropdown-item"><i class="bi bi-key me-2"></i>Cambiar contraseña</a>
                        <a href="/imprimir_credencial.php" class="dropdown-item"><i class="bi bi-camera me-2"></i>Imprimir Gafete</a>
                        <hr>
                        <button id="btnLogout" class="btn btn-outline-danger btn-sm w-100">
                            <i class="bi bi-box-arrow-right me-2"></i>Cerrar sesión
                        </button>
                    <?php else: ?>
                        <h6 class="mb-3"><i class="bi bi-person-circle me-2"></i>Iniciar sesión</h6>
                        <form id="formLogin">
                            <div class="mb-2">
                                <label class="form-label small mb-1">Matrícula</label>
                                <input type="text" name="matricula" maxlength="8" class="form-control form-control-sm" required>
                            </div>
                            <div class="mb-2">
                                <label class="form-label small mb-1">Contraseña</label>
                                <input type="password" name="password" maxlength="8" class="form-control form-control-sm" required>
                            </div>
                            <div id="loginError" class="text-danger small mb-2" style="display:none;"></div>
                            <button type="submit" class="btn btn-login btn-sm w-100">Iniciar Sesión</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</nav>