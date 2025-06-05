<?php
require('fpdf.php');

// Conexión a la base de datos
$conexion = new mysqli("localhost", "root", "", "city_boleteria");
if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

// Obtener datos por GET
$artista = isset($_GET['artista']) ? trim($_GET['artista']) : '';
$cantidad = isset($_GET['cantidad']) ? (int)$_GET['cantidad'] : 0;

// Mapeo para nombres en la base de datos
$mapa_artistas = [
    "taylor" => "Taylor Swift",
    "karol" => "Karol G",
    "coldplay" => "Coldplay"
];

// Precios por artista (puedes ajustar si lo deseas)
$precios = [
    "Taylor Swift" => 2000,
    "Karol G" => 1800,
    "Coldplay" => 1500
];

if (!array_key_exists($artista, $mapa_artistas)) {
    die("Artista inválido.");
}

$nombre_artista = $conexion->real_escape_string($mapa_artistas[$artista]);
$precio_boleto = $precios[$nombre_artista] ?? 1500;

// Buscar en inventario
$sql = "SELECT asientos_disponibles FROM inventario WHERE categoria = 'concierto' AND nombre = '$nombre_artista'";
$resultado = $conexion->query($sql);

if ($resultado && $resultado->num_rows > 0) {
    $fila = $resultado->fetch_assoc();
    $asientos_disponibles = (int)$fila['asientos_disponibles'];

    if ($asientos_disponibles >= $cantidad) {
        $nuevo_total = $asientos_disponibles - $cantidad;
        $update = "UPDATE inventario SET asientos_disponibles = $nuevo_total WHERE categoria = 'concierto' AND nombre = '$nombre_artista'";

        if ($conexion->query($update) === TRUE) {
            // Generar PDF
            class PDF extends FPDF {
                function Header() {
                    $this->SetFont('Arial', 'B', 16);
                    $this->Cell(0, 10, 'Recibo de Compra - City Boleteria (Concierto)', 0, 1, 'C');
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
            $pdf->Cell(0, 10, 'Artista: ' . $nombre_artista, 0, 1);
            $pdf->Cell(0, 10, 'Cantidad de boletos: ' . $cantidad, 0, 1);
            $pdf->Cell(0, 10, 'Precio por boleto: $' . $precio_boleto, 0, 1);
            $pdf->Cell(0, 10, 'Total pagado: $' . ($cantidad * $precio_boleto), 0, 1);
            $pdf->Ln(10);
            $pdf->Cell(0, 10, 'Fecha de compra: ' . date('d/m/Y H:i'), 0, 1);

            $pdf->Output('I', 'recibo_concierto.pdf');
            exit;
        } else {
            echo "Error al actualizar inventario: " . $conexion->error;
        }
    } else {
        echo "<h3>No hay suficientes asientos disponibles. Solo quedan $asientos_disponibles.</h3>";
    }
} else {
    echo "Artista no encontrado en el inventario.";
}

$conexion->close();
?>
