<?php
session_start();
include("../controladores/conexion.php");

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $correo = $_POST['correo'];
    $contra = $_POST['contra'];

    $sql = "SELECT id_empleado, nombre, pass FROM empleado WHERE correo = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("s", $correo);
    $stmt->execute();
    $resultado = $stmt->get_result();

    echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>";

    if ($resultado->num_rows === 1) {
        $fila = $resultado->fetch_assoc();
        $hash_guardado = $fila['pass'];

        if (password_verify($contra, $hash_guardado)) {
            // Guardar datos en sesión
            $_SESSION['id_empleado'] = $fila['id_empleado'];
            $_SESSION['nombre'] = $fila['nombre'];
            $_SESSION['ap_paterno'] = $fila['ap_paterno']; 
            $_SESSION['ap_materno'] = $fila['ap_materno']; 
            $_SESSION['edad'] = $fila['edad']; 
            $_SESSION['sexo'] = $fila['sexo'];
            $_SESSION['correo'] = $correo;

            echo "<script>
                Swal.fire({
                    icon: 'success',
                    title: 'Bienvenido',
                    text: 'Hola {$fila['nombre']}',
                    confirmButtonText: 'Continuar'
                }).then(() => {
                    window.location.href = 'home.php'; // o cualquier página de inicio
                });
            </script>";
        } else {
            echo "<script>
                Swal.fire({
                    icon: 'error',
                    title: 'Contraseña incorrecta',
                    text: 'Verifica tus datos e intenta de nuevo'
                }).then(() => {
                    window.history.back();
                });
            </script>";
        }
    } else {
        echo "<script>
            Swal.fire({
                icon: 'error',
                title: 'Usuario no encontrado',
                text: 'El correo no está registrado'
            }).then(() => {
                window.history.back();
            });
        </script>";
    }
}
?>
