<?php
session_start();
require_once "conexion.php";

if (!isset($_SESSION['id_empleado'])) {
    header("Location: ../index.php");
    exit;
}

$productos = $conexion->query("SELECT id_producto, nombre, contenido FROM producto ORDER BY nombre");
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Nuevo Pedido</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>

</head>

<body>

    <div class="wrapper">

        <?php
        $pagina = 'pedidos';
        include '../componentes/sidebar.php';
        ?>

        <div class="main-content">

            <div class="container py-5">

                <div class="card shadow-sm">
                    <div class="card-header bg-white py-3">
                        <h3 class="mb-0 fw-bold text-center">
                            <i class="bi bi-truck me-2"></i> NUEVO PEDIDO
                        </h3>
                    </div>

                    <div class="card-body p-4">

                        <form method="POST" action="guardar_pedido.php">

                            <!-- PROVEEDOR -->
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <label class="fw-bold">Proveedor</label>
                                    <input type="text" name="proveedor" class="form-control" required>
                                </div>
                            </div>

                            <hr>

                            <h5 class="fw-bold mb-4">
                                <i class="bi bi-box-seam"></i> Productos del pedido
                            </h5>

                            <div id="productos">

                                <div class="row g-2 mb-2 producto-item">
                                    <div class="col-md-6">
                                        <label class="small fw-bold">Producto</label>
                                        <select name="producto[]" class="form-select buscable" required>
                                            <?php while ($p = $productos->fetch_assoc()): ?>
                                                <option value="<?= $p['id_producto'] ?>">
                                                    <?= $p['nombre'] ?> — <?= $p['contenido'] ?>
                                                </option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>

                                    <div class="col-md-3">
                                        <label class="small fw-bold">Cantidad</label>
                                        <input type="number" name="cantidad[]" class="form-control" required>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="small fw-bold">Precio</label>
                                        <input type="number" name="precio[]" class="form-control precio" step="0.01" required>
                                    </div>


                                    <div class="col-md-3 d-flex align-items-end">
                                        <button type="button" class="btn btn-danger w-100 eliminar">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </div>

                            </div>

                            <!-- BOTONES -->
                            <div class="mt-3 d-flex gap-2">

                                <button type="button" onclick="agregar()" class="btn btn-outline-primary">
                                    <i class="bi bi-plus-circle"></i> Agregar producto
                                </button>
                                <button type="submit" class="btn btn-success ms-auto">
                                    <i class="bi bi-check-circle"></i> Guardar Pedido
                                </button>
                                <div class="ms-auto text-end mt-3">
                                    <label class="fw-bold">TOTAL A PAGAR</label>
                                    <input type="text" id="total_general" class="form-control fw-bold text-success" readonly>
                                </div>


                            </div>

                        </form>

                    </div>
                </div>

            </div>

            <?php include '../componentes/footer.php'; ?>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        function calcularTotal() {

            let total = 0;

            document.querySelectorAll(".precio").forEach(input => {
                const precio = parseFloat(input.value) || 0;
                total += precio;
            });

            document.getElementById("total_general").value = total.toFixed(2);
        }

        /* CALCULAR EN TIEMPO REAL */
        document.addEventListener("input", function(e) {
            if (e.target.classList.contains("precio")) {
                calcularTotal();
            }
        });

        /* CLONAR FILA */
        function agregar() {

            const contenedor = document.getElementById("productos");
            const fila = contenedor.querySelector(".producto-item").cloneNode(true);

            fila.querySelectorAll("input").forEach(i => i.value = "");

            contenedor.appendChild(fila);
            activarBuscador();
        }

        /* ELIMINAR FILA */
        document.addEventListener("click", function(e) {
            if (e.target.closest(".eliminar")) {
                const filas = document.querySelectorAll(".producto-item");
                if (filas.length > 1) {
                    e.target.closest(".producto-item").remove();
                    calcularTotal();
                }
            }
        });

        function activarBuscador() {
            document.querySelectorAll(".buscable").forEach(select => {

                if (!select.tomselect) {
                    new TomSelect(select, {
                        create: false,
                        sortField: {
                            field: "text",
                            direction: "asc"
                        },
                        placeholder: "Buscar producto..."
                    });
                }

            });
        }

        /* activar al cargar */
        activarBuscador();
    </script>


</body>

</html>