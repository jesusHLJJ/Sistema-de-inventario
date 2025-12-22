<?php
session_start();
require_once "conexion.php";

if (!isset($_SESSION['id_empleado'])) {
    header("Location: ../index.php");
    exit;
}

$id_empleado = $_SESSION['id_empleado'];

// Iniciar venta si no existe
if (!isset($_SESSION['id_venta'])) {
    $sql = "INSERT INTO venta (fecha, id_estatus, monto) VALUES (NOW(), 1, 0)";
    $conexion->query($sql);
    $_SESSION['id_venta'] = $conexion->insert_id;
}

$id_venta = $_SESSION['id_venta'];

// Obtener detalles del carrito
$sql = "SELECT d.id_detalles_venta, p.nombre, d.cantidad, d.totalproducto
        FROM detalles_venta d
        JOIN producto p ON p.id_producto = d.id_producto
        WHERE d.id_venta = ?";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("i", $id_venta);
$stmt->execute();
$detalles = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Venta nueva</title>
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
        $pagina = 'venta_nueva'; // Esto activará el botón correcto en la barra
        include '../componentes/sidebar.php';
        ?>

        <div class="main-content">

            <div class="container py-5">
                <div class="card shadow-lg p-4 mx-auto" style="max-width: 900px;">

                    <h3 class="mb-4 text-center fw-bold text-dark">VENTA NUEVA</h3>

                    <form action="carrito_venta.php" method="POST" class="row g-3 mb-4 align-items-end">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">ID Producto</label>
                            <input type="number" name="id_producto" class="form-control" placeholder="Escanea o escribe el ID" required autofocus>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Cantidad</label>
                            <input type="number" name="cantidad" class="form-control" placeholder="1" value="1" required>
                        </div>
                        <div class="col-md-3">
                            <button class="btn btn-success w-100"><i class="bi bi-plus-lg"></i> Agregar</button>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead class="table-dark">
                                <tr>
                                    <th>Producto</th>
                                    <th>Cantidad</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($detalles->num_rows > 0): ?>
                                    <?php while ($row = $detalles->fetch_assoc()): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($row['nombre']) ?></td>
                                            <td><?= htmlspecialchars($row['cantidad']) ?></td>
                                            <td>$<?= number_format($row['totalproducto'], 2) ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="3" class="text-center text-muted">Aún no hay productos en la venta</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="d-grid gap-2 mt-3">
                        <a href="../controladores/confirmar_venta.php" class="btn btn-primary btn-lg">
                            <i class="bi bi-check-circle-fill"></i> Confirmar Venta
                        </a>
                    </div>

                </div>
            </div>

            <?php
            include '../componentes/footer.php'; // Incluimos el archivo
            ?>

        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <?php if (isset($_SESSION['venta_status'])): ?>
        <script>
            <?php if ($_SESSION['venta_status'] == 'error'): ?>
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: '<?php echo isset($_SESSION['venta_msg']) ? $_SESSION['venta_msg'] : "Error al procesar"; ?>',
                });
            <?php elseif ($_SESSION['venta_status'] == 'success'): ?>
                Swal.fire({
                    icon: 'success',
                    title: '¡Listo!',
                    text: 'Venta realizada con éxito',
                    timer: 2000,
                    showConfirmButton: false
                });
            <?php endif; ?>
        </script>

        <?php
        // Limpiar variables de sesión
        unset($_SESSION['venta_status']);
        unset($_SESSION['venta_msg']);
        ?>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>