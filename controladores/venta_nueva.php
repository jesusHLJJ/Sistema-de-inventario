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

$check_caja = $conexion->query("SELECT * FROM caja WHERE DATE(fecha_apertura) = CURDATE() AND estatus = 'abierta'");
$mostrar_alerta_caja = ($check_caja->num_rows == 0);
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
    <button class="btn btn-outline-dark d-md-none" data-bs-toggle="collapse" data-bs-target="#sidebar">
        <i class="bi bi-list fs-4"></i>
    </button>
    <div class="wrapper">
        <?php $pagina = 'venta_nueva';
        include '../componentes/sidebar.php'; ?>

        <div class="main-content">
            <div class="container py-5">
                <div class="card shadow-lg p-4 mx-auto" style="max-width: 1000px;">
                    <h3 class="mb-4 text-center fw-bold text-dark">NUEVA TRANSACCIÓN</h3>

                    <div class="row g-4">
                        <div class="col-md-8">
                            <form id="formVenta" action="carrito_venta.php" method="POST" class="row g-3 mb-4 align-items-end">
                                <input type="hidden" id="tipo_producto" value="pieza">
                                <div class="col-12">
                                    <label class="form-label fw-bold small">Buscar Producto</label>
                                    <input type="text" name="id_producto" id="id_producto" class="form-control" placeholder="Escanea o escribe nombre" required autofocus autocomplete="off" list="lista_productos" oninput="buscarSugerencias(this.value)">
                                    <datalist id="lista_productos"></datalist>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-bold small">Cantidad</label>
                                    <input type="number" name="cantidad" id="cantidad" class="form-control" value="1" step="0.001" min="0.001" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-bold small">Precio</label>
                                    <div class="input-group">
                                        <span class="input-group-text">$</span>
                                        <input type="number" name="precio_venta" id="precio_venta" class="form-control" step="0.01" readonly required>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <button type="submit" class="btn btn-success w-100"><i class="bi bi-plus-lg"></i> Agregar</button>
                                </div>
                            </form>

                            <div class="table-responsive">
                                <table class="table table-bordered table-striped align-middle">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>Producto</th>
                                            <th>Cant.</th>
                                            <th>Total</th>
                                            <th class="text-center">Quitar</th>
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
                                                        <form action="eliminar_producto_venta.php" method="POST">
                                                            <input type="hidden" name="id_detalles_venta" value="<?= $row['id_detalles_venta'] ?>">
                                                            <button class="btn btn-danger btn-sm"><i class="bi bi-trash"></i></button>
                                                        </form>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                            <tr class="table-info fw-bold fs-5">
                                                <td colspan="2" class="text-center">TOTAL</td>
                                                <td colspan="2">$<?= number_format($total_pagar, 2) ?></td>
                                            </tr>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="4" class="text-center text-muted py-4">No hay productos</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="d-grid gap-2 mb-4">
                                <form action="../controladores/confirmar_venta.php" method="POST">
                                    <input type="hidden" name="accion" value="confirmar">
                                    <button type="submit" class="btn btn-primary btn-lg w-100 py-3" <?= ($total_pagar <= 0) ? 'disabled' : '' ?>>
                                        <i class="bi bi-cash-stack me-2"></i> Confirmar venta
                                    </button>
                                </form>
                            </div>

                            <div class="card border-primary">
                                <div class="card-header bg-primary text-white fw-bold small text-center">MODO PRÉSTAMO</div>
                                <div class="card-body">
                                    <label class="form-label small fw-bold">Nombre del Deudor:</label>
                                    <input type="text" id="nombre_cliente" class="form-control mb-3" placeholder="Ej. Juan Pérez">
                                    <button type="button" onclick="confirmarPrestamo()" class="btn btn-outline-primary w-100" <?= ($total_pagar <= 0) ? 'disabled' : '' ?>>
                                        <i class="bi bi-person-fill-exclamation me-2"></i> Registrar Préstamo
                                    </button>
                                </div>
                            </div>

                            <form action="../controladores/confirmar_venta.php" method="POST" onsubmit="return confirm('¿Cancelar venta?')">
                                <input type="hidden" name="accion" value="cancelar">
                                <button type="submit" class="btn btn-link text-danger w-100 mt-3 text-decoration-none" <?= ($total_pagar <= 0) ? 'disabled' : '' ?>>
                                    <i class="bi bi-x-circle"></i> Cancelar venta
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <?php include '../componentes/footer.php'; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        const inputId = document.getElementById('id_producto');
        const inputPrecio = document.getElementById('precio_venta');
        const inputCantidad = document.getElementById('cantidad');
        const tipoProd = document.getElementById('tipo_producto');
        const form = document.getElementById('formVenta');
        const datalist = document.getElementById('lista_productos');

        function buscarSugerencias(valor) {
            if (valor.length < 2) {
                datalist.innerHTML = "";
                return;
            }
            fetch(`../controladores/sugerencias_productos.php?query=${valor}`)
                .then(r => r.json())
                .then(data => {
                    datalist.innerHTML = "";
                    data.forEach(item => {
                        const option = document.createElement('option');
                        option.value = item.id_producto;
                        option.label = item.nombre;
                        datalist.appendChild(option);
                    });
                });
        }

        function verificarProducto(id) {
            if (!id) return;
            fetch(`../controladores/obtener_producto.php?id=${id}`)
                .then(r => r.json())
                .then(data => {
                    if (data.error) return;
                    inputPrecio.value = data.precio;
                    tipoProd.value = data.venta_por;
                    if (data.venta_por === 'granel') {
                        inputPrecio.readOnly = false;
                        inputPrecio.style.backgroundColor = "#fff3cd";
                        inputPrecio.focus();
                        inputPrecio.select();
                    } else {
                        form.submit();
                    }
                });
        }

        inputId.addEventListener('keydown', function(e) {
            if (e.keyCode === 13) {
                e.preventDefault();
                verificarProducto(this.value);
            }
        });

        // FUNCIÓN PARA PRÉSTAMOS
        function confirmarPrestamo() {
            const cliente = document.getElementById('nombre_cliente').value;
            if (cliente.trim() === "") {
                Swal.fire('Atención', 'Por favor ingrese el nombre del cliente para el préstamo.', 'warning');
                return;
            }

            Swal.fire({
                title: '¿Confirmar Préstamo?',
                text: `Se registrará la deuda a nombre de ${cliente}.`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Sí, registrar',
                cancelButtonText: 'No, esperar'
            }).then((result) => {
                if (result.isConfirmed) {
                    const f = document.createElement('form');
                    f.method = 'POST';
                    f.action = '../controladores/confirmar_prestamo.php';
                    const i = document.createElement('input');
                    i.type = 'hidden';
                    i.name = 'cliente';
                    i.value = cliente;
                    f.appendChild(i);
                    document.body.appendChild(f);
                    f.submit();
                }
            });
        }
    </script>

    <?php if (isset($_SESSION['venta_status'])): ?>
        <script>
            Swal.fire({
                icon: '<?= ($_SESSION['venta_status'] == "success") ? "success" : "error" ?>',
                title: '<?= ($_SESSION['venta_status'] == "success") ? "¡Listo!" : "Error" ?>',
                text: '<?= $_SESSION['venta_msg'] ?? "" ?>',
                timer: 2000,
                showConfirmButton: false
            });
        </script>
        <?php unset($_SESSION['venta_status']);
        unset($_SESSION['venta_msg']); ?>
    <?php endif; ?>

    <?php if ($mostrar_alerta_caja): ?>
        <script>
            document.addEventListener("DOMContentLoaded", function() {
                Swal.fire({
                        icon: 'error',
                        title: 'Caja cerrada',
                        text: 'Debe abrir la caja antes de vender.'
                    })
                    .then(() => {
                        window.location.href = 'ventas.php';
                    });
            });
        </script>
    <?php endif; ?>
</body>

</html>