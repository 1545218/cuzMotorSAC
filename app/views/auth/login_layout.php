<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'Iniciar Sesión' ?> - Cruz Motor S.A.C.</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_PATH ?>/public/css/login_layout.css">
    <link rel="stylesheet" href="<?= BASE_PATH ?>/public/css/login_carrusel.css">
    <script src="https://unpkg.com/feather-icons"></script>
</head>

<body class="bg-light">
    <div class="container-fluid min-vh-100 d-flex align-items-center justify-content-center bg-light">
    <div class="row w-100 g-0 flex-md-nowrap shadow-lg rounded-4 overflow-hidden" style="max-width: 950px; min-height: 520px; background: #fff;">
            <!-- Login SIEMPRE visible a la izquierda -->
            <div class="col-12 col-md-6 d-flex align-items-center justify-content-center p-0 order-1 order-md-1" style="min-height:520px;">
                <div class="w-100 login-glass-card d-flex flex-column justify-content-center align-items-center" style="max-width: 390px; min-height: 100%; padding: 40px 32px;">
                    <div class="mb-4 w-100">
                        <div class="text-center mb-3">
                            <i data-feather="box" class="feather-icon fa-3x text-primary mb-2"></i>
                        </div>
                        <h1 class="fw-bold display-6 text-center mb-1" style="letter-spacing:0.5px;">Cruz Motor S.A.C.</h1>
                        <p class="text-secondary text-center mb-0 fs-5">Sistema de Inventario</p>
                    </div>
                    <?php if (isset($error) && !empty($error)): ?>
                        <div class="alert alert-danger d-flex align-items-center gap-2 py-2 px-3 mb-3 w-100" role="alert">
                            <i class="fas fa-exclamation-triangle me-2"></i><span><?= htmlspecialchars($error) ?></span>
                        </div>
                    <?php endif; ?>
                    <form method="POST" action="?page=login" autocomplete="off" class="needs-validation w-100" novalidate>
                        <div class="mb-3">
                            <label for="username" class="form-label fw-semibold text-dark">Usuario</label>
                            <div class="input-group has-validation" style="height:44px;">
                                <span class="input-group-text bg-white px-3 border-end-0" style="border-radius: 8px 0 0 8px; display:flex; align-items:center; height:44px; border-color:#d1d5db;">
                                    <i data-feather="user" class="feather-icon text-secondary" style="font-size:1.2rem;"></i>
                                </span>
                                <input type="text" class="form-control border-start-0" id="username" name="username" required autocomplete="username" aria-label="Usuario" aria-describedby="usernameHelp" placeholder="Ingrese su usuario" style="background:#fff; border-radius: 0 8px 8px 0; height:44px; border-color:#d1d5db;">
                                <div class="invalid-feedback">Ingrese su usuario.</div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label fw-semibold text-dark">Contraseña</label>
                            <div class="input-group has-validation" style="height:44px;">
                                <span class="input-group-text bg-white px-3 border-end-0" style="border-radius: 8px 0 0 8px; display:flex; align-items:center; height:44px; border-color:#d1d5db;">
                                    <i data-feather="lock" class="feather-icon text-secondary" style="font-size:1.2rem;"></i>
                                </span>
                                <input type="password" class="form-control border-start-0" id="password" name="password" required autocomplete="current-password" aria-label="Contraseña" placeholder="Tu clave segura" style="background:#fff; border-radius: 0 8px 8px 0; height:44px; border-color:#d1d5db;">
                                <div class="invalid-feedback">Ingrese su contraseña.</div>
                            </div>
                        </div>
                        <div class="d-flex align-items-center mb-4 gap-2">
                            <div class="form-check m-0">
                                <input type="checkbox" class="form-check-input" id="remember" name="remember">
                                <label class="form-check-label small" for="remember">Recordarme</label>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary w-100 py-2 fs-5 fw-semibold shadow-sm" style="min-height:48px;">
                            <i data-feather="log-in" class="feather-icon me-2"></i> Iniciar Sesión
                        </button>
                    </form>
                </div>
            </div>
            <!-- Carrusel visual automotriz a la derecha -->
            <div class="col-12 col-md-6 d-flex p-0 carrusel-login-bg align-items-center justify-content-center order-2 order-md-2" style="min-height:520px;">
                <div id="loginCarousel" class="carousel carousel-fade slide w-100" data-bs-ride="carousel" data-bs-interval="2000" data-bs-pause="hover">
                    <div class="carousel-inner w-100">
                        <div class="carousel-item active w-100 d-flex flex-column justify-content-center align-items-center" style="background-image: url('<?= BASE_PATH ?>/public/images/race-car-4503692.jpg');">
                            <div class="carrusel-overlay"></div>
                            <div class="carrusel-content d-flex flex-column justify-content-center align-items-start h-100 w-100" style="padding: 48px;">
                                <span class="icon"><i data-feather="archive" class="feather-icon"></i></span>
                                <h2>+1200 repuestos gestionados</h2>
                                <p>Inventario digitalizado y actualizado en tiempo real.</p>
                            </div>
                        </div>
                        <div class="carousel-item w-100 d-flex flex-column justify-content-center align-items-center" style="background-image: url('<?= BASE_PATH ?>/public/images/race-car-4503692.jpg');">
                            <div class="carrusel-overlay"></div>
                            <div class="carrusel-content d-flex flex-column justify-content-center align-items-start h-100 w-100" style="padding: 48px;">
                                <span class="icon"><i data-feather="truck" class="feather-icon"></i></span>
                                <h2>Control de vehículos en taller</h2>
                                <p>Visualiza el avance de cada servicio en segundos.</p>
                            </div>
                        </div>
                        <div class="carousel-item w-100 d-flex flex-column justify-content-center align-items-center" style="background-image: url('<?= BASE_PATH ?>/public/images/race-car-4503692.jpg');">
                            <div class="carrusel-overlay"></div>
                            <div class="carrusel-content d-flex flex-column justify-content-center align-items-start h-100 w-100" style="padding: 48px;">
                                <span class="icon"><i data-feather="wrench" class="feather-icon"></i></span>
                                <h2>+30% eficiencia operativa</h2>
                                <p>Optimiza procesos y reduce errores humanos.</p>
                            </div>
                        </div>
                        <div class="carousel-item w-100 d-flex flex-column justify-content-center align-items-center" style="background-image: url('<?= BASE_PATH ?>/public/images/race-car-4503692.jpg');">
                            <div class="carrusel-overlay"></div>
                            <div class="carrusel-content d-flex flex-column justify-content-center align-items-start h-100 w-100" style="padding: 48px;">
                                <span class="icon"><i data-feather="users" class="feather-icon"></i></span>
                                <h2>Soporte técnico especializado</h2>
                                <p>Equipo capacitado y atención personalizada.</p>
                            </div>
                        </div>
                        <div class="carousel-item w-100 d-flex flex-column justify-content-center align-items-center" style="background-image: url('<?= BASE_PATH ?>/public/images/race-car-4503692.jpg');">
                            <div class="carrusel-overlay"></div>
                            <div class="carrusel-content d-flex flex-column justify-content-center align-items-start w-100" style="padding: 48px;">
                                <span class="icon"><i data-feather="bar-chart-2" class="feather-icon"></i></span>
                                <h2>Reportes y estadísticas</h2>
                                <p>Toma decisiones con datos claros y visuales.</p>
                            </div>
                        </div>
                    </div>
                    <!-- Flechas de navegación eliminadas para diseño limpio -->
                    <div class="carousel-indicators mb-0">
                        <button type="button" data-bs-target="#loginCarousel" data-bs-slide-to="0" class="active" aria-current="true" aria-label="Slide 1"></button>
                        <button type="button" data-bs-target="#loginCarousel" data-bs-slide-to="1" aria-label="Slide 2"></button>
                        <button type="button" data-bs-target="#loginCarousel" data-bs-slide-to="2" aria-label="Slide 3"></button>
                        <button type="button" data-bs-target="#loginCarousel" data-bs-slide-to="3" aria-label="Slide 4"></button>
                        <button type="button" data-bs-target="#loginCarousel" data-bs-slide-to="4" aria-label="Slide 5"></button>
                    </div>
                </div>
            </div>
            <!-- ...el carrusel permanece igual... -->
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Inicializaciones del login
        function igualarAlturaCarrusel() {
            var login = document.querySelector('.login-glass-card');
            var carrusel = document.getElementById('loginCarousel');
            if (window.innerWidth >= 768 && login && carrusel) {
                carrusel.style.height = login.offsetHeight + 'px';
            } else if (carrusel) {
                carrusel.style.height = '';
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            if (typeof feather !== 'undefined') feather.replace();
            // Igualar altura al cargar
            igualarAlturaCarrusel();
        });

        window.addEventListener('load', igualarAlturaCarrusel);
        window.addEventListener('resize', igualarAlturaCarrusel);
    </script>
</body>

</html>