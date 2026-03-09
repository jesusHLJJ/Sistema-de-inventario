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

/* =========================
   CONFIGURACIÓN DE SECTORES
========================= */
$negocios_general = [1, 2]; // Abarrotes y Papelería
$negocio_dulceria = [3];    // Dulcería

$sector_visualizar = $_GET['sector_ver'] ?? 'general';

/* =========================
   FILTROS LÓGICA PHP
========================= */
$where = [];
$params = [];
$types = "";

if (!empty($_GET['busqueda'])) {
    $busqueda = "%" . $_GET['busqueda'] . "%";
    $where[] = "(v.id_venta LIKE ? OR v.monto LIKE ?)";
    $params[] = $busqueda;
    $params[] = $busqueda;
    $types .= "ss";
}
if (!empty($_GET['dia'])) {
    $where[] = "DATE(v.fecha) = ?";
    $params[] = $_GET['dia'];
    $types .= "s";
}
if (!empty($_GET['mes'])) {
    $where[] = "MONTH(v.fecha) = ?";
    $params[] = $_GET['mes'];
    $types .= "i";
}
if (!empty($_GET['anio'])) {
    $where[] = "YEAR(v.fecha) = ?";
    $params[] = $_GET['anio'];
    $types .= "i";
}

/* =========================
   PAGINACIÓN
========================= */
$limite = 10;
$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
if ($pagina < 1) $pagina = 1;
$offset = ($pagina - 1) * $limite;

$sql_count = "SELECT COUNT(*) as total FROM venta v";
if ($where) {
    $sql_count .= " WHERE " . implode(" AND ", $where);
}
$stmt_count = $conexion->prepare($sql_count);
if ($params) {
    $stmt_count->bind_param($types, ...$params);
}
$stmt_count->execute();
$total_registros = $stmt_count->get_result()->fetch_assoc()['total'];
$total_paginas = ceil($total_registros / $limite);

$sql = "SELECT v.id_venta, v.fecha, v.monto, e.estatus FROM venta v JOIN estatus e ON e.id_estatus = v.id_estatus";
if ($where) {
    $sql .= " WHERE " . implode(" AND ", $where);
}
$sql .= " ORDER BY v.id_venta DESC LIMIT ? OFFSET ?";
$stmt = $conexion->prepare($sql);
$params_final = $params;
$params_final[] = $limite;
$params_final[] = $offset;
$stmt->bind_param($types . "ii", ...$params_final);
$stmt->execute();
$ventas = $stmt->get_result();

/* =========================
   LÓGICA DE CAJA
========================= */
$sql_caja = "SELECT * FROM caja WHERE DATE(fecha_apertura) = CURDATE() AND sector = ? ORDER BY id_caja DESC LIMIT 1";
$stmt_caja = $conexion->prepare($sql_caja);
$stmt_caja->bind_param("s", $sector_visualizar);
$stmt_caja->execute();
$caja = $stmt_caja->get_result()->fetch_assoc();

/* =========================
   CÁLCULO DE DESGLOSE / TOTALES
========================= */
$desglose = [];
$total_acumulado_ventas = 0;
$conteo_ventas = 0;

$ids_filtro = ($sector_visualizar == 'dulceria') ? implode(",", $negocio_dulceria) : implode(",", $negocios_general);

$sql_negocios = "SELECT n.id_negocio, n.nombre_negocio, SUM(dv.totalproducto) as total, COUNT(DISTINCT v.id_venta) as tickets
                 FROM detalles_venta dv 
                 JOIN venta v ON v.id_venta = dv.id_venta 
                 JOIN negocio n ON n.id_negocio = dv.id_negocio 
                 WHERE DATE(v.fecha) = CURDATE() AND v.id_estatus = 2 AND n.id_negocio IN ($ids_filtro)
                 GROUP BY n.id_negocio";
$res_negocios = $conexion->query($sql_negocios);

while ($row = $res_negocios->fetch_assoc()) {
    $desglose[] = $row;
    $total_acumulado_ventas += $row['total'];
    $conteo_ventas += $row['tickets'];
}

/* =========================
   ACCIONES POST
========================= */
if (isset($_POST['abrir_caja'])) {
    $dinero = $_POST['dinero_inicial'];
    $sec = $_POST['sector'];
    $stmt = $conexion->prepare("INSERT INTO caja (dinero_inicial, id_empleado, sector, estatus) VALUES (?, ?, ?, 'abierta')");
    $stmt->bind_param("dis", $dinero, $_SESSION['id_empleado'], $sec);
    if ($stmt->execute()) {
        $_SESSION['msg_tipo'] = 'success';
        $_SESSION['msg_texto'] = "Caja del sector $sec abierta correctamente.";
    }
    header("Location: ventas.php?sector_ver=$sec");
    exit;
}
if (isset($_POST['cerrar_caja'])) {
    $t_final = $caja['dinero_inicial'] + $total_acumulado_ventas;
    $stmt = $conexion->prepare("UPDATE caja SET total_ventas = ?, total_final = ?, fecha_cierre = NOW(), estatus = 'cerrada' WHERE id_caja = ?");
    $stmt->bind_param("ddi", $total_acumulado_ventas, $t_final, $caja['id_caja']);
    if ($stmt->execute()) {
        $_SESSION['msg_tipo'] = 'success';
        $_SESSION['msg_texto'] = "Caja cerrada. Total en efectivo: $" . number_format($t_final, 2);
    }
    header("Location: ventas.php?sector_ver=" . $caja['sector']);
    exit;
}
if (isset($_POST['reabrir_caja'])) {
    if ($conexion->query("UPDATE caja SET estatus = 'abierta', fecha_cierre = NULL WHERE id_caja = " . $caja['id_caja'])) {
        $_SESSION['msg_tipo'] = 'info';
        $_SESSION['msg_texto'] = "Caja reabierta.";
    }
    header("Location: ventas.php?sector_ver=" . $caja['sector']);
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Caja y Ventas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        html,
        body {
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

        /* Ajustes de fondo blanco para descanso visual */
        .header-box {
            background-color: #ffffff;
            border-radius: 10px;
            border: 1px solid #e3e6f0;
        }

        .tab-container {
            background-color: #ffffff;
            border-radius: 10px;
            padding: 10px;
            border: 1px solid #e3e6f0;
        }

        .alert-custom-info {
            background-color: #d1f3ff;
            border: 1px solid #b6e9ff;
            color: #055160;
            border-radius: 8px;
        }

        .bg-gray-panel {
            background-color: #ced4da;
            border-radius: 8px;
            color: #495057;
            border: 1px solid #adb5bd;
        }

        .text-summary-label {
            color: #6c757d;
            font-weight: bold;
            font-size: 0.85rem;
        }

        .text-summary-value {
            color: #343a40;
            font-weight: bold;
            font-size: 2.2rem;
        }

        .text-summary-money {
            color: #198754;
            font-weight: bold;
            font-size: 2.2rem;
        }

        .btn-close-caja {
            background-color: #dc3545;
            color: white;
            border-radius: 6px;
            font-weight: 500;
            border: none;
        }

        .btn-close-caja:hover {
            background-color: #bb2d3b;
            color: white;
        }
    </style>
</head>

<body>
    <div class="wrapper">
        <?php $pagina_activa = 'ventas';
        include '../componentes/sidebar.php'; ?>

        <div class="main-content">
            <div class="container py-4">

                <div class="header-box shadow-sm p-3 mb-4 text-center">
                    <h3 class="fw-bold mb-0 text-dark"><i class="bi bi-clock-history me-2 text-primary"></i>CONTROL DE CAJA Y VENTAS</h3>
                </div>

                <div class="tab-container shadow-sm mb-4">
                    <div class="btn-group w-100" role="group">
                        <a href="?sector_ver=general" class="btn <?= ($sector_visualizar == 'general') ? 'btn-primary' : 'btn-outline-secondary border-0' ?> py-3 fw-bold rounded-start">
                            <i class="bi bi-shop me-2"></i> ABARROTES / PAPELERÍA
                        </a>
                        <a href="?sector_ver=dulceria" class="btn <?= ($sector_visualizar == 'dulceria') ? 'btn-primary' : 'btn-outline-secondary border-0' ?> py-3 fw-bold rounded-end">
                            <i class="bi bi-candy-cane me-2"></i> DULCERÍA
                        </a>
                    </div>
                </div>

                <?php if ($caja && $caja['estatus'] == 'abierta'): ?>
                    <div class="alert alert-custom-info shadow-sm p-4 mb-3 d-flex justify-content-between align-items-center">
                        <div>
                            <span class="badge bg-primary text-uppercase px-3 py-2 mb-2"><?= $caja['sector'] ?></span>
                            <div class="fs-5">Caja abierta | Dinero inicial: <strong>$<?= number_format($caja['dinero_inicial'], 2) ?></strong></div>
                        </div>
                        <form method="POST" id="formCerrar">
                            <button type="button" onclick="confirmarCierre()" class="btn btn-close-caja py-2 px-4 shadow-sm">
                                <i class="bi bi-lock-fill me-1"></i> Cerrar Caja
                            </button>
                            <input type="hidden" name="cerrar_caja" value="1">
                        </form>
                    </div>

                    <div class="bg-gray-panel shadow-sm p-4 mb-4">
                        <div class="row text-center align-items-center">
                            <div class="col-md-6 border-end border-secondary">
                                <div class="text-summary-label text-uppercase small">VENTAS DEL DÍA (<?= strtoupper($caja['sector']) ?>)</div>
                                <div class="text-summary-value"><?= $conteo_ventas ?></div>
                            </div>
                            <div class="col-md-6">
                                <div class="text-summary-label text-uppercase small">TOTAL VENDIDO HOY (<?= strtoupper($caja['sector']) ?>)</div>
                                <div class="text-summary-money">$<?= number_format($total_acumulado_ventas, 2) ?></div>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-4 g-3">
                        <?php foreach ($desglose as $r): ?>
                            <div class="col-md-4">
                                <div class="card h-100 shadow-sm border-0 bg-white">
                                    <div class="card-body text-center">
                                        <div class="text-muted text-uppercase small fw-bold mb-1"><?= $r['nombre_negocio'] ?></div>
                                        <div class="h4 fw-bold text-primary mb-0">$<?= number_format($r['total'], 2) ?></div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                <?php elseif ($caja && $caja['estatus'] == 'cerrada'): ?>
                    <div class="alert alert-secondary shadow-sm p-3 mb-3 d-flex justify-content-between align-items-center bg-white border">
                        <div class="fw-bold text-muted text-uppercase">Caja <?= $sector_visualizar ?> Cerrada</div>
                        <form method="POST" id="formReabrir">
                            <button type="button" onclick="confirmarReapertura()" class="btn btn-warning btn-sm fw-bold px-3">
                                <i class="bi bi-arrow-counterclockwise"></i> REABRIR CAJA
                            </button>
                            <input type="hidden" name="reabrir_caja" value="1">
                        </form>
                    </div>

                    <div class="bg-gray-panel shadow-sm p-4 mb-4">
                        <div class="row align-items-center">
                            <div class="col-md-4 border-end border-secondary text-start">
                                <div class="text-summary-label text-uppercase mb-2">Desglose de Ventas</div>
                                <div class="small">
                                    <?php foreach ($desglose as $d): ?>
                                        <div class="d-flex justify-content-between mb-1">
                                            <span>Vendido de <?= $d['nombre_negocio'] ?>:</span>
                                            <span class="fw-bold">$<?= number_format($d['total'], 2) ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                    <hr class="my-1 border-secondary">
                                    <div class="d-flex justify-content-between">
                                        <span>Vendido Total:</span>
                                        <span class="fw-bold">$<?= number_format($total_acumulado_ventas, 2) ?></span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4 border-end border-secondary text-center">
                                <div class="text-summary-label text-uppercase">Había en Caja Inicial</div>
                                <div class="text-summary-value">$<?= number_format($caja['dinero_inicial'], 2) ?></div>
                            </div>
                            <div class="col-md-4 text-center">
                                <div class="text-summary-label text-uppercase">Total (Caja + Venta)</div>
                                <div class="text-summary-money">$<?= number_format($caja['dinero_inicial'] + $total_acumulado_ventas, 2) ?></div>
                            </div>
                        </div>
                    </div>

                <?php else: ?>
                    <div class="alert alert-warning shadow-sm p-5 text-center mb-4 border-dashed bg-white">
                        <h4 class="fw-bold">SIN REGISTRO DE CAJA HOY (<?= strtoupper($sector_visualizar) ?>)</h4>
                        <form method="POST" class="row g-2 justify-content-center mt-3">
                            <input type="hidden" name="sector" value="<?= $sector_visualizar ?>">
                            <div class="col-md-3">
                                <input type="number" step="0.01" name="dinero_inicial" class="form-control form-control-lg text-center" placeholder="Monto inicial $" required>
                            </div>
                            <div class="col-md-3">
                                <button type="submit" name="abrir_caja" class="btn btn-success btn-lg w-100 fw-bold shadow-sm">ABRIR CAJA</button>
                            </div>
                        </form>
                    </div>
                <?php endif; ?>

                <div class="card shadow-sm border-0 bg-white">
                    <div class="card-header bg-white py-3 fw-bold text-muted border-bottom">HISTORIAL DE TRANSACCIONES</div>
                    <div class="card-body p-4">
                        <form method="GET" class="row g-2 mb-4 bg-light p-2 rounded border">
                            <input type="hidden" name="sector_ver" value="<?= $sector_visualizar ?>">
                            <div class="col-md-4"><input type="text" name="busqueda" class="form-control" placeholder="Buscar Ticket o Monto" value="<?= htmlspecialchars($_GET['busqueda'] ?? '') ?>"></div>
                            <div class="col-md-3"><input type="date" name="dia" class="form-control" value="<?= $_GET['dia'] ?? '' ?>"></div>
                            <div class="col-md-2"><button class="btn btn-primary w-100">Filtrar</button></div>
                            <div class="col-md-2"><a href="ventas.php?sector_ver=<?= $sector_visualizar ?>" class="btn btn-secondary w-100 text-decoration-none">Limpiar</a></div>
                        </form>

                        <div class="table-responsive">
                            <table class="table table-hover border-bottom mb-0">
                                <thead class="table-dark text-center">
                                    <tr>
                                        <th>Ticket</th>
                                        <th>Fecha</th>
                                        <th>Estatus</th>
                                        <th>Total</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($v = $ventas->fetch_assoc()): ?>
                                        <tr>
                                            <td class="text-center fw-bold">#<?= str_pad($v['id_venta'], 5, "0", STR_PAD_LEFT) ?></td>
                                            <td class="text-center"><?= date('d/m/Y H:i', strtotime($v['fecha'])) ?></td>
                                            <td class="text-center"><span class="badge rounded-pill <?= ($v['estatus'] == 'Finalizada') ? 'bg-success' : 'bg-danger' ?>"><?= $v['estatus'] ?></span></td>
                                            <td class="text-end fw-bold text-success">$<?= number_format($v['monto'], 2) ?></td>
                                            <td class="text-center"><a href="venta_detalle.php?id_venta=<?= $v['id_venta'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></a></td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>

                        <?php if ($total_paginas > 1): ?>
                            <nav class="mt-4">
                                <ul class="pagination justify-content-center mb-0">
                                    <?php $qs = $_GET; ?>
                                    <li class="page-item <?= ($pagina <= 1) ? 'disabled' : '' ?>">
                                        <a class="page-link" href="?<?= http_build_query(array_merge($qs, ['pagina' => $pagina - 1])) ?>">Anterior</a>
                                    </li>
                                    <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                                        <li class="page-item <?= ($pagina == $i) ? 'active' : '' ?>">
                                            <a class="page-link" href="?<?= http_build_query(array_merge($qs, ['pagina' => $i])) ?>"><?= $i ?></a>
                                        </li>
                                    <?php endfor; ?>
                                    <li class="page-item <?= ($pagina >= $total_paginas) ? 'disabled' : '' ?>">
                                        <a class="page-link" href="?<?= http_build_query(array_merge($qs, ['pagina' => $pagina + 1])) ?>">Siguiente</a>
                                    </li>
                                </ul>
                            </nav>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php include '../componentes/footer.php'; ?>
        </div>
    </div>

    <script>
        <?php if (isset($_SESSION['msg_tipo'])): ?>
            Swal.fire({
                icon: '<?= $_SESSION['msg_tipo'] ?>',
                title: '<?= $_SESSION['msg_tipo'] == 'success' ? "¡Éxito!" : "Notificación" ?>',
                text: '<?= $_SESSION['msg_texto'] ?>',
                timer: 3500,
                showConfirmButton: false
            });
            <?php unset($_SESSION['msg_tipo']);
            unset($_SESSION['msg_texto']); ?>
        <?php endif; ?>

        function confirmarCierre() {
            Swal.fire({
                title: '¿Cerrar caja?',
                text: "Se realizará el corte definitivo del sector actual.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Sí, cerrar caja',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('formCerrar').submit();
                }
            })
        }

        function confirmarReapertura() {
            Swal.fire({
                title: '¿Reabrir caja?',
                text: "La caja volverá a estar disponible para ventas.",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#ffc107',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Sí, reabrir',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('formReabrir').submit();
                }
            })
        }
    </script>
</body>

</html>