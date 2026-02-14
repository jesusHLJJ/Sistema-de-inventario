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
    SELECT d.*, pr.nombre
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
    $total_pedido += $row['precio_compra']; // SOLO suma precio
}


/* =========================
   RECIBIR PEDIDO
========================= */
if (
    isset($_POST['cerrar_pedido']) &&
    isset($_POST['id_pedido']) &&
    intval($_POST['id_pedido']) === $id_pedido &&
    $pedido['estatus'] === 'pendiente'
) {

    $conexion->begin_transaction();

    try {

        /* SUMAR TOTAL DEL PEDIDO */
        $stmt = $conexion->prepare("
            SELECT SUM(precio_compra) AS total
            FROM detalle_pedido
            WHERE id_pedido = ?
        ");
        $stmt->bind_param("i", $id_pedido);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        $total = $res['total'] ?? 0;

        /* OBTENER DETALLES PARA STOCK */
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

        /* ACTUALIZAR PEDIDO */
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
    <title>Detalle del Pedido</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body class="bg-light">

    <div class="container py-5">

        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h3 class="mb-0 fw-bold text-center">
                    <i class="bi bi-box-seam me-2"></i>
                    DETALLE DEL PEDIDO #<?= $pedido['id_pedido'] ?>
                </h3>
            </div>

            <div class="card-body">

                <!-- INFO PEDIDO -->
                <div class="row mb-4">

                    <div class="col-md-3">
                        <strong>Proveedor:</strong><br>
                        <?= htmlspecialchars($pedido['proveedor']) ?>
                    </div>

                    <div class="col-md-3">
                        <strong>Fecha pedido:</strong><br>
                        <?= date('d/m/Y H:i', strtotime($pedido['fecha_pedido'])) ?>
                    </div>

                    <div class="col-md-3">
                        <strong>Empleado:</strong><br>
                        <?= htmlspecialchars($pedido['empleado']) ?>
                    </div>

                    <div class="col-md-3">
                        <strong>Estatus:</strong><br>

                        <?php
                        $badge = match ($pedido['estatus']) {
                            'Pendiente' => 'bg-warning text-dark',
                            'Recibido' => 'bg-success',
                            'cancelado' => 'bg-danger',
                            default => 'bg-secondary'
                        };
                        ?>

                        <span class="badge rounded-pill <?= $badge ?>">
                            <?= $pedido['estatus'] ?>
                        </span>

                    </div>
                </div>

                <!-- TABLA PRODUCTOS -->
                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle">

                        <thead class="table-dark text-center">
                            <tr>
                                <th>Producto</th>
                                <th>Cantidad</th>
                                <th>Precio compra</th>
                            </tr>
                        </thead>

                        <tbody>

                            <?php if (count($productos) > 0): ?>

                                <?php foreach ($productos as $pr): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($pr['nombre']) ?></td>
                                        <td class="text-center fw-bold"><?= $pr['cantidad'] ?></td>
                                        <td class="text-center">
                                            $<?= number_format($pr['precio_compra'], 2) ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>

                                <tr class="table-secondary fw-bold">
                                    <td colspan="2" class="text-end">TOTAL DEL PEDIDO</td>
                                    <td class="text-center">
                                        $<?= number_format($total_pedido, 2) ?>
                                    </td>
                                </tr>

                            <?php else: ?>

                                <tr>
                                    <td colspan="3" class="text-center py-4 text-muted">
                                        No hay productos en este pedido
                                    </td>
                                </tr>

                            <?php endif; ?>

                        </tbody>
                    </table>
                </div>

                <!-- BOTONES -->
                <div class="d-flex justify-content-between mt-4">

                    <a href="pedidos.php" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left"></i> Volver
                    </a>

                    <?php if ($pedido['estatus'] == 'pendiente'): ?>
                        <form method="POST" id="formCerrar">
                            <input type="hidden" name="cerrar_pedido" value="1">
                            <input type="hidden" name="id_pedido" value="<?= $pedido['id_pedido'] ?>">

                            <button type="button" onclick="confirmar()" class="btn btn-success">
                                <i class="bi bi-check-circle"></i>
                                Marcar como recibido
                            </button>
                        </form>
                    <?php endif; ?>

                </div>

            </div>
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