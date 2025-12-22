<?php
session_start();

// Evitar que el navegador guarde caché de esta página
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

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
    <title>Iniciar Sesión - Inventario</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/css/style_login.css">

</head>

<body>

    <div class="login-wrapper">
        
        <div class="card shadow-lg login-card p-4 mx-3">
            <div class="card-body text-center">
                
                <div class="mb-4">
                    <img src="https://cdn-icons-png.flaticon.com/512/2541/2541986.png" alt="Logo" class="brand-logo">
                    <h3 class="fw-bold text-dark">INICIAR SESIÓN</h3>
                    <p class="text-muted small">Bienvenido al Sistema de Inventario</p>
                </div>

                <form action="controladores/sesion.php" method="POST">
                    
                    <div class="form-floating mb-3">
                        <input type="text" class="form-control" id="floatingInput" placeholder="nombre@ejemplo.com" name="correo" required>
                        <label for="floatingInput"><i class="bi bi-envelope me-2"></i>Correo Electrónico</label>
                    </div>
                    
                    <div class="form-floating mb-4">
                        <input type="password" class="form-control" id="floatingPassword" placeholder="Contraseña" name="contra" required>
                        <label for="floatingPassword"><i class="bi bi-lock me-2"></i>Contraseña</label>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary btn-lg fw-bold shadow-sm">
                            INGRESAR
                        </button>
                    </div>

                    <div class="mt-4 border-top pt-3">
                        <a href="registro.php" class="text-decoration-none text-muted small">
                            ¿No tienes cuenta? <span class="text-primary fw-bold">Regístrate aquí</span>
                        </a>
                    </div>
                </form>

            </div>
            
            <div class="text-center mt-2">
                <button type="button" class="btn btn-link btn-sm text-secondary text-decoration-none" id="liveToastBtn">
                    <i class="bi bi-question-circle"></i> ¿Necesitas ayuda?
                </button>
            </div>
        </div>

    </div>

    <div class="toast-container position-fixed bottom-0 end-0 p-3">
        <div id="liveToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header bg-primary text-white">
                <i class="bi bi-info-circle-fill me-2"></i>
                <strong class="me-auto">Asistente de Login</strong>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Cerrar"></button>
            </div>
            <div class="toast-body bg-white">
                Ingresa tu correo registrado y contraseña para acceder al panel. Si olvidaste tus datos, contacta al administrador.
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            // Lógica para activar el Toast de ayuda
            const toastTrigger = document.getElementById('liveToastBtn');
            const toastLiveExample = document.getElementById('liveToast');

            if (toastTrigger) {
                const toast = new bootstrap.Toast(toastLiveExample);
                toastTrigger.addEventListener('click', () => {
                    toast.show();
                });
            }
        });
    </script>
</body>
</html>