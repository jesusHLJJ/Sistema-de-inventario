<?php
session_start();
require_once "conexion.php";

if (!isset($_GET['id_venta'])) {
    header("Location: prestamos.php");
    exit;
}

$id_venta = $_GET['id_venta'];

// 1. Cambiamos el estatus de 4 (Préstamo) a 2 (Finalizada)
// Importante: No descontamos inventario aquí porque se descontó cuando se registró el préstamo.
$sql = "UPDATE venta SET id_estatus = 2, fecha = NOW() WHERE id_venta = ?";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("i", $id_venta);

if ($stmt->execute()) {
    $_SESSION['msg_tipo'] = 'success';
    $_SESSION['msg_texto'] = "El préstamo ha sido pagado y registrado en la caja del día.";
} else {
    $_SESSION['msg_tipo'] = 'error';
    $_SESSION['msg_texto'] = "Error al procesar el pago.";
}

header("Location: prestamos.php");
exit;