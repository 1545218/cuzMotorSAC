<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Página no encontrada - Cruz Motor SAC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center align-items-center min-vh-100">
            <div class="col-md-6 text-center">
                <div class="card shadow">
                    <div class="card-body py-5">
                        <i class="fas fa-exclamation-triangle text-warning display-1 mb-4"></i>
                        <h1 class="display-4 text-dark mb-3">404</h1>
                        <h2 class="h4 text-muted mb-4">Página no encontrada</h2>
                        <p class="text-muted mb-4">
                            Lo sentimos, la página que estás buscando no existe o ha sido movida.
                        </p>
                        <div class="d-grid gap-2 d-md-block">
                            <a href="<?= BASE_PATH ?>/dashboard" class="btn btn-primary">
                                <i class="fas fa-home me-2"></i>Ir al Dashboard
                            </a>
                            <button onclick="history.back()" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-2"></i>Volver
                            </button>
                        </div>
                    </div>
                </div>
                <div class="mt-4">
                    <small class="text-muted">
                        <i class="fas fa-tools me-1"></i>
                        Sistema de Inventario <?= APP_NAME ?>
                    </small>
                </div>
            </div>
        </div>
    </div>
</body>

</html>