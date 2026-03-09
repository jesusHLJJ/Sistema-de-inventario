<?php
require_once '../controladores/conexion.php';

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $stmt = $conexion->prepare("SELECT precio, venta_por, nombre FROM producto WHERE id_producto = ?");
    $stmt->bind_param("s", $id);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($producto = $resultado->fetch_assoc()) {
        echo json_encode($producto);
    } else {
        echo json_encode(['error' => 'No encontrado']);
    }
}