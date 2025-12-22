<?php
session_start();
require_once "conexion.php";

// Validar sesión
if (!isset($_SESSION['id_venta'])) {
    header("Location: venta_nueva.php");
    exit;
}

$id_venta = $_SESSION['id_venta'];
$id_empleado = $_SESSION['id_empleado'];
$id_producto = $_POST['id_producto'];
$cantidad = $_POST['cantidad'];

/* Obtener producto */
$sql = "SELECT precio, piezas, nombre FROM producto WHERE id_producto=?";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("i", $id_producto);
$stmt->execute();
$result = $stmt->get_result();
$producto = $result->fetch_assoc();


if (!$producto) {
    $_SESSION['venta_status'] = 'error';
    $_SESSION['venta_msg'] = "El producto con ID $id_producto no existe.";
    header("Location: venta_nueva.php");
    exit;
}


if ($cantidad > $producto['piezas']) {
    $_SESSION['venta_status'] = 'error';
    // Mensaje 
    $_SESSION['venta_msg'] = "Stock insuficiente. Solo hay " . $producto['piezas'] . " piezas de " . $producto['nombre'];
    header("Location: venta_nueva.php");
    exit;
}

$total = $producto['precio'] * $cantidad;


$sql = "INSERT INTO detalles_venta (id_venta, id_producto, id_empleado, cantidad, totalproducto) VALUES (?, ?, ?, ?, ?)";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("iiiid", $id_venta, $id_producto, $id_empleado, $cantidad, $total);
$stmt->execute();


$sql = "UPDATE venta SET monto = (SELECT SUM(totalproducto) FROM detalles_venta WHERE id_venta = ?) WHERE id_venta = ?";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("ii", $id_venta, $id_venta);
$stmt->execute();


header("Location: venta_nueva.php");
exit;