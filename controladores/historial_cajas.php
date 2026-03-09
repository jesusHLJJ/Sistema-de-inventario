<?php
session_start();
require_once "conexion.php";

if (!isset($_SESSION['id_empleado'])) {
    header("Location: ../index.php");
    exit;
}

/* =========================
   PAGINACIÓN LÓGICA
========================= */
$limite = 10; // Registros por página
$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
if ($pagina < 1) $pagina = 1;
$offset = ($pagina - 1) * $limite;

/* =========================
   FILTROS DE BÚSQUEDA
========================= */
$where = [];
if (!empty($_GET['fecha'])) {
    $where[] = "DATE(c.fecha_apertura) = '" . $_GET['fecha'] . "'";
}
if (!empty($_GET['sector'])) {
    $where[] = "c.sector = '" . $_GET['sector'] . "'";
}

$sql_where = count($where) > 0 ? " WHERE " . implode(" AND ", $where) : "";

/* =========================
   CONTEO TOTAL PARA PAGINACIÓN
========================= */
$sql_count = "SELECT COUNT(*) as total FROM caja c $sql_where";
$res_count = $conexion->query($sql_count);
$total_registros = $res_count->fetch_assoc()['total'];
$total_paginas = ceil($total_registros / $limite);

/* =========================
   CONSULTA DE HISTORIAL CON LIMIT
========================= */
$sql = "SELECT c.*, e.nombre as empleado 
        FROM caja c 
        JOIN empleado e ON c.id_empleado = e.id_empleado 
        $sql_where 
        ORDER BY c.fecha_apertura DESC LIMIT $limite OFFSET $offset";
$resultado = $conexion->query($sql);
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Historial de Cortes de Caja</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
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

        .header-box,
        .filter-box,
        .table-container {
            background-color: #ffffff;
            border-radius: 10px;
            border: 1px solid #e3e6f0;
        }

        .badge-sector {
            font-size: 0.7rem;
            text-transform: uppercase;
            padding: 5px 10px;
        }

        .table-container {
            overflow: hidden;
        }

        .page-link {
            color: #4e73df;
        }

        .page-item.active .page-link {
            background-color: #4e73df;
            border-color: #4e73df;
        }
    </style>
</head>

<body>
    <div class="wrapper">
        <?php $pagina_activa = 'historial_cajas';
        include '../componentes/sidebar.php'; ?>

        <div class="main-content">
            <div class="container py-4">

                <div class="header-box shadow-sm p-3 mb-4 text-center">
                    <h3 class="fw-bold mb-0 text-dark">
                        <i class="bi bi-archive me-2 text-primary"></i>HISTORIAL DE CORTES DE CAJA
                    </h3>
                </div>

                <div class="filter-box shadow-sm p-4 mb-4">
                    <form method="GET" class="row g-3 align-items-end">
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-muted">FILTRAR POR FECHA</label>
                            <input type="date" name="fecha" class="form-control" value="<?= $_GET['fecha'] ?? '' ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-muted">SECTOR</label>
                            <select name="sector" class="form-select">
                                <option value="">Todos los sectores</option>
                                <option value="general" <?= (($_GET['sector'] ?? '') == 'general' ? 'selected' : '') ?>>General</option>
                                <option value="dulceria" <?= (($_GET['sector'] ?? '') == 'dulceria' ? 'selected' : '') ?>>Dulcería</option>
                            </select>
                        </div>
                        <div class="col-md-4 d-flex gap-2">
                            <button type="submit" class="btn btn-primary w-100 fw-bold">
                                <i class="bi bi-search me-2"></i>BUSCAR
                            </button>
                            <a href="historial_cajas.php" class="btn btn-outline-secondary px-3" title="Limpiar filtros">
                                <i class="bi bi-arrow-clockwise"></i>
                            </a>
                        </div>
                    </form>
                </div>

                <div class="table-container shadow-sm mb-4">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-dark">
                                <tr>
                                    <th class="ps-3">Apertura</th>
                                    <th>Cierre</th>
                                    <th>Sector</th>
                                    <th>Empleado</th>
                                    <th class="text-end">Fondo</th>
                                    <th class="text-end">Ventas</th>
                                    <th class="text-end">Gran Total</th>
                                    <th class="text-center pe-3">Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($resultado->num_rows > 0): ?>
                                    <?php while ($c = $resultado->fetch_assoc()): ?>
                                        <tr class="bg-white">
                                            <td class="ps-3"><small class="fw-bold"><?= date('d/m/Y H:i', strtotime($c['fecha_apertura'])) ?></small></td>
                                            <td><small class="text-muted"><?= $c['fecha_cierre'] ? date('d/m/Y H:i', strtotime($c['fecha_cierre'])) : '---' ?></small></td>
                                            <td>
                                                <span class="badge rounded-pill <?= $c['sector'] == 'general' ? 'bg-primary' : 'bg-info text-dark' ?> badge-sector">
                                                    <?= $c['sector'] ?>
                                                </span>
                                            </td>
                                            <td><i class="bi bi-person me-1"></i><?= htmlspecialchars($c['empleado']) ?></td>
                                            <td class="text-end text-muted">$<?= number_format($c['dinero_inicial'], 2) ?></td>
                                            <td class="text-end text-primary fw-bold">$<?= number_format($c['total_ventas'], 2) ?></td>
                                            <td class="text-end text-success fw-bold">$<?= number_format($c['total_final'], 2) ?></td>
                                            <td class="text-center pe-3">
                                                <?php if ($c['estatus'] == 'abierta'): ?>
                                                    <span class="badge bg-success shadow-sm px-3"><i class="bi bi-unlock me-1"></i> ABIERTA</span>
                                                <?php else: ?>
                                                    <span class="badge bg-light text-dark border shadow-xs px-3"><i class="bi bi-lock me-1"></i> CERRADA</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="8" class="text-center py-5 text-muted bg-white">
                                            <i class="bi bi-search fs-1 d-block mb-2"></i>
                                            No se encontraron registros de caja.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <?php if ($total_paginas > 1): ?>
                    <nav>
                        <ul class="pagination justify-content-center">
                            <?php
                            // Mantener los filtros actuales al cambiar de página
                            $query_string = $_GET;
                            ?>
                            <li class="page-item <?= ($pagina <= 1) ? 'disabled' : '' ?>">
                                <a class="page-link shadow-sm" href="?<?= http_build_query(array_merge($query_string, ['pagina' => $pagina - 1])) ?>">
                                    <i class="bi bi-chevron-left"></i> Anterior
                                </a>
                            </li>

                            <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                                <li class="page-item <?= ($pagina == $i) ? 'active' : '' ?>">
                                    <a class="page-link shadow-sm" href="?<?= http_build_query(array_merge($query_string, ['pagina' => $i])) ?>"><?= $i ?></a>
                                </li>
                            <?php endfor; ?>

                            <li class="page-item <?= ($pagina >= $total_paginas) ? 'disabled' : '' ?>">
                                <a class="page-link shadow-sm" href="?<?= http_build_query(array_merge($query_string, ['pagina' => $pagina + 1])) ?>">
                                    Siguiente <i class="bi bi-chevron-right"></i>
                                </a>
                            </li>
                        </ul>
                    </nav>
                <?php endif; ?>

            </div>
            <?php include '../componentes/footer.php'; ?>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>