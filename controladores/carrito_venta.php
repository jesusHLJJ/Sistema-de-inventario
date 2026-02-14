<?php
session_start();
require_once "conexion.php";

$id_empleado = $_SESSION['id_empleado'] ?? null;
$id_producto = $_POST['id_producto'];
$cantidad = floatval($_POST['cantidad']);

if (!$id_empleado) {
    header("Location: ../index.php");
    exit;
}

/* Obtener producto */
$sql = "SELECT precio, piezas, nombre FROM producto WHERE id_producto = ?";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("s", $id_producto);
$stmt->execute();
$result = $stmt->get_result();
$producto = $result->fetch_assoc();

if (!$producto) {
    $_SESSION['venta_status'] = 'error';
    $_SESSION['venta_msg'] = "El producto con ID $id_producto no existe.";
    header("Location: venta_nueva.php");
    exit;
}

/* Validar stock */
if ($cantidad > $producto['piezas']) {
    $_SESSION['venta_status'] = 'error';
    $_SESSION['venta_msg'] = "Stock insuficiente. Solo hay {$producto['piezas']} piezas de {$producto['nombre']}.";
    header("Location: venta_nueva.php");
    exit;
}

/* Crear venta SOLO si no existe */
if (!isset($_SESSION['id_venta'])) {
    $sql = "INSERT INTO venta (fecha, id_estatus, monto) VALUES (NOW(), 1, 0)";
    $stmt = $conexion->prepare($sql);
    $stmt->execute();

    $_SESSION['id_venta'] = $conexion->insert_id;
}

$id_venta = $_SESSION['id_venta'];

/* Calcular total */
$total = $producto['precio'] * $cantidad;

/* Insertar detalle */
$sql = "INSERT INTO detalles_venta 
        (id_venta, id_producto, id_empleado, cantidad, totalproducto)
        VALUES (?, ?, ?, ?, ?)";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("isidd", $id_venta, $id_producto, $id_empleado, $cantidad, $total);
$stmt->execute();

$sql = "UPDATE venta 
        SET monto = (SELECT SUM(totalproducto) FROM detalles_venta WHERE id_venta = ?)
        WHERE id_venta = ?";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("ii", $id_venta, $id_venta);
$stmt->execute();

header("Location: venta_nueva.php");
exit;
