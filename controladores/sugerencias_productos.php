<?php
require_once 'conexion.php';

$query = $_GET['query'] ?? '';

if ($query !== '') {
    $param = "%$query%";
    // Buscamos por nombre o ID
    $stmt = $conexion->prepare("SELECT id_producto, nombre FROM producto WHERE nombre LIKE ? OR id_producto LIKE ? LIMIT 10");
    $stmt->bind_param("ss", $param, $param);
    $stmt->execute();
    $result = $stmt->get_result();

    $productos = [];
    while ($row = $result->fetch_assoc()) {
        $productos[] = $row;
    }
    echo json_encode($productos);
}