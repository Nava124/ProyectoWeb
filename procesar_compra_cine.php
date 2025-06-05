<?php
require('fpdf.php');

// Conexión a la base de datos
$conexion = new mysqli("localhost", "root", "", "city_boleteria");
if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}


$evento = $_GET['evento'] ?? '';
$cantidad = (int)($_GET['cantidad'] ?? 0);

// Validación básica
if (!$evento || $cantidad < 1) {
    die("Datos inválidos.");
}

// Escapar datos
$evento = $conexion->real_escape_string($evento);

// Verificar disponibilidad en inventario
$sql = "SELECT asientos_disponibles FROM inventario WHERE categoria = 'cine' AND nombre = '$evento'";
$resultado = $conexion->query($sql);

if ($resultado && $resultado->num_rows > 0) {
    $fila = $resultado->fetch_assoc();
    $asientos_disponibles = (int)$fila['asientos_disponibles'];

    if ($asientos_disponibles >= $cantidad) {
        $nuevo_total = $asientos_disponibles - $cantidad;
        $update = "UPDATE inventario SET asientos_disponibles = $nuevo_total WHERE categoria = 'cine' AND nombre = '$evento'";
        
        if ($conexion->query($update) === TRUE) {
            // Generar PDF del recibo
            class PDF extends FPDF {
                function Header() {
                    $this->SetFont('Arial', 'B', 16);
                    $this->Cell(0, 10, 'Recibo de Compra - City Boleteria (Cine)', 0, 1, 'C');
                    $this->Ln(5);
                }
                function Footer() {
                    $this->SetY(-15);
                    $this->SetFont('Arial', 'I', 8);
                    $this->Cell(0, 10, 'Gracias por tu compra - cityboleteria.com', 0, 0, 'C');
                }
            }

            $precio_unitario = 50;
            $total = $precio_unitario * $cantidad;

            $pdf = new PDF();
            $pdf->AddPage();
            $pdf->SetFont('Arial', '', 12);
            $pdf->Cell(0, 10, 'Función: ' . $evento, 0, 1);
            $pdf->Cell(0, 10, 'Cantidad de boletos: ' . $cantidad, 0, 1);
            $pdf->Cell(0, 10, 'Precio por boleto: $' . $precio_unitario, 0, 1);
            $pdf->Cell(0, 10, 'Total pagado: $' . $total, 0, 1);
            $pdf->Ln(10);
            $pdf->Cell(0, 10, 'Fecha de compra: ' . date('d/m/Y H:i'), 0, 1);

            $pdf->Output('I', 'recibo_cine.pdf');
            exit;
        } else {
            echo "Error al actualizar inventario: " . $conexion->error;
        }
    } else {
        echo "<h3>No hay suficientes asientos disponibles. Solo quedan $asientos_disponibles.</h3>";
    }
} else {
    echo "Evento no encontrado en el inventario.";
}

$conexion->close();
?>
