<?php
session_start();
include("../controladores/conexion.php");

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $correo = $_POST['correo'];
    $contra = $_POST['contra'];

    $sql = "SELECT id_empleado, nombre, ap_paterno, ap_materno, edad, sexo, pass 
            FROM empleado WHERE correo = ?";

    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("s", $correo);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows === 1) {

        $fila = $resultado->fetch_assoc();

        if (password_verify($contra, $fila['pass'])) {

            $_SESSION['id_empleado'] = $fila['id_empleado'];
            $_SESSION['nombre'] = $fila['nombre'];
            $_SESSION['ap_paterno'] = $fila['ap_paterno'];
            $_SESSION['ap_materno'] = $fila['ap_materno'];
            $_SESSION['edad'] = $fila['edad'];
            $_SESSION['sexo'] = $fila['sexo'];
            $_SESSION['correo'] = $correo;

            $tipo = "success";
            $titulo = "Bienvenido";
            $mensaje = "Hola " . $fila['nombre'];
            $redir = "home.php";

        } else {
            $tipo = "error";
            $titulo = "Contraseña incorrecta";
            $mensaje = "Verifica tus datos e intenta de nuevo";
            $redir = "javascript:history.back()";
        }

    } else {
        $tipo = "error";
        $titulo = "Usuario no encontrado";
        $mensaje = "El correo no está registrado";
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