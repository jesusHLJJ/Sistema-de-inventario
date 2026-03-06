<?php
session_start();
require_once 'conexion.php';

if (!isset($_SESSION['id_empleado'])) {
    header("Location: ../index.php");
    exit;
}

$nombre_usuario = $_SESSION['nombre'];

/* =========================
   PROCESAR ACTUALIZACIÓN
========================= */
if (isset($_POST['guardar_cambios'])) {
    $id_original = $_POST['id_original'];
    $id_producto = $_POST['id_producto'];
    $marca = $_POST['marca'];
    $nombre = $_POST['nombre'];
    $contenido = $_POST['contenido'];
    $piezas = $_POST['piezas'];
    $precio = $_POST['precio'];

    $stmt = $conexion->prepare("UPDATE producto SET id_producto=?, marca=?, nombre=?, contenido=?, piezas=?, precio=? WHERE id_producto=?");
    $stmt->bind_param("ssssids", $id_producto, $marca, $nombre, $contenido, $piezas, $precio, $id_original);

    if ($stmt->execute()) {
        $params = $_GET;
        unset($params['editar_id']);
        $params['status'] = 'success';
        header("Location: home.php?" . http_build_query($params));
        exit;
    }
}

/* =========================
   LÓGICA DE FILTROS Y PAGINACIÓN
========================= */
$busqueda_general = $_GET['busqueda_general'] ?? '';
$limite = 10;
$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
if ($pagina < 1) $pagina = 1;
$offset = ($pagina - 1) * $limite;

$where = [];
$params_query = [];
$types = '';

if ($busqueda_general !== '') {
    $param = "%$busqueda_general%";
    $where[] = "(id_producto LIKE ? OR marca LIKE ? OR nombre LIKE ?)";
    $types .= "sss";
    $params_query = [$param, $param, $param];
}

// 1. CONTEO REAL DE REGISTROS PARA PAGINACIÓN
$sql_count = "SELECT COUNT(*) as total FROM producto";
if ($where) {
    $sql_count .= " WHERE " . implode(" AND ", $where);
}

$stmt_c = $conexion->prepare($sql_count);
if ($types) {
    $stmt_c->bind_param($types, ...$params_query);
}
$stmt_c->execute();
$total_registros = $stmt_c->get_result()->fetch_assoc()['total'];
$total_paginas = ceil($total_registros / $limite);

// 2. CONSULTA CON LIMIT Y OFFSET
$sql = "SELECT * FROM producto";
if ($where) {
    $sql .= " WHERE " . implode(" AND ", $where);
}
$sql .= " LIMIT ? OFFSET ?";

$stmt = $conexion->prepare($sql);
$types_f = $types . "ii";
$params_f = array_merge($params_query, [$limite, $offset]);
$stmt->bind_param($types_f, ...$params_f);
$stmt->execute();
$result = $stmt->get_result();

$editar_id = $_GET['editar_id'] ?? null;
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Inicio - Inventario</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body>
    <div class="wrapper">
        <?php $pagina_activa = 'home';
        include '../componentes/sidebar.php'; ?>

        <div class="main-content">
            <div class="container-fluid p-4">

                <div class="card shadow-sm">
                    <div class="card-header bg-white py-3 text-center">
                        <h4 class="mb-0 fw-bold text-primary">LISTA DE PRODUCTOS</h4>
                    </div>
                    <div class="card-body">
                        <form method="GET" class="row g-2 mb-4">
                            <div class="col-md-8">
                                <input type="text" name="busqueda_general" class="form-control" placeholder="Buscar por ID, Marca o Nombre..." value="<?= htmlspecialchars($busqueda_general) ?>">
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-primary w-100"><i class="bi bi-search"></i> Buscar</button>
                            </div>
                            <div class="col-md-2">
                                <a href="home.php" class="btn btn-outline-secondary w-100"><i class="bi bi-eraser"></i> Limpiar</a>
                            </div>
                        </form>

                        <div class="table-responsive">
                            <table class="table table-bordered table-hover align-middle">
                                <thead class="table-dark text-center">
                                    <tr>
                                        <th>ID</th>
                                        <th>Marca</th>
                                        <th>Nombre</th>
                                        <th>Contenido</th>
                                        <th>Stock</th>
                                        <th>Precio</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($row = $result->fetch_assoc()):
                                        $es_esta_fila = ($editar_id == $row['id_producto']);
                                    ?>
                                        <?php if ($es_esta_fila): ?>
                                            <tr class="table-info">
                                                <form method="POST" id="formEditar">
                                                    <input type="hidden" name="id_original" value="<?= $row['id_producto'] ?>">
                                                    <input type="hidden" name="guardar_cambios" value="1">
                                                    <td><input type="text" name="id_producto" class="form-control form-control-sm" value="<?= $row['id_producto'] ?>"></td>
                                                    <td><input type="text" name="marca" class="form-control form-control-sm" value="<?= $row['marca'] ?>"></td>
                                                    <td><input type="text" name="nombre" class="form-control form-control-sm" value="<?= $row['nombre'] ?>"></td>
                                                    <td><input type="text" name="contenido" class="form-control form-control-sm" value="<?= $row['contenido'] ?>"></td>
                                                    <td><input type="number" name="piezas" class="form-control form-control-sm" value="<?= $row['piezas'] ?>"></td>
                                                    <td><input type="number" step="0.01" name="precio" class="form-control form-control-sm" value="<?= $row['precio'] ?>"></td>
                                                    <td class="text-center">
                                                        <div class="btn-group">
                                                            <button type="button" onclick="confirmarCambio()" class="btn btn-success btn-sm"><i class="bi bi-check-lg"></i></button>
                                                            <?php
                                                            $params_cancel = $_GET;
                                                            unset($params_cancel['editar_id']);
                                                            unset($params_cancel['status']);
                                                            ?>
                                                            <a href="home.php?<?= http_build_query($params_cancel) ?>" class="btn btn-secondary btn-sm"><i class="bi bi-x-lg"></i></a>
                                                        </div>
                                                    </td>
                                                </form>
                                            </tr>
                                        <?php else: ?>
                                            <tr>
                                                <td class="text-center fw-bold"><?= $row['id_producto'] ?></td>
                                                <td><?= $row['marca'] ?></td>
                                                <td><?= $row['nombre'] ?></td>
                                                <td class="text-center"><?= $row['contenido'] ?></td>
                                                <td class="text-center">
                                                    <span class="badge <?= ($row['piezas'] < 10) ? 'bg-danger' : 'bg-success' ?>">
                                                        <?= $row['piezas'] ?>
                                                    </span>
                                                </td>
                                                <td class="text-end fw-bold text-success">$<?= number_format($row['precio'], 2) ?></td>
                                                <td class="text-center">
                                                    <?php
                                                    $params_edit = $_GET;
                                                    $params_edit['editar_id'] = $row['id_producto'];
                                                    unset($params_edit['status']);
                                                    ?>
                                                    <a href="?<?= http_build_query($params_edit) ?>" class="btn btn-primary btn-sm">
                                                        <i class="bi bi-pencil-square"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>

                        <?php if ($total_paginas > 1): ?>
                            <nav class="mt-4">
                                <ul class="pagination justify-content-center">
                                    <?php
                                    $qs = $_GET;
                                    unset($qs['editar_id']);
                                    unset($qs['status']);
                                    ?>

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

                        <div class="text-center text-muted small mt-2">
                            Mostrando <?= $result->num_rows ?> de <?= $total_registros ?> productos.
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
        function confirmarCambio() {
            Swal.fire({
                title: '¿Confirmar cambios?',
                text: "Los datos del producto serán actualizados permanentemente.",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#198754',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Sí, guardar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('formEditar').submit();
                }
            })
        }

        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('status') === 'success') {
            Swal.fire({
                title: '¡Éxito!',
                text: 'Producto actualizado correctamente.',
                icon: 'success',
                timer: 1500,
                showConfirmButton: false
            }).then(() => {
                urlParams.delete('status');
                const newUrl = window.location.pathname + (urlParams.toString() ? '?' + urlParams.toString() : '');
                window.history.replaceState({}, document.title, newUrl);
            });
        }
    </script>
</body>

</html>