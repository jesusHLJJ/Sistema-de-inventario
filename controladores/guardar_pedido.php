<?php
session_start();
require_once "conexion.php";

$id_empleado = $_SESSION['id_empleado'];
$proveedor   = $_POST['proveedor'];
$productos   = $_POST['producto'];
$cantidades  = $_POST['cantidad'];
$precios     = $_POST['precio'];  

$conexion->begin_transaction();

try {

    /* 1. CREAR PEDIDO */
    $stmt = $conexion->prepare("
        INSERT INTO pedidos (proveedor, id_empleado, total_pago)
        VALUES (?, ?, 0)
    ");
    $stmt->bind_param("si", $proveedor, $id_empleado);
    $stmt->execute();

    $id_pedido = $conexion->insert_id;

    /* 2. INSERTAR DETALLE */
    $stmt = $conexion->prepare("
        INSERT INTO detalle_pedido (id_pedido, id_producto, cantidad, precio_compra)
        VALUES (?, ?, ?, ?)
    ");

    $total = 0;

    foreach ($productos as $i => $id_producto) {

        $cantidad = $cantidades[$i];
        $precio   = $precios[$i];   // ← precio por fila

        $total += $precio;

        $stmt->bind_param("iiid", $id_pedido, $id_producto, $cantidad, $precio);
        $stmt->execute();
    }

    /* 3. ACTUALIZAR TOTAL DEL PEDIDO */
    $stmt = $conexion->prepare("
        UPDATE pedidos 
        SET total_pago = ?
        WHERE id_pedido = ?
    ");
    $stmt->bind_param("di", $total, $id_pedido);
    $stmt->execute();

    $conexion->commit();

    header("Location: pedidos.php");
    exit;

} catch(Exception $e) {

    $conexion->rollback();
    echo "Error: " . $e->getMessage();
}
