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
   FILTROS LOGICA PHP
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
   PAGINACION (CÁLCULOS)
========================= */
$limite = 10;
$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
if ($pagina < 1) $pagina = 1;
$offset = ($pagina - 1) * $limite;

// 1. Contar total de registros para saber cuántas páginas hay
$sql_count = "SELECT COUNT(*) as total FROM venta v JOIN estatus e ON e.id_estatus = v.id_estatus";
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

/* =========================
   CONSULTA PRINCIPAL
========================= */
$sql = "SELECT v.id_venta, v.fecha, v.monto, e.estatus
        FROM venta v
        JOIN estatus e ON e.id_estatus = v.id_estatus";

if ($where) {
    $sql .= " WHERE " . implode(" AND ", $where);
}

$sql .= " ORDER BY v.id_venta DESC LIMIT ? OFFSET ?";

$stmt = $conexion->prepare($sql);

// Combinar parámetros de filtros con los de paginación
$params_final = $params;
$params_final[] = $limite;
$params_final[] = $offset;
$types_final = $types . "ii";

$stmt->bind_param($types_final, ...$params_final);
$stmt->execute();
$ventas = $stmt->get_result();

/* =========================
   LÓGICA DE CAJA (CORTE)
========================= */
$sql_corte = "SELECT COUNT(*) AS total_ventas, SUM(monto) AS total_dinero 
              FROM venta WHERE DATE(fecha) = CURDATE() AND id_estatus = 2";
$result_corte = $conexion->query($sql_corte);
$corte = $result_corte->fetch_assoc();
$total_ventas_hoy = $corte['total_ventas'] ?? 0;
$total_dinero_hoy = $corte['total_dinero'] ?? 0;

$sql_caja = "SELECT * FROM caja WHERE DATE(fecha_apertura) = CURDATE() LIMIT 1";
$result_caja = $conexion->query($sql_caja);
$caja = $result_caja->fetch_assoc();

// Acciones de caja (Abrir/Cerrar/Reabrir)
if (isset($_POST['abrir_caja'])) {
    $dinero_inicial = $_POST['dinero_inicial'];
    if (!$caja) {
        $stmt_abrir = $conexion->prepare("INSERT INTO caja (dinero_inicial, id_empleado) VALUES (?, ?)");
        $stmt_abrir->bind_param("di", $dinero_inicial, $_SESSION['id_empleado']);
        $stmt_abrir->execute();
        header("Location: ventas.php");
        exit();
    }
}

if (isset($_POST['cerrar_caja']) && $caja && $caja['estatus'] == 'abierta') {
    $total_final = $caja['dinero_inicial'] + $total_dinero_hoy;
    $stmt_cerrar = $conexion->prepare("UPDATE caja SET total_ventas = ?, total_final = ?, fecha_cierre = NOW(), estatus = 'cerrada' WHERE id_caja = ?");
    $stmt_cerrar->bind_param("ddi", $total_dinero_hoy, $total_final, $caja['id_caja']);
    $stmt_cerrar->execute();
    header("Location: ventas.php");
    exit();
}

if (isset($_POST['reabrir_caja']) && $caja && $caja['estatus'] == 'cerrada') {
    $conexion->query("UPDATE caja SET estatus = 'abierta', fecha_cierre = NULL WHERE id_caja = " . $caja['id_caja']);
    header("Location: ventas.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Historial de Ventas</title>
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
    <div class="wrapper">
        <?php $pagina_activa = 'ventas';
        include '../componentes/sidebar.php'; ?>

        <div class="main-content">
            <div class="container py-5">
                <div class="card shadow-sm">
                    <div class="card-header bg-white py-3">
                        <h3 class="mb-0 fw-bold text-center"><i class="bi bi-clock-history me-2"></i>HISTORIAL DE VENTAS</h3>
                    </div>

                    <div class="card-body p-4">
                        <?php if (!$caja): ?>
                            <div class="alert alert-warning shadow-sm">
                                <form method="POST" class="row g-2 align-items-end">
                                    <div class="col-md-4">
                                        <label class="fw-bold">Dinero inicial</label>
                                        <input type="number" step="0.01" name="dinero_inicial" class="form-control" required>
                                    </div>
                                    <div class="col-md-3">
                                        <button type="submit" name="abrir_caja" class="btn btn-success w-100"><i class="bi bi-unlock-fill"></i> Abrir Caja</button>
                                    </div>
                                </form>
                            </div>
                        <?php elseif ($caja['estatus'] == 'abierta'): ?>
                            <div class="alert alert-info shadow-sm d-flex justify-content-between align-items-center">
                                <div><strong>Caja abierta</strong> | Dinero inicial: <strong>$<?= number_format($caja['dinero_inicial'], 2) ?></strong></div>
                                <form method="POST"><button type="submit" name="cerrar_caja" class="btn btn-danger"><i class="bi bi-lock-fill"></i> Cerrar Caja</button></form>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-success shadow-sm d-flex justify-content-between align-items-center">
                                <div>
                                    <strong>Caja cerrada</strong><br>
                                    Inicial: $<?= number_format($caja['dinero_inicial'], 2) ?> | Ventas: $<?= number_format($caja['total_ventas'], 2) ?> | <strong>Total: $<?= number_format($caja['total_final'], 2) ?></strong>
                                </div>
                                <form method="POST"><button type="submit" name="reabrir_caja" class="btn btn-warning" onclick="return confirm('¿Reabrir caja?')"><i class="bi bi-arrow-counterclockwise"></i> Reabrir</button></form>
                            </div>
                        <?php endif; ?>

                        <div class="alert alert-dark shadow-sm">
                            <div class="row text-center">
                                <div class="col-md-6 border-end">
                                    <h6 class="mb-1 text-uppercase">Ventas del día</h6>
                                    <h3 class="fw-bold"><?= $total_ventas_hoy ?></h3>
                                </div>
                                <div class="col-md-6">
                                    <h6 class="mb-1 text-uppercase">Total vendido hoy</h6>
                                    <h3 class="fw-bold text-success">$<?= number_format($total_dinero_hoy, 2) ?></h3>
                                </div>
                            </div>
                        </div>

                        <form method="GET" class="row g-3 mb-4 bg-light p-3 rounded border align-items-end">
                            <div class="col-12">
                                <label class="form-label fw-bold small">Búsqueda rápida (ID o Monto)</label>
                                <input type="text" name="busqueda" class="form-control" value="<?= htmlspecialchars($_GET['busqueda'] ?? '') ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small">Día</label>
                                <input type="date" name="dia" class="form-control" value="<?= $_GET['dia'] ?? '' ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small">Mes</label>
                                <select name="mes" class="form-select">
                                    <option value="">Todos</option>
                                    <?php
                                    $meses = ["Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre"];
                                    foreach ($meses as $idx => $m): ?>
                                        <option value="<?= $idx + 1 ?>" <?= (($_GET['mes'] ?? '') == $idx + 1) ? 'selected' : '' ?>><?= $m ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small">Año</label>
                                <select name="anio" class="form-select">
                                    <option value="">Todos</option>
                                    <?php for ($y = date('Y'); $y >= 2020; $y--): ?>
                                        <option value="<?= $y ?>" <?= (($_GET['anio'] ?? '') == $y) ? 'selected' : '' ?>><?= $y ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div class="col-md-3 d-flex gap-2">
                                <button class="btn btn-primary w-100">Filtrar</button>
                                <a href="ventas.php" class="btn btn-outline-secondary"><i class="bi bi-x-lg"></i></a>
                            </div>
                        </form>

                        <div class="table-responsive">
                            <table class="table table-hover table-bordered align-middle">
                                <thead class="table-dark text-center">
                                    <tr>
                                        <th># Venta</th>
                                        <th>Fecha y Hora</th>
                                        <th>Estatus</th>
                                        <th>Monto Total</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($ventas->num_rows > 0): ?>
                                        <?php while ($v = $ventas->fetch_assoc()): ?>
                                            <tr>
                                                <td class="text-center fw-bold">#<?= str_pad($v['id_venta'], 5, "0", STR_PAD_LEFT) ?></td>
                                                <td class="text-center"><?= date('d/m/Y H:i', strtotime($v['fecha'])) ?></td>
                                                <td class="text-center">
                                                    <?php
                                                    $badge = match ($v['estatus']) {
                                                        'En proceso', 'Pagado' => 'bg-warning text-dark',
                                                        'Finalizada' => 'bg-success',
                                                        'Cancelada' => 'bg-danger',
                                                        default => 'bg-secondary'
                                                    };
                                                    ?>
                                                    <span class="badge rounded-pill <?= $badge ?>"><?= $v['estatus'] ?></span>
                                                </td>
                                                <td class="text-end fw-bold text-success">$<?= number_format($v['monto'], 2) ?></td>
                                                <td class="text-center">
                                                    <a href="venta_detalle.php?id_venta=<?= $v['id_venta'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye-fill"></i> Ver</a>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="5" class="text-center py-5 text-muted">No se encontraron ventas.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <?php if ($total_paginas > 1): ?>
                            <nav class="mt-4">
                                <ul class="pagination justify-content-center">
                                    <?php
                                    $qs = $_GET; // Copiamos los parámetros actuales
                                    ?>

                                    <li class="page-item <?= ($pagina <= 1) ? 'disabled' : '' ?>">
                                        <?php $qs['pagina'] = $pagina - 1; ?>
                                        <a class="page-link" href="?<?= http_build_query($qs) ?>">Anterior</a>
                                    </li>

                                    <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                                        <?php $qs['pagina'] = $i; ?>
                                        <li class="page-item <?= ($pagina == $i) ? 'active' : '' ?>">
                                            <a class="page-link" href="?<?= http_build_query($qs) ?>"><?= $i ?></a>
                                        </li>
                                    <?php endfor; ?>

                                    <li class="page-item <?= ($pagina >= $total_paginas) ? 'disabled' : '' ?>">
                                        <?php $qs['pagina'] = $pagina + 1; ?>
                                        <a class="page-link" href="?<?= http_build_query($qs) ?>">Siguiente</a>
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
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>