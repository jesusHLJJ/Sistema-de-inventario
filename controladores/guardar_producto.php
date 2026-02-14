<?php
require_once '../controladores/conexion.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $id_producto = $_POST['id_producto'];
    $marca = $_POST['marca'];
    $nombre = $_POST['nombre'];
    $contenido = $_POST['contenido'];
    $piezas = $_POST['piezas'];
    $precio = $_POST['precio'];

    $mensaje = '';
    $tipo = '';
    $redirect = '';

    $check = $conexion->prepare("SELECT id_producto FROM producto WHERE id_producto = ?");
    $check->bind_param("s", $id_producto);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        $tipo = 'warning';
        $mensaje = 'Ya existe un producto con ese código de barras.';
        $redirect = 'javascript:history.back()';
    } else {
        $stmt = $conexion->prepare(
            "INSERT INTO producto (id_producto, marca, nombre, contenido, piezas, precio)
             VALUES (?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param("sssssd", $id_producto, $marca, $nombre, $contenido, $piezas, $precio);

        if ($stmt->execute()) {
            $tipo = 'success';
            $mensaje = 'Producto agregado correctamente.';
            $redirect = 'agregar_producto.php';
        } else {
            $tipo = 'error';
            $mensaje = 'Error al guardar: ' . $stmt->error;
            $redirect = 'javascript:history.back()';
        }

        $stmt->close();
    }

    $check->close();
    $conexion->close();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Procesando</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>

<script>
    Swal.fire({
        icon: '<?= $tipo ?>',
        title: 'Resultado',
        text: '<?= addslashes($mensaje) ?>',
        confirmButtonText: 'Aceptar'
    }).then(() => {
        window.location.href = '<?= $redirect ?>';
    });
</script>

</body>
</html>
