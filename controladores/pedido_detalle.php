<?php
session_start();
require_once "conexion.php";

if (!isset($_SESSION['id_empleado'])) {
    header("Location: ../index.php");
    exit;
}

if (!isset($_GET['id'])) {
    header("Location: pedidos.php");
    exit;
}

$id_pedido = intval($_GET['id']);

/* =========================
   OBTENER PEDIDO
========================= */
$stmt = $conexion->prepare("
    SELECT p.*, e.nombre AS empleado
    FROM pedidos p
    LEFT JOIN empleado e ON e.id_empleado = p.id_empleado
    WHERE p.id_pedido = ?
");
$stmt->bind_param("i", $id_pedido);
$stmt->execute();
$pedido = $stmt->get_result()->fetch_assoc();

if (!$pedido) {
    die("Pedido no encontrado");
}

/* =========================
   PRODUCTOS DEL PEDIDO
========================= */
$stmt = $conexion->prepare("
    SELECT d.*, pr.nombre, pr.contenido
    FROM detalle_pedido d
    JOIN producto pr ON pr.id_producto = d.id_producto
    WHERE d.id_pedido = ?
");
$stmt->bind_param("i", $id_pedido);
$stmt->execute();
$resultado = $stmt->get_result();

/* GUARDAR PRODUCTOS EN ARRAY Y CALCULAR TOTAL */
$productos = [];
$total_pedido = 0;

while ($row = $resultado->fetch_assoc()) {
    $productos[] = $row;
    $total_pedido += $row['precio_compra'];
}

/* =========================
   RECIBIR PEDIDO
========================= */
if (
    isset($_POST['cerrar_pedido']) &&
    isset($_POST['id_pedido']) &&
    intval($_POST['id_pedido']) === $id_pedido &&
    strtolower($pedido['estatus']) === 'pendiente'
) {

    $conexion->begin_transaction();

    try {

        $stmt = $conexion->prepare("
            SELECT SUM(precio_compra) AS total
            FROM detalle_pedido
            WHERE id_pedido = ?
        ");
        $stmt->bind_param("i", $id_pedido);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        $total = $res['total'] ?? 0;

        $stmt = $conexion->prepare("
            SELECT id_producto, cantidad
            FROM detalle_pedido
            WHERE id_pedido = ?
        ");
        $stmt->bind_param("i", $id_pedido);
        $stmt->execute();
        $detalles = $stmt->get_result();

        $updateStock = $conexion->prepare("
            UPDATE producto
            SET piezas = piezas + ?
            WHERE id_producto = ?
        ");

        while ($d = $detalles->fetch_assoc()) {
            $updateStock->bind_param("ii", $d['cantidad'], $d['id_producto']);
            $updateStock->execute();
        }

        $stmt = $conexion->prepare("
            UPDATE pedidos 
            SET estatus='recibido',
                fecha_entrega = NOW(),
                total_pago = ?
            WHERE id_pedido=?
        ");
        $stmt->bind_param("di", $total, $id_pedido);
        $stmt->execute();

        $conexion->commit();

        header("Location: pedido_detalle.php?id=" . $id_pedido . "&ok=1");
        exit;
    } catch (Exception $e) {

        $conexion->rollback();
        die("Error al recibir pedido: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Detalle del Pedido</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">

    <style>
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

    <button class="btn btn-outline-dark d-md-none position-absolute m-3"
        style="z-index:1000;"
        data-bs-toggle="collapse"
        data-bs-target="#sidebar">
        <i class="bi bi-list fs-4"></i>
    </button>

    <div class="wrapper">

        <?php
        $pagina = 'pedidos';
        include '../componentes/sidebar.php';
        ?>

        <div class="main-content">

            <div class="container py-5">

                <div class="card shadow-lg p-4 mx-auto"
                    style="max-width:850px;background:white;color:black;border-radius:10px;">

                    <h3 class="text-center fw-bold mb-4">
                        DETALLE DE PEDIDO #<?= str_pad($pedido['id_pedido'], 5, "0", STR_PAD_LEFT) ?>
                    </h3>

                    <div class="row mb-4 p-3 rounded bg-light border mx-1">

                        <div class="col-md-3 text-center border-end">

                            <small class="text-muted fw-bold">PROVEEDOR</small>

                            <div class="fs-6">
                                <?= htmlspecialchars($pedido['proveedor']) ?>
                            </div>

                        </div>

                        <div class="col-md-3 text-center border-end">

                            <small class="text-muted fw-bold">FECHA PEDIDO</small>

                            <div>
                                <?= date('d/m/Y', strtotime($pedido['fecha_pedido'])) ?>
                            </div>

                            <small>
                                <?= date('H:i', strtotime($pedido['fecha_pedido'])) ?>
                            </small>

                        </div>

                        <div class="col-md-3 text-center border-end">

                            <small class="text-muted fw-bold">EMPLEADO</small>

                            <div>
                                <?= htmlspecialchars($pedido['empleado']) ?>
                            </div>

                        </div>

                        <div class="col-md-3 text-center">

                            <small class="text-muted fw-bold">ESTATUS</small>

                            <?php
                            $bg = 'bg-secondary';

                            if ($pedido['estatus'] == 'Pendiente') $bg = 'bg-warning text-dark';
                            if ($pedido['estatus'] == 'Recibido') $bg = 'bg-success';
                            if ($pedido['estatus'] == 'cancelado') $bg = 'bg-danger';
                            ?>

                            <div>
                                <span class="badge <?= $bg ?>">
                                    <?= $pedido['estatus'] ?>
                                </span>
                            </div>

                        </div>

                    </div>

                    <h5 class="mb-3 fw-bold border-bottom pb-2">
                        Productos del pedido
                    </h5>

                    <div class="table-responsive mb-4">

                        <table class="table table-hover align-middle">

                            <thead class="table-light">
                                <tr>
                                    <th>Producto</th>
                                    <th class="text-center">Cantidad</th>
                                    <th class="text-end">Precio compra</th>
                                </tr>
                            </thead>

                            <tbody>
                                <?php if (count($productos) > 0): ?>
                                    <?php foreach ($productos as $pr): ?>
                                        <tr>
                                            <td>
                                                <span class="fw-bold"><?= htmlspecialchars($pr['nombre']) ?></span>
                                                <span class="text-muted small"><?= htmlspecialchars($pr['contenido']) ?></span>
                                            </td>

                                            <td class="text-center">
                                                <?= $pr['cantidad'] ?>
                                            </td>

                                            <td class="text-end">
                                                $<?= number_format($pr['precio_compra'], 2) ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>

                                    <tr class="table-light fw-bold">

                                        <td colspan="2" class="text-end">
                                            TOTAL DEL PEDIDO
                                        </td>

                                        <td class="text-end text-success">
                                            $<?= number_format($total_pedido, 2) ?>
                                        </td>

                                    </tr>

                                <?php else: ?>

                                    <tr>

                                        <td colspan="3" class="text-center text-muted py-4">
                                            No hay productos en este pedido
                                        </td>

                                    </tr>

                                <?php endif; ?>

                            </tbody>

                        </table>

                    </div>

                    <div class="d-flex justify-content-between">

                        <a href="pedidos.php" class="btn btn-outline-secondary px-4">
                            <i class="bi bi-arrow-left me-2"></i>REGRESAR
                        </a>

                        <?php if ($pedido['estatus'] == 'Pendiente'): ?>

                            <form method="POST" id="formCerrar">

                                <input type="hidden" name="cerrar_pedido" value="1">
                                <input type="hidden" name="id_pedido" value="<?= $pedido['id_pedido'] ?>">

                                <button type="button" onclick="confirmar()" class="btn btn-success">
                                    <i class="bi bi-check-circle me-2"></i>
                                    MARCAR COMO RECIBIDO
                                </button>

                            </form>

                        <?php endif; ?>

                    </div>

                </div>
            </div>

            <?php include '../componentes/footer.php'; ?>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        function confirmar() {

            Swal.fire({
                title: '¿Confirmar recepción?',
                text: 'El pedido se marcará como recibido',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Sí, recibir',
                cancelButtonText: 'Cancelar'
            }).then((result) => {

                if (result.isConfirmed) {
                    document.getElementById('formCerrar').submit();
                }

            });

        }
    </script>

</body>

</html>