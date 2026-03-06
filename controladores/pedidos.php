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
   FILTROS LÓGICA PHP
========================= */
$where = [];
$params = [];
$types = "";

// Filtro por proveedor o búsqueda general
if (!empty($_GET['busqueda'])) {
    $busqueda = "%" . $_GET['busqueda'] . "%";
    $where[] = "(p.proveedor LIKE ? OR p.id_pedido LIKE ?)";
    $params[] = $busqueda;
    $params[] = $busqueda;
    $types .= "ss";
}

// Filtro por Estatus
if (!empty($_GET['estatus'])) {
    $where[] = "p.estatus = ?";
    $params[] = $_GET['estatus'];
    $types .= "s";
}

/* =========================
   PAGINACIÓN (CÁLCULOS)
========================= */
$limite = 10;
$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
if ($pagina < 1) $pagina = 1;
$offset = ($pagina - 1) * $limite;

// 1. Contar total de registros con filtros
$sql_count = "SELECT COUNT(*) as total FROM pedidos p";
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
$sql = "SELECT p.*, e.nombre AS empleado
        FROM pedidos p
        LEFT JOIN empleado e ON e.id_empleado = p.id_empleado";

if ($where) {
    $sql .= " WHERE " . implode(" AND ", $where);
}

$sql .= " ORDER BY p.id_pedido DESC LIMIT ? OFFSET ?";

$stmt = $conexion->prepare($sql);

// Preparar parámetros para LIMIT y OFFSET
$params_final = $params;
$params_final[] = $limite;
$params_final[] = $offset;
$types_final = $types . "ii";

$stmt->bind_param($types_final, ...$params_final);
$stmt->execute();
$pedidos = $stmt->get_result();
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

    <button class="btn btn-outline-dark d-md-none" data-bs-toggle="collapse" data-bs-target="#sidebar">
        <i class="bi bi-list fs-4"></i>
    </button>

    <div class="wrapper">
        <?php $pagina_activa = 'pedidos';
        include '../componentes/sidebar.php'; ?>

        <div class="main-content">
            <div class="container py-5">
                <div class="card shadow-sm">
                    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                        <h3 class="mb-0 fw-bold"><i class="bi bi-box-seam me-2"></i> PEDIDOS</h3>
                        <a href="pedidos_nuevo.php" class="btn btn-success"><i class="bi bi-plus-circle"></i> Hacer pedido</a>
                    </div>

                    <div class="card-body p-4">

                        <form method="GET" class="row g-3 mb-4 bg-light p-3 rounded border align-items-end">
                            <div class="col-md-5">
                                <label class="form-label fw-bold small">Buscar Proveedor o # Pedido</label>
                                <input type="text" name="busqueda" class="form-control" placeholder="Ej: Coca Cola o 00012" value="<?= htmlspecialchars($_GET['busqueda'] ?? '') ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold small">Estatus</label>
                                <select name="estatus" class="form-select">
                                    <option value="">Todos</option>
                                    <option value="Pendiente" <?= (($_GET['estatus'] ?? '') == 'Pendiente') ? 'selected' : '' ?>>Pendiente</option>
                                    <option value="Recibido" <?= (($_GET['estatus'] ?? '') == 'Recibido') ? 'selected' : '' ?>>Recibido</option>
                                </select>
                            </div>
                            <div class="col-md-3 d-flex gap-2">
                                <button type="submit" class="btn btn-primary w-100 fw-bold">Filtrar</button>
                                <a href="pedidos.php" class="btn btn-outline-secondary" title="Limpiar"><i class="bi bi-arrow-clockwise"></i></a>
                            </div>
                        </form>

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
                                                <td class="text-center fw-bold">#<?= str_pad($p['id_pedido'], 5, "0", STR_PAD_LEFT) ?></td>
                                                <td><?= htmlspecialchars($p['proveedor']) ?></td>
                                                <td class="text-center">
                                                    <?= date('d/m/Y', strtotime($p['fecha_pedido'])) ?>
                                                    <small class="text-muted ms-1"><?= date('H:i', strtotime($p['fecha_pedido'])) ?></small>
                                                </td>
                                                <td class="text-center"><?= htmlspecialchars($p['empleado'] ?? 'N/A') ?></td>
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
                                                    <a href="pedido_detalle.php?id=<?= $p['id_pedido'] ?>" class="btn btn-sm btn-outline-primary">
                                                        <i class="bi bi-eye-fill"></i> Detalles
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" class="text-center py-5 text-muted">
                                                <i class="bi bi-inbox fs-1 d-block mb-2"></i> No hay pedidos registrados
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <?php if ($total_paginas > 1): ?>
                            <nav class="mt-4">
                                <ul class="pagination justify-content-center">
                                    <?php $qs = $_GET; ?>

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
                                <div class="text-center text-muted small mt-2">
                                    Mostrando página <?= $pagina ?> de <?= $total_paginas ?> (<?= $total_registros ?> registros en total)
                                </div>
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