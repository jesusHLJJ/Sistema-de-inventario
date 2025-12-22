<?php
ob_start(); // Prevenir errores de headers
session_start();
require_once "conexion.php";

if (!isset($_SESSION['id_venta'])) {
    $_SESSION['venta_status'] = 'error';
    header("Location: venta_nueva.php");
    exit;
}

$id_venta = $_SESSION['id_venta'];

$conexion->begin_transaction();

try {
    /* Obtener detalles */
    $sql = "SELECT id_producto, cantidad FROM detalles_venta WHERE id_venta=?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $id_venta);
    $stmt->execute();
    $detalles = $stmt->get_result();

    while ($row = $detalles->fetch_assoc()) {
        /* Verificar stock */
        $sql = "SELECT piezas FROM producto WHERE id_producto=?";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("i", $row['id_producto']);
        $stmt->execute();
        $prod = $stmt->get_result()->fetch_assoc();
        
        // Verificamos si existe y el stock
        if (!$prod || $prod['piezas'] < $row['cantidad']) {
            throw new Exception("Stock insuficiente para uno de los productos.");
        }

        /* Descontar stock */
        $sql = "UPDATE producto SET piezas = piezas - ? WHERE id_producto = ?";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("ii", $row['cantidad'], $row['id_producto']);
        $stmt->execute();
    }

    /* Finalizar venta */
    $sql = "UPDATE venta SET id_estatus = 2 WHERE id_venta=?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $id_venta);
    $stmt->execute();

    $conexion->commit();

    unset($_SESSION['id_venta']);
    $_SESSION['venta_status'] = 'success';

} catch (Exception $e) {
    $conexion->rollback();
    $_SESSION['venta_status'] = 'error';
    $_SESSION['venta_msg'] = $e->getMessage();
}

// CORRECCIÓN IMPORTANTE: La ruta lleva "../"
header("Location: venta_nueva.php");
exit;