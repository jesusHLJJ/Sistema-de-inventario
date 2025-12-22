<?php
include("../controladores/conexion.php");

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nombre = $_POST['nombre'];
    $ap_paterno = $_POST['ap_paterno'];
    $ap_materno = $_POST['ap_materno'];
    $edad = $_POST['edad'];
    $sexo = $_POST['sexo'];
    $correo = $_POST['correo'];
    $contra = $_POST['contra'];

    // Hashear la contraseña
    $contra_hash = password_hash($contra, PASSWORD_DEFAULT);

    $sql = "INSERT INTO empleado (nombre, ap_paterno, ap_materno, edad, sexo, correo, pass)
            VALUES (?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("sssisss", $nombre, $ap_paterno, $ap_materno, $edad, $sexo, $correo, $contra_hash);

    echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>";

    if ($stmt->execute()) {
        echo "<script>
            Swal.fire({
                icon: 'success',
                title: 'Registrado correctamente',
                text: 'Empleado registrado',
            }).then(() => {
                window.location.href = '../index.php';
            });
        </script>";
    } else {
        echo "<script>
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'No se pudo registrar el empleado: " . $conexion->error . "'
            }).then(() => {
                window.history.back();
            });
        </script>";
    }

}
?>
