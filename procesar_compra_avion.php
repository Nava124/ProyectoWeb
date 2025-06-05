<?php
require('fpdf.php');

$conexion = new mysqli("localhost", "root", "", "city_boleteria");
if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

$destino = trim($_GET['destino'] ?? '');
$cantidad = (int)($_GET['cantidad'] ?? 0);

$mapa_destinos = [
    "cdmx" => "Ciudad de Mexico",
    "monterrey" => "Monterrey",
    "cancun" => "Cancún"
];

if (!array_key_exists($destino, $mapa_destinos)) {
    die("Destino inválido.");
}

$nombre_destino = $conexion->real_escape_string($mapa_destinos[$destino]);

$sql = "SELECT asientos_disponibles FROM inventario WHERE categoria = 'avion' AND nombre = '$nombre_destino'";
$resultado = $conexion->query($sql);

if ($resultado && $resultado->num_rows > 0) {
    $fila = $resultado->fetch_assoc();
    $asientos_disponibles = (int)$fila['asientos_disponibles'];

    if ($asientos_disponibles >= $cantidad) {
        $nuevo_total = $asientos_disponibles - $cantidad;
        $update = "UPDATE inventario SET asientos_disponibles = $nuevo_total WHERE categoria = 'avion' AND nombre = '$nombre_destino'";

        if ($conexion->query($update) === TRUE) {
            class PDF extends FPDF {
                function Header() {
                    $this->SetFont('Arial', 'B', 16);
                    $this->Cell(0, 10, 'Recibo de Compra - City Boleteria (Avion)', 0, 1, 'C');
                    $this->Ln(5);
                }
                function Footer() {
                    $this->SetY(-15);
                    $this->SetFont('Arial', 'I', 8);
                    $this->Cell(0, 10, 'Gracias por tu compra - cityboleteria.com', 0, 0, 'C');
                }
            }

            $pdf = new PDF();
            $pdf->AddPage();
            $pdf->SetFont('Arial', '', 12);
            $pdf->Cell(0, 10, 'Destino: ' . $nombre_destino, 0, 1);
            $pdf->Cell(0, 10, 'Cantidad de boletos: ' . $cantidad, 0, 1);
            $pdf->Cell(0, 10, 'Precio por boleto: $2000', 0, 1);
            $pdf->Cell(0, 10, 'Total pagado: $' . (2000 * $cantidad), 0, 1);
            $pdf->Ln(10);
            $pdf->Cell(0, 10, 'Fecha de compra: ' . date('d/m/Y H:i'), 0, 1);

            $pdf->Output('I', 'recibo_avion.pdf');
            exit;
        } else {
            echo "Error al actualizar inventario: " . $conexion->error;
        }
    } else {
        echo "<h3>No hay suficientes asientos disponibles. Solo quedan $asientos_disponibles.</h3>";
    }
} else {
    echo "Destino no encontrado en el inventario.";
}

$conexion->close();
?>
