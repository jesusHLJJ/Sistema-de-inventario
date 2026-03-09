<?php
require_once '../controladores/conexion.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Recibimos los campos estándar y los nuevos campos
    $id_producto = $_POST['id_producto'];
    $id_negocio  = $_POST['id_negocio']; // Nuevo
    $marca       = $_POST['marca'];
    $nombre      = $_POST['nombre'];
    $contenido   = $_POST['contenido'];
    $piezas      = $_POST['piezas'];
    $precio      = $_POST['precio'];
    $venta_por   = $_POST['venta_por']; // Nuevo

    $mensaje = '';
    $tipo = '';
    $redirect = '';

    // Verificamos si el producto ya existe
    $check = $conexion->prepare("SELECT id_producto FROM producto WHERE id_producto = ?");
    $check->bind_param("s", $id_producto);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        $tipo = 'warning';
        $mensaje = 'Ya existe un producto con ese código de barras.';
        $redirect = 'javascript:history.back()';
    } else {
        // Preparamos el INSERT incluyendo id_negocio y venta_por
        $stmt = $conexion->prepare(
            "INSERT INTO producto (id_producto, id_negocio, marca, nombre, contenido, piezas, precio, venta_por)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
        );

        /* Tipos en bind_param:
           s = string (id_producto, marca, nombre, contenido, venta_por)
           i = integer (id_negocio)
           d = double/decimal (piezas, precio)
        */
        $stmt->bind_param("sissssds", 
            $id_producto, 
            $id_negocio, 
            $marca, 
            $nombre, 
            $contenido, 
            $piezas, 
            $precio, 
            $venta_por
        );

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