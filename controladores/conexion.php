<?php
$server = "localhost";
$user = "root";
$pass = ""; 
$db = "punto_de_venta"; 

$conexion = new mysqli($server, $user, $pass, $db);

if($conexion->connect_errno){
    // Solo mostramos mensaje si falla
    die("fallo la conexion" . $conexion->connect_errno); 
}

// Si la conexión es exitosa, NO HACEMOS NADA (el silencio es éxito).
// Eliminamos el echo(".");