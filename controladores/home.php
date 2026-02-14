<?php
session_start();

// Validar si el usuario está logueado
if (!isset($_SESSION['id_empleado'])) {
    header("Location: ../index.php");
    exit;
}

$nombre = $_SESSION['nombre']; // Obtenemos el nombre de la sesión
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inicio - Inventario</title>
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
        $pagina = 'home'; // Definimos qué página es esta
        include '../componentes/sidebar.php'; // Incluimos el archivo
        ?>

        <div class="main-content">

            <div class="container-fluid p-4">
                <div class="d-flex justify-content-center align-items-center" style="height: 200px;">
                    <div class="cont_special justify-content-center">
                        <div class="text-center mb-4">
                            <h2 class="fw-bold text-dark ">Bienvenido</h2>
                            <div class="d-flex justify-content-center flex-wrap gap-2 mt-3">
                                <form method="post" class="d-inline">
                                    <button name="ver_productos" class="btn btn-secondary">Ver productos</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                <div id="liveToast" class="toast position-fixed bottom-0 end-0 m-3" role="alert" aria-live="assertive" aria-atomic="true" data-bs-delay="3000" style="z-index: 1050;">
                    <div class="toast-header">
                        <strong class="me-auto">Sistema</strong>
                        <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
                    </div>
                    <div class="toast-body">
                        ¡Hola! PENDIENTE
                    </div>
                </div>

                <?php
                if (isset($_POST['ver_productos']) || isset($_POST['buscar']) || isset($_POST['editar_producto']) || isset($_POST['cancelar_edicion'])) {
                    require_once 'conexion.php';

                    // Procesar actualización
                    if (isset($_POST['editar_producto'])) {
                        $id_original = $_POST['id_original'];
                        $id_producto = $_POST['id_producto'];
                        $marca = $_POST['marca'];
                        $nombre = $_POST['nombre'];
                        $contenido = $_POST['contenido'];
                        $piezas = $_POST['piezas'];
                        $precio = $_POST['precio'];

                        $stmt = $conexion->prepare("UPDATE producto SET id_producto=?, marca=?, nombre=?, contenido=?, piezas=?, precio=? WHERE id_producto=?");
                        $stmt->bind_param("ssssids", $id_producto, $marca, $nombre, $contenido, $piezas, $precio, $id_original);
                        $stmt->execute();
                        $stmt->close();
                    }

                    // Captura de filtros
                    $busqueda_general = $_POST['busqueda_general'] ?? '';
                    $filtros = [
                        'id_producto' => $_POST['f_id_producto'] ?? '',
                        'marca'       => $_POST['f_marca'] ?? '',
                        'nombre'      => $_POST['f_nombre'] ?? '',
                        'contenido'   => $_POST['f_contenido'] ?? '',
                        'piezas'      => $_POST['f_piezas'] ?? '',
                        'precio'      => $_POST['f_precio'] ?? '',
                    ];

                    $where = [];
                    $params = [];
                    $types = '';

                    // Filtro general
                    if ($busqueda_general !== '') {
                        $param = "%$busqueda_general%";
                        $where[] = "(id_producto LIKE ? OR marca LIKE ? OR nombre LIKE ? OR contenido LIKE ? OR CAST(piezas AS CHAR) LIKE ? OR CAST(precio AS CHAR) LIKE ?)";
                        $types .= "ssssss";
                        for ($i = 0; $i < 6; $i++) $params[] = $param;
                    }

                    // Filtros específicos
                    foreach ($filtros as $campo => $valor) {
                        if ($valor !== '') {
                            if ($campo == 'piezas' || $campo == 'precio') {
                                $where[] = "$campo = ?";
                                $types .= ($campo == 'piezas') ? "i" : "d";
                                $params[] = $campo == 'piezas' ? intval($valor) : floatval($valor);
                            } else {
                                $where[] = "$campo LIKE ?";
                                $types .= "s";
                                $params[] = "%$valor%";
                            }
                        }
                    }

                    $sql = "SELECT * FROM producto";
                    if (count($where) > 0) {
                        $sql .= " WHERE " . implode(" AND ", $where);
                    }

                    $stmt = $conexion->prepare($sql);
                    if ($types !== '') {
                        $bind_names[] = $types;
                        for ($i = 0; $i < count($params); $i++) {
                            $bind_name = 'bind' . $i;
                            $$bind_name = $params[$i];
                            $bind_names[] = &$$bind_name;
                        }
                        call_user_func_array([$stmt, 'bind_param'], $bind_names);
                    }

                    $stmt->execute();
                    $result = $stmt->get_result();
                    $editar_id = $_POST['editar_id'] ?? null;
                ?>

                    <div class="card shadow-sm mb-5" id="tablaProductos">
                        <div class="card-header bg-white">
                            <h4 class="mb-0 text-center">Lista de productos</h4>
                        </div>
                        <div class="card-body">
                            <form method="POST" class="mb-3">
                                <input type="hidden" name="ver_productos" value="1">
                                <div class="row g-3 align-items-center mb-3">
                                    <div class="col-auto">
                                        <label for="busqueda_general" class="col-form-label fw-bold">Buscar:</label>
                                    </div>
                                    <div class="col-auto flex-grow-1">
                                        <input type="text" name="busqueda_general" id="busqueda_general" class="form-control" placeholder="Escribe para buscar..." value="<?= htmlspecialchars($busqueda_general) ?>">
                                    </div>
                                    <button type="button"
                                        class="btn btn-outline-primary w-100 mt-2 d-md-none"
                                        onclick="abrirScanner()">
                                        <i class="bi bi-camera"></i> Escanear con cámara
                                    </button>
                                    <div class="col-auto">
                                        <button type="submit" name="buscar" class="btn btn-primary">Buscar</button>
                                        <a href="home.php" class="btn btn-outline-secondary">Limpiar</a>
                                    </div>
                                </div>

                                <div class="row g-2">
                                    <div class="col-md-2"><input type="text" name="f_id_producto" class="form-control form-control-sm" placeholder="Filtrar ID" value="<?= htmlspecialchars($filtros['id_producto']) ?>"></div>
                                    <div class="col-md-2"><input type="text" name="f_marca" class="form-control form-control-sm" placeholder="Filtrar Marca" value="<?= htmlspecialchars($filtros['marca']) ?>"></div>
                                    <div class="col-md-2"><input type="text" name="f_nombre" class="form-control form-control-sm" placeholder="Filtrar Nombre" value="<?= htmlspecialchars($filtros['nombre']) ?>"></div>
                                    <div class="col-md-2"><input type="text" name="f_contenido" class="form-control form-control-sm" placeholder="Filtrar Cont." value="<?= htmlspecialchars($filtros['contenido']) ?>"></div>
                                    <div class="col-md-2"><input type="number" name="f_piezas" class="form-control form-control-sm" placeholder="Filtrar Piezas" value="<?= htmlspecialchars($filtros['piezas']) ?>"></div>
                                    <div class="col-md-2"><input type="number" step="0.01" name="f_precio" class="form-control form-control-sm" placeholder="Filtrar Precio" value="<?= htmlspecialchars($filtros['precio']) ?>"></div>
                                </div>
                            </form>
                            <div id="scanner-container" class="mt-3 d-none">
                                <video id="video" autoplay playsinline style="width:100%;"></video>
                                <button class="btn btn-danger w-100 mt-2" onclick="cerrarScanner()">
                                    Cerrar cámara
                                </button>
                            </div>

                            <?php if ($result && $result->num_rows > 0): ?>
                                <div class="table-responsive">
                                    <table class="table table-bordered table-hover align-middle">
                                        <thead class="table-dark">
                                            <tr>
                                                <th>ID</th>
                                                <th>Marca</th>
                                                <th>Nombre</th>
                                                <th>Contenido</th>
                                                <th>Piezas</th>
                                                <th>Precio</th>
                                                <th style="width: 150px;">Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($row = $result->fetch_assoc()):
                                                $is_editing = ($editar_id === $row['id_producto']);
                                            ?>
                                                <?php if ($is_editing): ?>
                                                    <tr class="table-active">
                                                        <form method="POST">
                                                            <input type="hidden" name="id_original" value="<?= htmlspecialchars($row['id_producto']) ?>">
                                                            <input type="hidden" name="ver_productos" value="1">
                                                            <td><input type="text" class="form-control form-control-sm" name="id_producto" value="<?= htmlspecialchars($row['id_producto']) ?>" required></td>
                                                            <td><input type="text" class="form-control form-control-sm" name="marca" value="<?= htmlspecialchars($row['marca']) ?>" required></td>
                                                            <td><input type="text" class="form-control form-control-sm" name="nombre" value="<?= htmlspecialchars($row['nombre']) ?>" required></td>
                                                            <td><input type="text" class="form-control form-control-sm" name="contenido" value="<?= htmlspecialchars($row['contenido']) ?>" required></td>
                                                            <td><input type="number" class="form-control form-control-sm" name="piezas" value="<?= htmlspecialchars($row['piezas']) ?>" required></td>
                                                            <td><input type="number" step="0.01" class="form-control form-control-sm" name="precio" value="<?= htmlspecialchars($row['precio']) ?>" required></td>
                                                            <td>
                                                                <button type="submit" name="editar_producto" class="btn btn-success btn-sm"><i class="bi bi-check-lg"></i></button>
                                                                <button type="submit" name="cancelar_edicion" class="btn btn-secondary btn-sm"><i class="bi bi-x-lg"></i></button>
                                                            </td>
                                                        </form>
                                                    </tr>
                                                <?php else: ?>
                                                    <tr>
                                                        <td><?= htmlspecialchars($row['id_producto']) ?></td>
                                                        <td><?= htmlspecialchars($row['marca']) ?></td>
                                                        <td><?= htmlspecialchars($row['nombre']) ?></td>
                                                        <td><?= htmlspecialchars($row['contenido']) ?></td>
                                                        <td><?= htmlspecialchars($row['piezas']) ?></td>
                                                        <td>$<?= htmlspecialchars($row['precio']) ?></td>
                                                        <td>
                                                            <form method="POST" style="display:inline;">
                                                                <input type="hidden" name="editar_id" value="<?= htmlspecialchars($row['id_producto']) ?>">
                                                                <input type="hidden" name="ver_productos" value="1">
                                                                <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-pencil-square"></i> Editar</button>
                                                            </form>
                                                        </td>
                                                    </tr>
                                                <?php endif; ?>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-warning text-center mt-3">No hay productos que coincidan con la búsqueda.</div>
                            <?php endif; ?>
                        </div>
                    </div>

                <?php } ?>

            </div>

            <?php
            include '../componentes/footer.php'; // Incluimos el archivo
            ?>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Prevenir botón atrás
        history.pushState(null, null, location.href);
        window.onpopstate = function() {
            history.go(1);
        };

        // Scroll automático a la tabla si se está viendo productos
        <?php if (isset($_POST['ver_productos'])): ?>
            document.addEventListener("DOMContentLoaded", function() {
                const tabla = document.getElementById('tablaProductos');
                if (tabla) {
                    tabla.scrollIntoView({
                        behavior: 'smooth'
                    });
                }
            });
        <?php endif; ?>
    </script>
    <script src="https://unpkg.com/html5-qrcode"></script>
    <script src="../assets/js/buscar-scanner.js" defer></script>
</body>
</html>