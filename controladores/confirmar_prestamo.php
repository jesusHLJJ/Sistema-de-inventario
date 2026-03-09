<?php
session_start();
require_once "../controladores/conexion.php";

$id_venta = $_SESSION['id_venta'] ?? null;
$cliente = $_POST['cliente'] ?? 'Anónimo';

if ($id_venta) {
    // 1. Cambiar estatus a 4 (Préstamo) y guardar cliente
    $stmt = $conexion->prepare("UPDATE venta SET id_estatus = 4, cliente_prestamo = ?, monto = (SELECT SUM(totalproducto) FROM detalles_venta WHERE id_venta = ?) WHERE id_venta = ?");
    $stmt->bind_param("sii", $cliente, $id_venta, $id_venta);
    $stmt->execute();

    // 2. Descontar Inventario
    $res = $conexion->query("SELECT id_producto, cantidad FROM detalles_venta WHERE id_venta = $id_venta");
    while ($row = $res->fetch_assoc()) {
        $conexion->query("UPDATE producto SET piezas = piezas - {$row['cantidad']} WHERE id_producto = '{$row['id_producto']}'");
    }

    // 3. Finalizar sesión de venta
    unset($_SESSION['id_venta']);
    $_SESSION['venta_status'] = 'success';
    $_SESSION['venta_msg'] = "Préstamo registrado a $cliente";
    header("Location: venta_nueva.php");
}