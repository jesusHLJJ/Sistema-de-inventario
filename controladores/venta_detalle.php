<?php
session_start();
require_once "conexion.php";

/* =========================
   SEGURIDAD
========================= */
if (!isset($_SESSION['id_empleado'])) {
    header("Location: ../index.php");
    exit;
}

if (!isset($_GET['id_venta'])) {
    header("Location: ventas.php");
    exit;
}

$id_venta = $_GET['id_venta'];

/* DATOS DE LA VENTA */
$sql = "SELECT v.id_venta, v.fecha, v.monto, e.estatus
        FROM venta v
        JOIN estatus e ON e.id_estatus = v.id_estatus
        WHERE v.id_venta = ?";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("i", $id_venta);
$stmt->execute();
$resultVenta = $stmt->get_result();

if ($resultVenta->num_rows === 0) {
    header("Location: ventas.php");
    exit;
}
$venta = $resultVenta->fetch_assoc();

/* DETALLES */
$sql = "SELECT p.nombre, d.cantidad, p.precio, d.totalproducto
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
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Detalle de Venta</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">

    <style>
        /* Aseguramos el layout */
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
            display: flex;
            flex-direction: column;
        }
    </style>
</head>

<body>

    <button class="btn btn-outline-dark d-md-none position-absolute m-3" style="z-index: 1000;"
        data-bs-toggle="collapse"
        data-bs-target="#sidebar">
        <i class="bi bi-list fs-4"></i>
    </button>

    <div class="wrapper">

        <?php
        $pagina = 'ventas'; // Mantenemos activo el menú de Ventas
        include '../componentes/sidebar.php';
        ?>

        <div class="main-content">

            <div class="container py-5">

                <div class="card shadow-lg p-4 mx-auto" style="max-width: 800px; background: white; color: black; border-radius: 10px;">

                    <h3 class="text-center fw-bold mb-4">
                        DETALLE DE VENTA #<?= str_pad($venta['id_venta'], 5, "0", STR_PAD_LEFT) ?>
                    </h3>

                    <div class="row mb-4 p-3 rounded bg-light border mx-1">
                        <div class="col-md-4 text-center border-end">
                            <small class="text-muted fw-bold">FECHA</small>
                            <div class="fs-5"><?= date('d/m/Y', strtotime($venta['fecha'])) ?></div>
                            <small><?= date('H:i', strtotime($venta['fecha'])) ?></small>
                        </div>
                        <div class="col-md-4 text-center border-end">
                            <small class="text-muted fw-bold">ESTATUS</small>
                            <div>
                                <?php
                                $bg = 'bg-secondary';
                                if ($venta['estatus'] == 'En proceso' || $venta['estatus'] == 'Pagado') $bg = 'bg-warning text-dark';
                                if ($venta['estatus'] == 'Finalizada') $bg = ' bg-success';
                                if ($venta['estatus'] == 'Cancelada') $bg = 'bg-danger';
                                ?>
                                <span class="badge <?= $bg ?>"><?= $venta['estatus'] ?></span>
                            </div>
                        </div>
                        <div class="col-md-4 text-center">
                            <small class="text-muted fw-bold">TOTAL</small>
                            <div class="fs-4 fw-bold text-success">$<?= number_format($venta['monto'], 2) ?></div>
                        </div>
                    </div>

                    <h5 class="mb-3 fw-bold border-bottom pb-2">Productos Vendidos</h5>
                    <div class="table-responsive mb-4">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Producto</th>
                                    <th class="text-center">Cantidad</th>
                                    <th class="text-end">Precio</th>
                                    <th class="text-end">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($d = $detalles->fetch_assoc()): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($d['nombre']) ?></td>
                                        <td class="text-center"><?= $d['cantidad'] ?></td>
                                        <td class="text-end text-muted">$<?= number_format($d['precio'], 2) ?></td>
                                        <td class="text-end fw-bold">$<?= number_format($d['totalproducto'], 2) ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-end">
                        <a href="ventas.php" class="btn btn-outline-secondary px-4">
                            <i class="bi bi-arrow-left me-2"></i>REGRESAR
                        </a>
                    </div>

                </div>
            </div>

            <?php
            include '../componentes/footer.php';
            ?>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>