<?php
session_start();
require_once "conexion.php";

$id_empleado = $_SESSION['id_empleado'] ?? null;
$id_producto = $_POST['id_producto'];
$cantidad    = floatval($_POST['cantidad']);
// Recibimos el precio que viene del input (ya sea el de la BD o el editado manualmente)
$precio_venta = floatval($_POST['precio_venta']); 

if (!$id_empleado) {
    header("Location: ../index.php");
    exit;
}

/* 1. Obtener datos del producto (necesitamos el id_negocio y el stock) */
$sql = "SELECT piezas, nombre, id_negocio FROM producto WHERE id_producto = ?";
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

/* 2. Validar stock */
if ($cantidad > $producto['piezas']) {
    $_SESSION['venta_status'] = 'error';
    $_SESSION['venta_msg'] = "Stock insuficiente. Solo hay {$producto['piezas']} de {$producto['nombre']}.";
    header("Location: venta_nueva.php");
    exit;
}

/* 3. Crear venta si no existe */
if (!isset($_SESSION['id_venta'])) {
    $sql = "INSERT INTO venta (fecha, id_estatus, monto) VALUES (NOW(), 1, 0)";
    $stmt = $conexion->prepare($sql);
    $stmt->execute();
    $_SESSION['id_venta'] = $conexion->insert_id;
}

$id_venta = $_SESSION['id_venta'];

/* 4. CALCULAR TOTAL USANDO EL PRECIO DEL FORMULARIO */
// Esto permite que si pusiste $10 pesos, se guarde exactamente eso.
$total = $precio_venta * $cantidad;

/* 5. Insertar detalle (Incluyendo id_negocio para los reportes) */
$sql = "INSERT INTO detalles_venta 
        (id_venta, id_producto, id_empleado, cantidad, totalproducto, id_negocio)
        VALUES (?, ?, ?, ?, ?, ?)";
$stmt = $conexion->prepare($sql);
// Agregamos el id_negocio al final del bind_param
$stmt->bind_param("isiddd", $id_venta, $id_producto, $id_empleado, $cantidad, $total, $producto['id_negocio']);
$stmt->execute();

/* 6. Actualizar el monto total de la venta */
$sql = "UPDATE venta 
        SET monto = (SELECT SUM(totalproducto) FROM detalles_venta WHERE id_venta = ?)
        WHERE id_venta = ?";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("ii", $id_venta, $id_venta);
$stmt->execute();

header("Location: venta_nueva.php");
exit;