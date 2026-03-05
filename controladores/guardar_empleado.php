<?php
include("../controladores/conexion.php");

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $nombre      = $_POST['nombre'];
    $ap_paterno  = $_POST['ap_paterno'];
    $ap_materno  = $_POST['ap_materno'];
    $edad        = $_POST['edad'];
    $sexo        = $_POST['sexo'];
    $correo      = $_POST['correo'];
    $contra      = $_POST['contra'];

    $contra_hash = password_hash($contra, PASSWORD_DEFAULT);

    $sql = "INSERT INTO empleado (nombre, ap_paterno, ap_materno, edad, sexo, correo, pass)
            VALUES (?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("sssisss", $nombre, $ap_paterno, $ap_materno, $edad, $sexo, $correo, $contra_hash);

    if ($stmt->execute()) {
        $tipo = "success";
        $titulo = "Registrado correctamente";
        $mensaje = "Empleado registrado";
        $redir = "../index.php";
    } else {
        $tipo = "error";
        $titulo = "Error";
        $mensaje = "No se pudo registrar el empleado";
        $redir = "javascript:history.back()";
    }
}
?>

<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>

    <script>
        Swal.fire({
            icon: "<?php echo $tipo; ?>",
            title: "<?php echo $titulo; ?>",
            text: "<?php echo $mensaje; ?>"
        }).then(() => {
            window.location.href = "<?php echo $redir; ?>";
        });
    </script>

</body>

</html>