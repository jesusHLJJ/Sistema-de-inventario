<?php
require_once '../controladores/conexion.php';

echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_producto = $_POST['id_producto']; // Código de barras
    $marca = $_POST['marca'];
    $nombre = $_POST['nombre'];
    $contenido = $_POST['contenido'];
    $piezas = $_POST['piezas'];
    $precio = $_POST['precio'];

    // Validar si ya existe ese ID de producto
    $check = $conexion->prepare("SELECT id_producto FROM producto WHERE id_producto = ?");
    $check->bind_param("s", $id_producto);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        echo "<script>
            Swal.fire({
                icon: 'warning',
                title: 'Código duplicado',
                text: 'Ya existe un producto con ese código de barras.',
                confirmButtonText: 'Regresar'
            }).then(() => {
                window.history.back();
            });
        </script>";
    } else {
        // Insertar nuevo producto
        $stmt = $conexion->prepare("INSERT INTO producto (id_producto, marca, nombre, contenido, piezas, precio) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssd", $id_producto, $marca, $nombre, $contenido, $piezas, $precio);

        if ($stmt->execute()) {
            echo "<script>
                Swal.fire({
                    icon: 'success',
                    title: 'Producto guardado',
                    text: 'Se agregó el producto correctamente',
                    confirmButtonText: 'Continuar'
                }).then(() => {
                    window.location.href = 'home.php';
                });
            </script>";
        } else {
            echo "<script>
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'No se pudo guardar el producto: " . addslashes($stmt->error) . "',
                    confirmButtonText: 'Regresar'
                }).then(() => {
                    window.history.back();
                });
            </script>";
        }

        $stmt->close();
    }

    $check->close();
    $conexion->close();
}
?>
