<?php
require('fpdf.php');

// Conexión a la base de datos
$conexion = new mysqli("localhost", "root", "", "city_boleteria");
if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

// Obtener datos del formulario desde POST o GET
$obra = trim($_POST['obra'] ?? $_GET['obra'] ?? '');
$cantidad = (int)($_POST['cantidad'] ?? $_GET['cantidad'] ?? 0);

// Mapeo para los nombres en la base de datos
$mapa_obras = [
    "hamlet" => "Hamlet",
    "romeo" => "Romeo y Julieta",
    "donjuan" => "Don Juan Tenorio"
];

// Validar obra seleccionada
if (!array_key_exists($obra, $mapa_obras)) {
    die("Obra inválida.");
}

$nombre_obra = $conexion->real_escape_string($mapa_obras[$obra]);

// Consultar disponibilidad en inventario
$sql = "SELECT asientos_disponibles FROM inventario WHERE categoria = 'teatro' AND nombre = '$nombre_obra'";
$resultado = $conexion->query($sql);

if ($resultado && $resultado->num_rows > 0) {
    $fila = $resultado->fetch_assoc();
    $asientos_disponibles = (int)$fila['asientos_disponibles'];

    if ($asientos_disponibles >= $cantidad) {
        $nuevo_total = $asientos_disponibles - $cantidad;
        $update = "UPDATE inventario SET asientos_disponibles = $nuevo_total WHERE categoria = 'teatro' AND nombre = '$nombre_obra'";
        
        if ($conexion->query($update) === TRUE) {
            // Generar PDF de recibo
            class PDF extends FPDF {
                function Header() {
                    $this->SetFont('Arial', 'B', 16);
                    $this->Cell(0, 10, 'Recibo de Compra - City Boleteria (Teatro)', 0, 1, 'C');
                    $this->Ln(5);
                }
                function Footer() {
                    $this->SetY(-15);
                    $this->SetFont('Arial', 'I', 8);
                    $this->Cell(0, 10, 'Gracias por tu compra - cityboleteria.com', 0, 0, 'C');
                }
            }

            $precio_boleto = 800;
            $pdf = new PDF();
            $pdf->AddPage();
            $pdf->SetFont('Arial', '', 12);
            $pdf->Cell(0, 10, 'Obra: ' . $nombre_obra, 0, 1);
            $pdf->Cell(0, 10, 'Cantidad de boletos: ' . $cantidad, 0, 1);
            $pdf->Cell(0, 10, 'Precio por boleto: $' . $precio_boleto, 0, 1);
            $pdf->Cell(0, 10, 'Total pagado: $' . ($cantidad * $precio_boleto), 0, 1);
            $pdf->Ln(10);
            $pdf->Cell(0, 10, 'Fecha de compra: ' . date('d/m/Y H:i'), 0, 1);

            $pdf->Output('I', 'recibo_teatro.pdf');
            exit;
        } else {
            echo "Error al actualizar inventario: " . $conexion->error;
        }
    } else {
        echo "<h3>No hay suficientes asientos disponibles. Solo quedan $asientos_disponibles.</h3>";
    }
} else {
    echo "Obra no encontrada en el inventario.";
}

$conexion->close();
?>
