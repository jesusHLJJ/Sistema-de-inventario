<?php
session_start();
require_once "conexion.php";

if (!isset($_SESSION['id_empleado'])) {
    header("Location: ../index.php");
    exit;
}

/* =========================
   CONFIGURACIÓN DE SECTOR
========================= */
$sector_analisis = isset($_GET['sector']) ? $_GET['sector'] : 'general';

/* =========================
   DATOS MES ACTUAL
========================= */
$mes_actual_num = date('n');
$anio_actual_num = date('Y');

$sql_mes = "SELECT SUM(total_ventas) as total FROM caja 
            WHERE MONTH(fecha_apertura) = ? AND YEAR(fecha_apertura) = ? 
            AND sector = ? AND estatus = 'cerrada'";

$stmt_act = $conexion->prepare($sql_mes);
$stmt_act->bind_param("iis", $mes_actual_num, $anio_actual_num, $sector_analisis);
$stmt_act->execute();
$mes_actual_monto = $stmt_act->get_result()->fetch_assoc()['total'] ?? 0;

/* =========================
   DATOS MES A COMPARAR
========================= */
$comp_mes = isset($_GET['comp_mes']) ? (int)$_GET['comp_mes'] : ($mes_actual_num == 1 ? 12 : $mes_actual_num - 1);
$comp_anio = isset($_GET['comp_anio']) ? (int)$_GET['comp_anio'] : ($mes_actual_num == 1 ? $anio_actual_num - 1 : $anio_actual_num);

$stmt_comp = $conexion->prepare($sql_mes);
$stmt_comp->bind_param("iis", $comp_mes, $comp_anio, $sector_analisis);
$stmt_comp->execute();
$mes_comp_monto = $stmt_comp->get_result()->fetch_assoc()['total'] ?? 0;

// Cálculo de diferencia
$dif_custom = $mes_actual_monto - $mes_comp_monto;

/* =========================
   DATOS GRÁFICA (Segmentada por Sector)
========================= */
$sql_grafica = "SELECT DATE(fecha_apertura) as fecha, total_ventas 
                FROM caja 
                WHERE sector = ? AND estatus = 'cerrada' 
                ORDER BY fecha_apertura DESC LIMIT 10";
$stmt_graf = $conexion->prepare($sql_grafica);
$stmt_graf->bind_param("s", $sector_analisis);
$stmt_graf->execute();
$res_grafica = $stmt_graf->get_result();

$fechas = []; $montos = [];
while ($row = $res_grafica->fetch_assoc()) {
    $fechas[] = date('d/m', strtotime($row['fecha']));
    $montos[] = $row['total_ventas'];
}
$fechas = array_reverse($fechas); $montos = array_reverse($montos);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Análisis de Rendimiento - <?= strtoupper($sector_analisis) ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        html, body { height: 100%; overflow: hidden; }
        .wrapper { display: flex; height: 100vh; width: 100%; }
        .main-content { flex-grow: 1; height: 100vh; overflow-y: auto; background-color: #f8f9fa; display: flex; flex-direction: column; }
        .card-sector-select { border-left: 5px solid #0d6efd; }
    </style>
</head>
<body>
    <div class="wrapper">
        <?php $pagina_activa = 'analisis'; include '../componentes/sidebar.php'; ?>

        <div class="main-content">
            <div class="container py-5">
                <h3 class="fw-bold mb-4 text-center"><i class="bi bi-bar-chart-line me-2"></i>ANÁLISIS COMPARATIVO: <?= strtoupper($sector_analisis) ?></h3>

                <div class="card shadow-sm border-0 mb-4 card-sector-select">
                    <div class="card-body">
                        <form method="GET" class="row g-3 align-items-end">
                            <div class="col-md-3">
                                <label class="form-label small fw-bold">Sector:</label>
                                <select name="sector" class="form-select bg-light fw-bold" onchange="this.form.submit()">
                                    <option value="general" <?= ($sector_analisis == 'general') ? 'selected' : '' ?>>Abarrotes / Papelería</option>
                                    <option value="dulceria" <?= ($sector_analisis == 'dulceria') ? 'selected' : '' ?>>Dulcería</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-bold">Mes a Comparar:</label>
                                <select name="comp_mes" class="form-select">
                                    <?php
                                    $meses = ["Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre"];
                                    foreach ($meses as $idx => $m): ?>
                                        <option value="<?= $idx + 1 ?>" <?= ($comp_mes == $idx + 1) ? 'selected' : '' ?>><?= $m ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small fw-bold">Año:</label>
                                <select name="comp_anio" class="form-select">
                                    <?php for ($y = date('Y'); $y >= 2023; $y--): ?>
                                        <option value="<?= $y ?>" <?= ($comp_anio == $y) ? 'selected' : '' ?>><?= $y ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <button type="submit" class="btn btn-dark w-100">
                                    <i class="bi bi-arrow-repeat me-2"></i>Actualizar Reporte
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="row mb-4 text-center">
                    <div class="col-md-5">
                        <div class="card shadow-sm border-0 bg-primary text-white h-100 py-3">
                            <small class="text-white-50">MES ACTUAL (<?= strtoupper($sector_analisis) ?>)</small>
                            <h2 class="fw-bold">$<?= number_format($mes_actual_monto, 2) ?></h2>
                        </div>
                    </div>
                    <div class="col-md-2 d-flex align-items-center justify-content-center">
                        <span class="badge rounded-pill bg-dark px-3 py-2 shadow">VS</span>
                    </div>
                    <div class="col-md-5">
                        <div class="card shadow-sm border-0 bg-secondary text-white h-100 py-3">
                            <small class="text-white-50"><?= strtoupper($meses[$comp_mes-1]) ?> <?= $comp_anio ?></small>
                            <h2 class="fw-bold">$<?= number_format($mes_comp_monto, 2) ?></h2>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-body text-center">
                        <h5 class="mb-1">
                            <?php if ($dif_custom >= 0): ?>
                                <span class="text-success fw-bold"><i class="bi bi-graph-up-arrow"></i> +$<?= number_format($dif_custom, 2) ?></span>
                                <small class="text-muted"> rendimiento superior en <?= $sector_analisis ?></small>
                            <?php else: ?>
                                <span class="text-danger fw-bold"><i class="bi bi-graph-down-arrow"></i> -$<?= number_format(abs($dif_custom), 2) ?></span>
                                <small class="text-muted"> rendimiento inferior en <?= $sector_analisis ?></small>
                            <?php endif; ?>
                        </h5>
                    </div>
                </div>

                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 fw-bold">Tendencia de Últimos Cierres (<?= ucfirst($sector_analisis) ?>)</h5>
                        <div class="btn-group btn-group-sm">
                            <button class="btn btn-outline-primary active" id="btnLine" onclick="cambiarTipo('line')"><i class="bi bi-graph-up"></i></button>
                            <button class="btn btn-outline-primary" id="btnBar" onclick="cambiarTipo('bar')"><i class="bi bi-bar-chart-fill"></i></button>
                        </div>
                    </div>
                    <div class="card-body">
                        <canvas id="ventasChart" style="max-height: 350px;"></canvas>
                    </div>
                </div>

            </div>
            <?php include '../componentes/footer.php'; ?>
        </div>
    </div>

    <script>
        const ctx = document.getElementById('ventasChart').getContext('2d');
        let chart;
        const colorSector = '<?= ($sector_analisis == "dulceria") ? "#fd7e14" : "#0d6efd" ?>';

        function renderChart(type) {
            if (chart) chart.destroy();
            chart = new Chart(ctx, {
                type: type,
                data: {
                    labels: <?= json_encode($fechas) ?>,
                    datasets: [{
                        label: 'Ventas de <?= ucfirst($sector_analisis) ?> ($)',
                        data: <?= json_encode($montos) ?>,
                        borderColor: colorSector,
                        backgroundColor: type === 'line' ? colorSector + '22' : colorSector,
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: { 
                    responsive: true, 
                    maintainAspectRatio: false,
                    plugins: { legend: { display: true } },
                    scales: { y: { beginAtZero: true } }
                }
            });
        }

        function cambiarTipo(t) {
            document.getElementById('btnLine').classList.toggle('active', t === 'line');
            document.getElementById('btnBar').classList.toggle('active', t === 'bar');
            renderChart(t);
        }

        document.addEventListener('DOMContentLoaded', () => renderChart('line'));
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>