<?php
session_start();
require_once "conexion.php";

if (!isset($_SESSION['id_empleado'])) {
    header("Location: ../index.php");
    exit;
}

$total_pagar = 0;

if (isset($_SESSION['id_venta'])) {
    $id_venta = $_SESSION['id_venta'];

    $sql = "SELECT monto FROM venta WHERE id_venta = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $id_venta);
    $stmt->execute();
    $total_pagar = $stmt->get_result()->fetch_assoc()['monto'] ?? 0;
}

$id_empleado = $_SESSION['id_empleado'];

//  detalles carrito
$detalles = null;

if (isset($_SESSION['id_venta'])) {
    $id_venta = $_SESSION['id_venta'];

    $sql = "SELECT d.id_detalles_venta, p.nombre, d.cantidad, d.totalproducto
            FROM detalles_venta d
            JOIN producto p ON p.id_producto = d.id_producto
            WHERE d.id_venta = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $id_venta);
    $stmt->execute();
    $detalles = $stmt->get_result();
}

$check_caja = $conexion->query("
    SELECT * FROM caja 
    WHERE DATE(fecha_apertura) = CURDATE()
    AND estatus = 'abierta'
");

$mostrar_alerta_caja = false;

if ($check_caja->num_rows == 0) {
    $mostrar_alerta_caja = true;
}

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
                            <input type="text" name="id_producto" id="id_producto" class="form-control" placeholder="Escanea o escribe el ID" required autofocus>
                            <button type="button"
                                class="btn btn-outline-primary w-100 mt-2 d-md-none"
                                onclick="abrirScanner()">
                                <i class="bi bi-camera"></i> Escanear con cámara
                            </button>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Cantidad</label>
                            <input type="number"
                                name="cantidad"
                                class="form-control"
                                placeholder="1"
                                value="1"
                                step="0.001"
                                min="0.001"
                                required>
                        </div>
                        <div class="col-md-3">
                            <button class="btn btn-success w-100"><i class="bi bi-plus-lg"></i> Agregar</button>
                        </div>
                    </form>
                    <div id="scanner-container" class="mt-3 d-none">
                        <video id="video" autoplay playsinline style="width:100%;"></video>
                        <button class="btn btn-danger w-100 mt-2" onclick="cerrarScanner()">
                            Cerrar cámara
                        </button>
                    </div>


                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead class="table-dark">
                                <tr>
                                    <th>Producto</th>
                                    <th>Cantidad</th>
                                    <th>Total</th>
                                    <th>Quitar</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($detalles && $detalles->num_rows > 0): ?>

                                    <?php while ($row = $detalles->fetch_assoc()): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($row['nombre']) ?></td>
                                            <td><?= htmlspecialchars($row['cantidad']) ?></td>
                                            <td>$<?= number_format($row['totalproducto'], 2) ?></td>
                                            <td class="text-center">
                                                <form action="eliminar_producto_venta.php" method="POST"
                                                    onsubmit="return confirm('¿Quitar este producto de la venta?')">
                                                    <input type="hidden" name="id_detalles_venta"
                                                        value="<?= $row['id_detalles_venta'] ?>">
                                                    <button class="btn btn-danger btn-sm">
                                                        <i class="bi bi-x-lg"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>


                                    <tr class="table-dark fw-bold fs-5">
                                        <td colspan="3" class="text-center">TOTAL A PAGAR</td>
                                        <td>$<?= number_format($total_pagar, 2) ?></td>
                                    </tr>

                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="text-center text-muted">
                                            Aún no hay productos en la venta
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="d-grid gap-2 mt-3">


                        <form action="../controladores/confirmar_venta.php" method="POST">
                            <input type="hidden" name="accion" value="confirmar">
                            <button type="submit" class="btn btn-primary btn-lg w-100">
                                <i class="bi bi-check-circle-fill"></i> Confirmar Venta
                            </button>
                        </form>


                        <form action="../controladores/confirmar_venta.php" method="POST"
                            onsubmit="return confirm('¿Seguro que deseas cancelar la venta?')">
                            <input type="hidden" name="accion" value="cancelar">
                            <button type="submit" class="btn btn-danger btn-lg w-100">
                                <i class="bi bi-x-lg"></i> Cancelar Venta
                            </button>
                        </form>

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
    <script src="https://unpkg.com/html5-qrcode"></script>
    <script>
        let video = null;
        let stream = null;
        let detector = null;
        let escaneando = false;

        async function abrirScanner() {

            if (!('BarcodeDetector' in window)) {
                alert("Tu navegador no soporta lector de códigos");
                return;
            }

            document.getElementById('scanner-container').classList.remove('d-none');
            video = document.getElementById('video');

            detector = new BarcodeDetector({
                formats: ['ean_13', 'ean_8', 'code_128', 'upc_a']
            });

            stream = await navigator.mediaDevices.getUserMedia({
                video: {
                    facingMode: "environment",
                    width: {
                        ideal: 1280
                    },
                    height: {
                        ideal: 720
                    }
                }
            });

            video.srcObject = stream;
            escaneando = true;
            detectar();
        }

        async function detectar() {
            if (!escaneando) return;

            try {
                const codigos = await detector.detect(video);

                if (codigos.length > 0) {
                    const codigo = codigos[0].rawValue;

                    document.getElementById('id_producto').value = codigo;
                    navigator.vibrate?.(200);

                    cerrarScanner();
                    document.querySelector('input[name="cantidad"]').focus();
                    return;
                }
            } catch (e) {
                console.error(e);
            }

            requestAnimationFrame(detectar);
        }

        function cerrarScanner() {
            escaneando = false;

            if (stream) {
                stream.getTracks().forEach(t => t.stop());
            }

            document.getElementById('scanner-container').classList.add('d-none');
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <?php if (!empty($mostrar_alerta_caja)): ?>
        <script>
            document.addEventListener("DOMContentLoaded", function() {
                Swal.fire({
                    icon: 'error',
                    title: 'Caja cerrada',
                    text: 'Debe abrir la caja antes de vender.',
                    confirmButtonColor: '#d33'
                }).then(() => {
                    window.location.href = 'ventas.php';
                });
            });
        </script>
    <?php endif; ?>
</body>

</html>