<?php
session_start(); // Iniciar sesión

// Conexión a la base de datos
$conexion = new mysqli("localhost", "root", "", "city_boleteria");
if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

// Verificar si hay sesión activa de administrador (opcional)
if (!isset($_SESSION['admin'])) {
    die("<script>alert('Acceso no autorizado.'); window.location.href='login.html';</script>");
}

// Obtener y sanitizar datos
$nombre = $conexion->real_escape_string(trim($_POST['nombre'] ?? ''));
$cantidad = (int)($_POST['cantidad'] ?? 0);
$accion = $_POST['action'] ?? '';

if ($nombre === '' || $cantidad < 1 || !in_array($accion, ['increase', 'decrease'])) {
    die("Datos inválidos.");
}

// Verificar si el producto existe
$sql = "SELECT asientos_disponibles FROM inventario WHERE nombre = '$nombre'";
$resultado = $conexion->query($sql);

if ($resultado && $resultado->num_rows > 0) {
    $fila = $resultado->fetch_assoc();
    $disponibles_actuales = (int)$fila['asientos_disponibles'];

    // Aplicar la operación correspondiente
    if ($accion === 'increase') {
        $nuevo_total = $disponibles_actuales + $cantidad;
    } elseif ($accion === 'decrease') {
        $nuevo_total = max(0, $disponibles_actuales - $cantidad); // no menos de 0
    }

    // Actualizar en base de datos
    $update = "UPDATE inventario SET asientos_disponibles = $nuevo_total WHERE nombre = '$nombre'";
    if ($conexion->query($update) === TRUE) {
        echo "<script>alert('Inventario actualizado correctamente.'); window.location.href='admin.html';</script>";
    } else {
        echo "Error al actualizar inventario: " . $conexion->error;
    }
} else {
    echo "<script>alert('El producto \"$nombre\" no fue encontrado.'); window.history.back();</script>";
}

$conexion->close();
session_destroy(); // Cierra la sesión (si quieres que se destruya tras ejecutar este script)
?>
