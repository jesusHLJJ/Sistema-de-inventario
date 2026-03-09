<?php
session_start();
// Asegúrate de que la ruta a conexión sea correcta. 
// Si prestamos.php está en "vistas/", entonces "conexion.php" debe estar ahí también.
require_once "conexion.php"; 

if (!isset($_SESSION['id_empleado'])) {
    header("Location: ../index.php");
    exit;
}


$sql = "SELECT v.id_venta, v.fecha, v.monto, v.cliente_prestamo 
        FROM venta v 
        WHERE v.id_estatus = 4 
        ORDER BY v.fecha DESC";

$resultado = $conexion->query($sql);

if (!$resultado) {
    die("Error en la consulta: " . $conexion->error);
}

// Calcular total global de deudas
$total_deudas = 0;
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Préstamos</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        html, body { height: 100%; overflow: hidden; }
        .wrapper { display: flex; height: 100vh; width: 100%; }
        .main-content { flex-grow: 1; height: 100vh; overflow-y: auto; background-color: #f8f9fa; display: flex; flex-direction: column; }
        
        .header-box { background-color: #ffffff; border-radius: 10px; border: 1px solid #e3e6f0; }
        .table-container { background-color: #ffffff; border-radius: 10px; border: 1px solid #e3e6f0; overflow: hidden; }
    </style>
</head>
<body>
    <div class="wrapper">
        <?php $pagina_activa = 'prestamos'; include '../componentes/sidebar.php'; ?>
        
        <div class="main-content">
            <div class="container py-4">
                
                <div class="header-box shadow-sm p-3 mb-4 text-center">
                    <h3 class="fw-bold mb-0 text-dark">
                        <i class="bi bi-person-badge me-2 text-primary"></i>PRÉSTAMOS PENDIENTES
                    </h3>
                </div>

                <div class="row">
                    <div class="col-12">
                        <div class="table-container shadow-sm">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-dark">
                                        <tr>
                                            <th class="ps-3"># Ticket</th>
                                            <th>Fecha del Préstamo</th>
                                            <th>Nombre del Cliente</th>
                                            <th class="text-end">Monto de Deuda</th>
                                            <th class="text-center pe-3">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ($resultado->num_rows > 0): ?>
                                            <?php while ($v = $resultado->fetch_assoc()): 
                                                $total_deudas += $v['monto']; ?>
                                                <tr class="bg-white">
                                                    <td class="ps-3 fw-bold">#<?= str_pad($v['id_venta'], 5, "0", STR_PAD_LEFT) ?></td>
                                                    <td><?= date('d/m/Y H:i', strtotime($v['fecha'])) ?></td>
                                                    <td>
                                                        <span class="badge bg-light text-dark border px-3">
                                                            <i class="bi bi-person me-1"></i><?= htmlspecialchars($v['cliente_prestamo']) ?>
                                                        </span>
                                                    </td>
                                                    <td class="text-end fw-bold text-danger fs-5">$<?= number_format($v['monto'], 2) ?></td>
                                                    <td class="text-center pe-3">
                                                        <div class="btn-group">
                                                            <a href="venta_detalle.php?id_venta=<?= $v['id_venta'] ?>" class="btn btn-outline-primary btn-sm shadow-sm">
                                                                <i class="bi bi-eye"></i> Ver Detalle
                                                            </a>
                                                            <button onclick="liquidarDeuda(<?= $v['id_venta'] ?>, '<?= addslashes($v['cliente_prestamo']) ?>', <?= $v['monto'] ?>)" class="btn btn-success btn-sm shadow-sm">
                                                                <i class="bi bi-cash-coin"></i> Registrar Pago
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="5" class="text-center py-5 text-muted bg-white">
                                                    <i class="bi bi-check-circle fs-1 d-block mb-2 text-success"></i>
                                                    No hay préstamos pendientes por cobrar.
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                    <?php if ($total_deudas > 0): ?>
                                    <tfoot class="table-light border-top">
                                        <tr>
                                            <td colspan="3" class="text-end fw-bold text-uppercase p-3">Total por Recuperar:</td>
                                            <td class="text-end fw-bold text-danger fs-4 p-3">$<?= number_format($total_deudas, 2) ?></td>
                                            <td></td>
                                        </tr>
                                    </tfoot>
                                    <?php endif; ?>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
            <?php include '../componentes/footer.php'; ?>
        </div>
    </div>

    <script>
    function liquidarDeuda(id, cliente, monto) {
        Swal.fire({
            title: '¿Registrar pago de deuda?',
            text: `¿Confirmas que recibiste los $${monto.toFixed(2)} de ${cliente}? Esto entrará a la caja actual.`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#198754',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, recibió pago',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                // RUTA CORREGIDA AL CONTROLADOR
                window.location.href = `../controladores/cobrar_prestamo.php?id_venta=${id}`;
            }
        });
    }

    // Alertas de SweetAlert para mensajes de sesión
    <?php if (isset($_SESSION['msg_tipo'])): ?>
        Swal.fire({
            icon: '<?= $_SESSION['msg_tipo'] ?>',
            title: 'Notificación',
            text: '<?= $_SESSION['msg_texto'] ?>',
            timer: 3000,
            showConfirmButton: false
        });
        <?php unset($_SESSION['msg_tipo']); unset($_SESSION['msg_texto']); ?>
    <?php endif; ?>
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>