<?php
// Conexión MySQL
$host = 'localhost';
$user = 'root';
$pass = '';
$db = 'city_boleteria';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

// Consulta para obtener todo el inventario
$sql = "SELECT id, categoria, nombre, asientos_disponibles FROM inventario ORDER BY categoria, nombre";
$result = $conn->query($sql);

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Inventario Actual - City Boletería</title>
    <style>
        table {
            border-collapse: collapse;
            width: 90%;
            margin: 20px auto;
        }
        th, td {
            border: 1px solid #444;
            padding: 8px 12px;
            text-align: left;
        }
        th {
            background-color: #222;
            color: white;
        }
        tbody tr:nth-child(odd) {
            background-color: #eee;
        }
    </style>
</head>
<body>

<h2 style="text-align:center;">Inventario Actual</h2>

<?php if ($result && $result->num_rows > 0): ?>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Categoría</th>
                <th>Nombre</th>
                <th>Asientos Disponibles</th>
            </tr>
        </thead>
        <tbody>
            <?php while($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?php echo htmlspecialchars($row['id']); ?></td>
                <td><?php echo htmlspecialchars($row['categoria']); ?></td>
                <td><?php echo htmlspecialchars($row['nombre']); ?></td>
                <td><?php echo htmlspecialchars($row['asientos_disponibles']); ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
<?php else: ?>
    <p style="text-align:center;">No hay productos en inventario.</p>
<?php endif; ?>

</body>
</html>

<?php $conn->close(); ?>
