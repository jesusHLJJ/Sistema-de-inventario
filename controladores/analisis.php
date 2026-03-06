<?php
session_start();
require_once "conexion.php";

if (!isset($_SESSION['id_empleado'])) {
    header("Location: ../index.php");
    exit;
}

/* =========================
   DATOS MES ACTUAL
========================= */
$mes_actual_num = date('n');
$anio_actual_num = date('Y');

$sql_actual = "SELECT SUM(total_ventas) as total FROM caja 
               WHERE MONTH(fecha_apertura) = ? AND YEAR(fecha_apertura) = ? AND estatus = 'cerrada'";
$stmt_act = $conexion->prepare($sql_actual);
$stmt_act->bind_param("ii", $mes_actual_num, $anio_actual_num);
$stmt_act->execute();
$mes_actual_monto = $stmt_act->get_result()->fetch_assoc()['total'] ?? 0;

/* =========================
   DATOS MES A COMPARAR (POR DEFECTO MES ANTERIOR)
========================= */
$comp_mes = isset($_GET['comp_mes']) ? (int)$_GET['comp_mes'] : ($mes_actual_num == 1 ? 12 : $mes_actual_num - 1);
$comp_anio = isset($_GET['comp_anio']) ? (int)$_GET['comp_anio'] : ($mes_actual_num == 1 ? $anio_actual_num - 1 : $anio_actual_num);

$stmt_comp = $conexion->prepare($sql_actual); // Reutilizamos la misma estructura de consulta
$stmt_comp->bind_param("ii", $comp_mes, $comp_anio);
$stmt_comp->execute();
$mes_comp_monto = $stmt_comp->get_result()->fetch_assoc()['total'] ?? 0;

// Cálculo de diferencia
$dif_custom = $mes_actual_monto - $mes_comp_monto;
$porc_custom = ($mes_comp_monto > 0) ? ($dif_custom / $mes_comp_monto) * 100 : 0;

/* =========================
   DATOS GRÁFICA (Últimos 10 cierres)
========================= */
$res_grafica = $conexion->query("SELECT DATE(fecha_apertura) as fecha, total_ventas FROM caja WHERE estatus = 'cerrada' ORDER BY fecha_apertura DESC LIMIT 10");
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
    <title>Análisis Comparativo</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        html, body { height: 100%; overflow: hidden; }
        .wrapper { display: flex; height: 100vh; width: 100%; }
        .main-content { flex-grow: 1; height: 100vh; overflow-y: auto; background-color: #f8f9fa; display: flex; flex-direction: column; }
        .card-vs { background: linear-gradient(90deg, #0d6efd 50%, #6c757d 50%); color: white; }
    </style>
</head>
<body>
    <div class="wrapper">
        <?php $pagina_activa = 'analisis'; include '../componentes/sidebar.php'; ?>

        <div class="main-content">
            <div class="container py-5">
                <h3 class="fw-bold mb-4 text-center"><i class="bi bi-arrow-left-right me-2"></i>COMPARATIVA DE RENDIMIENTO</h3>

                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3 align-items-end">
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Comparar Mes Actual contra:</label>
                                <select name="comp_mes" class="form-select">
                                    <?php
                                    $meses = ["Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre"];
                                    foreach ($meses as $idx => $m): ?>
                                        <option value="<?= $idx + 1 ?>" <?= ($comp_mes == $idx + 1) ? 'selected' : '' ?>><?= $m ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-bold">Año:</label>
                                <select name="comp_anio" class="form-select">
                                    <?php for ($y = date('Y'); $y >= 2023; $y--): ?>
                                        <option value="<?= $y ?>" <?= ($comp_anio == $y) ? 'selected' : '' ?>><?= $y ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div class="col-md-5">
                                <button type="submit" class="btn btn-dark w-100">
                                    <i class="bi bi-funnel-fill me-2"></i>Actualizar Comparativa
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="row mb-4 text-center">
                    <div class="col-md-5">
                        <div class="card shadow-sm border-0 bg-primary text-white h-100 py-3">
                            <small class="text-white-50">MES ACTUAL</small>
                            <h2 class="fw-bold">$<?= number_format($mes_actual_monto, 2) ?></h2>
                        </div>
                    </div>
                    <div class="col-md-2 d-flex align-items-center justify-content-center">
                        <span class="badge rounded-pill bg-dark px-3 py-2">VS</span>
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
                                <small class="text-muted"> superior este mes</small>
                            <?php else: ?>
                                <span class="text-danger fw-bold"><i class="bi bi-graph-down-arrow"></i> -$<?= number_format(abs($dif_custom), 2) ?></span>
                                <small class="text-muted"> inferior este mes</small>
                            <?php endif; ?>
                        </h5>
                        <div class="progress mt-2" style="height: 10px;">
                            <?php 
                                $max = max($mes_actual_monto, $mes_comp_monto, 1);
                                $p1 = ($mes_actual_monto / $max) * 100;
                            ?>
                            <div class="progress-bar bg-primary" role="progressbar" style="width: <?= $p1 ?>%"></div>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 fw-bold">Tendencia de Últimos Cierres</h5>
                        <div class="btn-group btn-group-sm">
                            <button class="btn btn-outline-primary active" id="btnLine" onclick="cambiarTipo('line')"><i class="bi bi-graph-up"></i></button>
                            <button class="btn btn-outline-primary" id="btnBar" onclick="cambiarTipo('bar')"><i class="bi bi-bar-chart-fill"></i></button>
                        </div>
                    </div>
                    <div class="card-body">
                        <canvas id="ventasChart" style="max-height: 300px;"></canvas>
                    </div>
                </div>

            </div>
            <?php include '../componentes/footer.php'; ?>
        </div>
    </div>

    <script>
        const ctx = document.getElementById('ventasChart').getContext('2d');
        let tipoActual = 'line';
        let chart;

        function renderChart(type) {
            if (chart) chart.destroy();
            chart = new Chart(ctx, {
                type: type,
                data: {
                    labels: <?= json_encode($fechas) ?>,
                    datasets: [{
                        label: 'Venta $',
                        data: <?= json_encode($montos) ?>,
                        borderColor: '#0d6efd',
                        backgroundColor: type === 'line' ? 'rgba(13, 110, 253, 0.1)' : '#0d6efd',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.3
                    }]
                },
                options: { responsive: true, plugins: { legend: { display: false } } }
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