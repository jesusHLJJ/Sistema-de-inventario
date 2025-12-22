<?php
session_start();
// Si ya inició sesión, redirigir al home
if (isset($_SESSION['id_empleado'])) {
    header("Location: controladores/home.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Usuario</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/css/style_register.css">

</head>

<body>

    <div class="register-wrapper">

        <div class="card shadow-lg register-card p-4">
            <div class="card-body">

                <div class="text-center mb-4">
                    <img src="https://cdn-icons-png.flaticon.com/512/2541/2541986.png" alt="Logo" class="brand-logo-small mb-2">
                    <h3 class="fw-bold text-dark">CREAR CUENTA</h3>
                    <p class="text-muted small">Ingresa los datos del nuevo empleado</p>
                </div>

                <form action="controladores/guardar_empleado.php" method="POST">

                    <div class="row g-2 justify-content-center">

                        <div class="col-md-4">
                            <label class="form-label fw-bold small text-secondary">Nombre(s)</label>
                            <input type="text" class="form-control" placeholder="Ej. Juan Carlos" name="nombre" required>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-bold small text-secondary">Apellido Paterno</label>
                            <input type="text" class="form-control" placeholder="Ej. Pérez" name="ap_paterno" required>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-bold small text-secondary">Apellido Materno</label>
                            <input type="text" class="form-control" placeholder="Ej. López" name="ap_materno" required>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-bold small text-secondary">Edad</label>
                            <select class="form-select" name="edad" required>
                                <option selected disabled value="">Seleccionar...</option>
                                <?php for ($i = 18; $i <= 80; $i++): ?>
                                    <option value="<?= $i ?>"><?= $i ?> años</option>
                                <?php endfor; ?>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-bold small text-secondary">Género</label>
                            <select class="form-select" name="sexo" required>
                                <option selected disabled value="">Seleccionar...</option>
                                <option value="M">Masculino</option>
                                <option value="F">Femenino</option>
                            </select>
                        </div>

                        <div class="col-20 mt-1">
                            <hr class="text-muted">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-secondary">Correo Electrónico</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="bi bi-envelope"></i></span>
                                <input type="email" class="form-control" placeholder="correo@ejemplo.com" name="correo" required>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-secondary">Contraseña</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="bi bi-key"></i></span>
                                <input type="password" class="form-control" placeholder="******" name="contra" required>
                            </div>
                        </div>

                    </div>

                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-success fw-bold shadow-sm w-50">
                            <i class="bi bi-person-plus-fill me-2"></i>REGISTRAR
                        </button>
                        <a href="index.php" class="btn btn-outline-secondary w-50">
                            <i class="bi bi-arrow-left me-2"></i>LOGIN
                        </a>
                    </div>

                </form>

            </div>

            <div class="text-center mt-3">
                <button type="button" class="btn btn-link btn-sm text-secondary text-decoration-none" id="liveToastBtn">
                    <i class="bi bi-question-circle"></i> Ayuda sobre el registro
                </button>
            </div>
        </div>

    </div>

    <div class="toast-container position-fixed bottom-0 end-0 p-3">
        <div id="liveToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header bg-success text-white">
                <i class="bi bi-lightbulb-fill me-2"></i>
                <strong class="me-auto">Ayuda de Registro</strong>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body bg-white">
                Completa todos los campos obligatorios para dar de alta un nuevo usuario en el sistema. La contraseña debe ser segura, para tu seguridad. 🤖
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const toastTrigger = document.getElementById('liveToastBtn');
            const toastLiveExample = document.getElementById('liveToast');

            if (toastTrigger) {
                const toast = new bootstrap.Toast(toastLiveExample);
                toastTrigger.addEventListener('click', () => {
                    toast.show();
                });
            }
        });
        document.addEventListener("DOMContentLoaded", function() {
            const toastElement = document.getElementById('liveToast');
            if (toastElement) {
                const toast = new bootstrap.Toast(toastElement);
                toast.show();
            }
        });
    </script>
</body>

</html>