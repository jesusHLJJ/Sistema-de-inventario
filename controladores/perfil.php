<?php
session_start();
// Verificar sesión e incluir conexión ANTES de cualquier HTML
if (!isset($_SESSION['id_empleado'])) {
    header("Location: ../index.php");
    exit;
}
require_once 'conexion.php';

$id_empleado = $_SESSION['id_empleado'];
$mensaje = "";

/* =========================
   LOGICA DE ACTUALIZACIÓN (PHP)
========================= */
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // ... (Tu lógica de actualización existente se mantiene igual) ...
    $nombre = $_POST['nombre'];
    $ap_paterno = $_POST['ap_paterno'];
    $ap_materno = $_POST['ap_materno'];
    $edad = $_POST['edad'];
    $sexo = $_POST['sexo'];
    $correo = $_POST['correo'];

    $sql = "UPDATE empleado SET nombre=?, ap_paterno=?, ap_materno=?, edad=?, sexo=?, correo=? WHERE id_empleado=?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("sssissi", $nombre, $ap_paterno, $ap_materno, $edad, $sexo, $correo, $id_empleado);

    if ($stmt->execute()) {
        $mensaje = "Perfil actualizado correctamente";
        // Actualizar sesión si cambiaste el nombre
        $_SESSION['nombre'] = $nombre;
    } else {
        $mensaje = "Error al actualizar";
    }
}

/* OBTENER DATOS */
$sql = "SELECT nombre, ap_paterno, ap_materno, edad, sexo, correo FROM empleado WHERE id_empleado=?";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("i", $id_empleado);
$stmt->execute();
$empleado = $stmt->get_result()->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Perfil</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body>
    <button class="btn btn-outline-dark d-md-none"
        data-bs-toggle="collapse"
        data-bs-target="#sidebar">
        <i class="bi bi-list fs-4"></i>
    </button>
    <div class="wrapper">

        <?php
        $pagina = 'perfil'; // Definimos qué página es esta
        include '../componentes/sidebar.php'; // Incluimos el archivo
        ?>

        <div class="main-content">

            <div class="container">
                <div class="card shadow-lg p-4 mx-auto mt-5" style="max-width: 800px; background: white; color: black; border-radius: 10px;">

                    <h3 class="text-center fw-bold mb-4">ACTUALIZAR PERFIL</h3>

                    <?php if ($mensaje): ?>
                        <div class="alert alert-success text-center"><?= $mensaje ?></div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Nombre</label>
                                <input type="text" class="form-control" name="nombre" value="<?= $empleado['nombre'] ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Apellido paterno</label>
                                <input type="text" class="form-control" name="ap_paterno" value="<?= $empleado['ap_paterno'] ?>" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Apellido materno</label>
                            <input type="text" class="form-control" name="ap_materno" value="<?= $empleado['ap_materno'] ?>" required>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Edad</label>
                                <select class="form-select" name="edad">
                                    <?php for ($i = 18; $i <= 100; $i++): ?>
                                        <option value="<?= $i ?>" <?= ($empleado['edad'] == $i) ? 'selected' : '' ?>><?= $i ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Género</label>
                                <select class="form-select" name="sexo">
                                    <option value="M" <?= ($empleado['sexo'] == 'M') ? 'selected' : '' ?>>Masculino</option>
                                    <option value="F" <?= ($empleado['sexo'] == 'F') ? 'selected' : '' ?>>Femenino</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label">Correo electrónico</label>
                            <input type="email" class="form-control" name="correo" value="<?= $empleado['correo'] ?>" required>
                        </div>

                        <div class="d-flex justify-content-between">
                            <button type="submit" class="btn btn-primary px-4"><i class="bi bi-save"></i> ACTUALIZAR</button>
                            <a href="home.php" class="btn btn-outline-secondary px-4">REGRESAR</a>
                        </div>
                    </form>

                </div>
            </div>
            <div id="liveToast" class="toast position-fixed bottom-0 end-0 m-3" role="alert" aria-live="assertive" aria-atomic="true" data-bs-delay="5000">
                <div class="toast-header">
                    <strong class="me-auto text-primary"><i class="bi bi-info-circle-fill"></i> Asistente</strong>
                    <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Cerrar"></button>
                </div>
                <div class="toast-body text-dark">
                    Selecciona cada campo que deseas actualizar sobre tu infomración personal y al final da click sobre el boton actualizar para guardar tus cambios. ✍️
                </div>
            </div>
            <?php
            include '../componentes/footer.php'; // Incluimos el archivo
            ?>
        </div>

    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
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