<?php
session_start();

// Verifica si el usuario está logueado
if (!isset($_SESSION['id_empleado'])) {
    header("Location: ../index.php");
    exit;
}

$nombre = $_SESSION['nombre'];
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Agregar Producto</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">

    <style>
        /* Aseguramos que el layout sea consistente */
        html,
        body {
            margin: 0;
            padding: 0;
            height: 100%;
            overflow: hidden;
        }

        .wrapper {
            display: flex;
            height: 100vh;
            width: 100%;
        }

        .main-content {
            flex-grow: 1;
            height: 100vh;
            overflow-y: auto;
            background-color: #f8f9fa;
            /* Fondo gris claro suave */
            display: flex;
            flex-direction: column;
        }
    </style>
</head>

<body>
    <button class="btn btn-outline-dark d-md-none"
        data-bs-toggle="collapse"
        data-bs-target="#sidebar">
        <i class="bi bi-list fs-4"></i>
    </button>
    <div class="wrapper">

        <?php
        $pagina = 'agregar_producto'; // Puedes ajustar esto según tu sidebar
        include '../componentes/sidebar.php';
        ?>

        <div class="main-content">

            <div class="container py-5">
                <div class="card shadow-lg p-4 mx-auto" style="max-width: 800px; background: white; color: black; border-radius: 10px;">

                    <h3 class="text-center fw-bold mb-4">AGREGAR PRODUCTO NUEVO</h3>

                    <form action="guardar_producto.php" method="POST">

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="id_producto" class="form-label fw-bold">Código de barras (ID)</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-upc-scan"></i></span>
                                    <input type="text" class="form-control" id="id_producto" name="id_producto" required placeholder="Ej. 750100...">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="marca" class="form-label fw-bold">Marca</label>
                                <input type="text" class="form-control" id="marca" name="marca" required placeholder="Ej. Bic, Sabritas...">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="nombre" class="form-label fw-bold">Nombre del producto</label>
                            <input type="text" class="form-control" id="nombre" name="nombre" required placeholder="Ej. Bolígrafo Azul Punto Medio">
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="contenido" class="form-label fw-bold">Contenido</label>
                                <input type="text" class="form-control" id="contenido" name="contenido" required placeholder="Ej. 1 pza, 500ml...">
                            </div>
                            <div class="col-md-6">
                                <label for="piezas" class="form-label fw-bold">Stock Inicial (Piezas)</label>
                                <input type="number" class="form-control" id="piezas" name="piezas" required placeholder="0">
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="precio" class="form-label fw-bold">Precio de Venta</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" step="0.01" class="form-control" id="precio" name="precio" required placeholder="0.00">
                            </div>
                        </div>

                        <div class="d-flex justify-content-between">
                            <button type="submit" class="btn btn-success px-4">
                                <i class="bi bi-floppy me-2"></i> Guardar Producto
                            </button>
                            <a href="home.php" class="btn btn-outline-secondary px-4">
                                Regresar
                            </a>
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
                    Llena todos los campos correctamente para registrar el nuevo producto en el inventario. 💡
                </div>
            </div>

            <?php include '../componentes/footer.php'; ?>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Mostrar Toast automáticamente al cargar (opcional, basado en tu código anterior)
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