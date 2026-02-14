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

/* 1. BÚSQUEDA GENERAL (ID o Monto) */
// Usamos paréntesis ( ... OR ... ) para que no rompa los filtros de fecha
if (!empty($_GET['busqueda'])) {
    $busqueda = "%" . $_GET['busqueda'] . "%";
    $where[] = "(v.id_venta LIKE ? OR v.monto LIKE ?)";
    $params[] = $busqueda;
    $params[] = $busqueda;
    $types .= "ss";
}

/* 2. FILTROS DE FECHA */

/* Día */
if (!empty($_GET['dia'])) {
    $where[] = "DATE(v.fecha) = ?";
    $params[] = $_GET['dia'];
    $types .= "s";
}

/* Mes */
if (!empty($_GET['mes'])) {
    $where[] = "MONTH(v.fecha) = ?";
    $params[] = $_GET['mes'];
    $types .= "i";
}

/* Año */
if (!empty($_GET['anio'])) {
    $where[] = "YEAR(v.fecha) = ?";
    $params[] = $_GET['anio'];
    $types .= "i";
}

/* =========================
   CONSULTA
========================= */
$sql = "SELECT v.id_venta, v.fecha, v.monto, e.estatus
        FROM venta v
        JOIN estatus e ON e.id_estatus = v.id_estatus";

if ($where) {
    $sql .= " WHERE " . implode(" AND ", $where);
}

$sql .= " ORDER BY v.id_venta DESC";

$stmt = $conexion->prepare($sql);

if ($params) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$ventas = $stmt->get_result();

$sql_corte = "SELECT 
                COUNT(*) AS total_ventas,
                SUM(monto) AS total_dinero
              FROM venta
              WHERE DATE(fecha) = CURDATE()
              AND id_estatus = 2";

$result_corte = $conexion->query($sql_corte);
$corte = $result_corte->fetch_assoc();

$total_ventas_hoy = $corte['total_ventas'] ?? 0;
$total_dinero_hoy = $corte['total_dinero'] ?? 0;

$sql_caja = "SELECT * FROM caja 
             WHERE DATE(fecha_apertura) = CURDATE() 
             LIMIT 1";
$result_caja = $conexion->query($sql_caja);
$caja = $result_caja->fetch_assoc();


if (isset($_POST['abrir_caja'])) {

    $dinero_inicial = $_POST['dinero_inicial'];
    $id_empleado = $_SESSION['id_empleado'];

    if (!$caja) {

        $stmt = $conexion->prepare("INSERT INTO caja (dinero_inicial, id_empleado) VALUES (?, ?)");
        $stmt->bind_param("di", $dinero_inicial, $id_empleado);
        $stmt->execute();

        header("Location: ventas.php");
        exit();
    }
}


if (isset($_POST['cerrar_caja']) && $caja && $caja['estatus'] == 'abierta') {

    $total_ventas = $total_dinero_hoy;
    $total_final = $caja['dinero_inicial'] + $total_ventas;

    $stmt = $conexion->prepare("
        UPDATE caja 
        SET total_ventas = ?, 
            total_final = ?, 
            fecha_cierre = NOW(),
            estatus = 'cerrada'
        WHERE DATE(fecha_apertura) = CURDATE()
    ");

    $stmt->bind_param("dd", $total_ventas, $total_final);
    $stmt->execute();

    header("Location: ventas.php");
    exit();
}

if (isset($_POST['reabrir_caja']) && $caja && $caja['estatus'] == 'cerrada') {

    $stmt = $conexion->prepare("
        UPDATE caja 
        SET estatus = 'abierta',
            fecha_cierre = NULL
        WHERE DATE(fecha_apertura) = CURDATE()
    ");

    $stmt->execute();

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
    <button class="btn btn-outline-dark d-md-none"
        data-bs-toggle="collapse"
        data-bs-target="#sidebar">
        <i class="bi bi-list fs-4"></i>
    </button>
    <div class="wrapper">

        <?php
        $pagina = 'ventas';
        include '../componentes/sidebar.php';
        ?>

        <div class="main-content">

            <div class="container py-5">

                <div class="card shadow-sm">
                    <div class="card-header bg-white py-3">
                        <h3 class="mb-0 fw-bold text-center"><i class="bi bi-clock-history me-2"></i>HISTORIAL DE VENTAS</h3>
                    </div>

                    <div class="card-body p-4">
                        <?php if (!$caja): ?>

                            <!-- NO HAY CAJA -->
                            <div class="alert alert-warning shadow-sm">
                                <form method="POST" class="row g-2 align-items-end">
                                    <div class="col-md-4">
                                        <label class="fw-bold">Dinero inicial</label>
                                        <input type="number" step="0.01" name="dinero_inicial" class="form-control" required>
                                    </div>
                                    <div class="col-md-3">
                                        <button type="submit" name="abrir_caja" class="btn btn-success w-100">
                                            <i class="bi bi-unlock-fill"></i> Abrir Caja
                                        </button>
                                    </div>
                                </form>
                            </div>

                        <?php elseif ($caja['estatus'] == 'abierta'): ?>

                            <!-- CAJA ABIERTA -->
                            <div class="alert alert-info shadow-sm d-flex justify-content-between align-items-center">
                                <div>
                                    <strong>Caja abierta</strong> |
                                    Dinero inicial: <strong>$<?= number_format($caja['dinero_inicial'], 2) ?></strong>
                                </div>
                                <form method="POST">
                                    <button type="submit" name="cerrar_caja" class="btn btn-danger">
                                        <i class="bi bi-lock-fill"></i> Cerrar Caja
                                    </button>
                                </form>
                            </div>

                        <?php else: ?>

                            <div class="alert alert-success shadow-sm d-flex justify-content-between align-items-center">
                                <div>
                                    <strong>Caja cerrada</strong><br>
                                    Dinero inicial: $<?= number_format($caja['dinero_inicial'], 2) ?><br>
                                    Total ventas: $<?= number_format($caja['total_ventas'], 2) ?><br>
                                    Total final: $<?= number_format($caja['total_final'], 2) ?>
                                </div>

                                <form method="POST">
                                    <button type="submit" name="reabrir_caja"
                                        class="btn btn-warning"
                                        onclick="return confirm('¿Seguro que deseas reabrir la caja?')">
                                        <i class="bi bi-arrow-counterclockwise"></i> Reabrir Caja
                                    </button>
                                </form>
                            </div>

                        <?php endif; ?>


                        <div class="alert alert-success shadow-sm">
                            <div class="row text-center">
                                <div class="col-md-6">
                                    <h5 class="fw-bold mb-1">Ventas del día</h5>
                                    <h3><?= $total_ventas_hoy ?></h3>
                                </div>
                                <div class="col-md-6">
                                    <h5 class="fw-bold mb-1">Total vendido hoy</h5>
                                    <h3>$<?= number_format($total_dinero_hoy, 2) ?></h3>
                                </div>
                            </div>
                        </div>
                        <form method="GET" class="row g-3 mb-4 bg-light p-3 rounded border align-items-end">

                            <div class="col-12 mb-2">
                                <label class="form-label fw-bold small">Búsqueda rápida</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                                    <input type="text" name="busqueda" class="form-control"
                                        placeholder="Escribe el ID de venta o el Monto (ej: 500)"
                                        value="<?= htmlspecialchars($_GET['busqueda'] ?? '') ?>">
                                </div>
                            </div>

                            <div class="col-md-12 mb-0">
                                <hr class="my-2">
                                <h6 class="text-muted fw-bold text-uppercase small mt-2">Filtros de Fecha:</h6>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label fw-bold small">Día</label>
                                <input type="date" name="dia" class="form-control" value="<?= $_GET['dia'] ?? '' ?>">
                            </div>

                            <div class="col-md-3">
                                <label class="form-label fw-bold small">Mes</label>
                                <select name="mes" class="form-select">
                                    <option value="">Todos</option>
                                    <?php
                                    $meses = ["Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre"];
                                    for ($m = 1; $m <= 12; $m++): ?>
                                        <option value="<?= $m ?>" <?= (($_GET['mes'] ?? '') == $m) ? 'selected' : '' ?>>
                                            <?= $meses[$m - 1] ?>
                                        </option>
                                    <?php endfor; ?>
                                </select>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label fw-bold small">Año</label>
                                <select name="anio" class="form-select">
                                    <option value="">Todos</option>
                                    <?php for ($y = date('Y'); $y >= 2020; $y--): ?>
                                        <option value="<?= $y ?>" <?= (($_GET['anio'] ?? '') == $y) ? 'selected' : '' ?>>
                                            <?= $y ?>
                                        </option>
                                    <?php endfor; ?>
                                </select>
                            </div>

                            <div class="col-md-3 d-flex gap-2">
                                <button class="btn btn-primary w-100 fw-bold">
                                    Filtrar
                                </button>
                                <a href="ventas.php" class="btn btn-outline-secondary" title="Limpiar filtros">
                                    <i class="bi bi-x-lg"></i>
                                </a>
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
                                                <td class="text-center">
                                                    <?= date('d/m/Y', strtotime($v['fecha'])) ?>
                                                    <small class="text-muted ms-1"><?= date('H:i', strtotime($v['fecha'])) ?></small>
                                                </td>
                                                <td class="text-center">
                                                    <?php
                                                    $badge = match ($v['estatus']) {
                                                        'En proceso', 'Pagado' => ' bg-warning text-dark',
                                                        'Finalizada' => ' bg-success',
                                                        'Cancelada' => 'bg-danger',
                                                        default => 'bg-secondary'
                                                    };
                                                    ?>
                                                    <span class="badge rounded-pill <?= $badge ?>"><?= $v['estatus'] ?></span>
                                                </td>
                                                <td class="text-end fw-bold text-success fs-5">
                                                    $<?= number_format($v['monto'], 2) ?>
                                                </td>
                                                <td class="text-center">
                                                    <a href="venta_detalle.php?id_venta=<?= $v['id_venta'] ?>"
                                                        class="btn btn-sm btn-outline-primary">
                                                        <i class="bi bi-eye-fill"></i> Detalles
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="5" class="text-center py-5 text-muted">
                                                <i class="bi bi-search fs-1 d-block mb-2"></i>
                                                No se encontraron resultados.
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>

            <?php
            include '../componentes/footer.php'; // Incluimos el archivo
            ?>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>