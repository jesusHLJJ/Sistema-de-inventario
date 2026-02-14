<?php
session_start();
require_once "conexion.php";

if (!isset($_SESSION['id_venta'], $_POST['id_detalles_venta'])) {
    header("Location: venta_nueva.php");
    exit;
}


$id_venta = $_SESSION['id_venta'];
$id_detalle = $_POST['id_detalles_venta'];

/* Eliminar producto */
$sql = "DELETE FROM detalles_venta 
        WHERE id_detalles_venta = ? AND id_venta = ?";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("ii", $id_detalle, $id_venta);
$stmt->execute();

/*  Recalcular total */
$sql = "SELECT SUM(totalproducto) AS total 
        FROM detalles_venta WHERE id_venta = ?";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("i", $id_venta);
$stmt->execute();
$total = $stmt->get_result()->fetch_assoc()['total'] ?? 0;


if ($total <= 0) {
    $sql = "DELETE FROM venta WHERE id_venta = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $id_venta);
    $stmt->execute();

    unset($_SESSION['id_venta']);
} else {
    $sql = "UPDATE venta SET monto = ? WHERE id_venta = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("di", $total, $id_venta);
    $stmt->execute();
}

header("Location: venta_nueva.php");
exit;
