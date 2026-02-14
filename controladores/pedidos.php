<?php
session_start();
require_once "conexion.php";

if (!isset($_SESSION['id_empleado'])) {
    header("Location: ../index.php");
    exit;
}

$sql = "SELECT p.*, e.nombre AS empleado
        FROM pedidos p
        LEFT JOIN empleado e ON e.id_empleado = p.id_empleado
        ORDER BY p.id_pedido DESC";

$pedidos = $conexion->query($sql);
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Pedidos</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

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

    <button class="btn btn-outline-dark d-md-none"
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

                <div class="card shadow-sm">

                    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                        <h3 class="mb-0 fw-bold">
                            <i class="bi bi-box-seam me-2"></i> PEDIDOS
                        </h3>

                        <a href="pedidos_nuevo.php" class="btn btn-success">
                            <i class="bi bi-plus-circle"></i> Hacer pedido
                        </a>
                    </div>

                    <div class="card-body p-4">

                        <div class="table-responsive">
                            <table class="table table-hover table-bordered align-middle">

                                <thead class="table-dark text-center">
                                    <tr>
                                        <th># Pedido</th>
                                        <th>Proveedor</th>
                                        <th>Fecha</th>
                                        <th>Empleado</th>
                                        <th>Estatus</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>

                                <tbody>

                                    <?php if ($pedidos->num_rows > 0): ?>
                                        <?php while ($p = $pedidos->fetch_assoc()): ?>

                                            <tr>

                                                <td class="text-center fw-bold">
                                                    #<?= str_pad($p['id_pedido'], 5, "0", STR_PAD_LEFT) ?>
                                                </td>

                                                <td><?= htmlspecialchars($p['proveedor']) ?></td>

                                                <td class="text-center">
                                                    <?= date('d/m/Y', strtotime($p['fecha_pedido'])) ?>
                                                    <small class="text-muted ms-1">
                                                        <?= date('H:i', strtotime($p['fecha_pedido'])) ?>
                                                    </small>
                                                </td>

                                                <td class="text-center"><?= htmlspecialchars($p['empleado']) ?></td>

                                                <td class="text-center">
                                                    <?php
                                                    $badge = match ($p['estatus']) {
                                                        'Pendiente' => 'bg-warning text-dark',
                                                        'Recibido' => 'bg-success',
                                                        default => 'bg-secondary'
                                                    };
                                                    ?>
                                                    <span class="badge rounded-pill <?= $badge ?>">
                                                        <?= ucfirst($p['estatus']) ?>
                                                    </span>
                                                </td>

                                                <td class="text-center">

                                                    <a href="pedido_detalle.php?id=<?= $p['id_pedido'] ?>"
                                                        class="btn btn-sm btn-outline-primary">
                                                        <i class="bi bi-eye-fill"></i> Detalles
                                                    </a>

                                                </td>

                                            </tr>

                                        <?php endwhile; ?>
                                    <?php else: ?>

                                        <tr>
                                            <td colspan="6" class="text-center py-5 text-muted">
                                                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                                No hay pedidos registrados
                                            </td>
                                        </tr>

                                    <?php endif; ?>

                                </tbody>
                            </table>
                        </div>

                    </div>
                </div>

            </div>

            <?php include '../componentes/footer.php'; ?>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>