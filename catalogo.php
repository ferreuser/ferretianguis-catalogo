<?php
// Conexión a la base de datos
$host = "localhost";
$user = "root";
$password = ""; // Cambia según tu configuración
$database = "productos_ferretianguis";

$conn = new mysqli($host, $user, $password, $database);

if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

// Configuración de paginación
$items_per_page = 10;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($page - 1) * $items_per_page;

// Filtros avanzados
$filters = [];
if (!empty($_GET['sku'])) {
    $filters[] = "p.SKU LIKE '%" . $conn->real_escape_string($_GET['sku']) . "%'";
}
if (!empty($_GET['descripcion'])) {
    $filters[] = "p.Descripcion LIKE '%" . $conn->real_escape_string($_GET['descripcion']) . "%'";
}
if (!empty($_GET['descripcion_subfamilia'])) {
    $filters[] = "p.DescripcionSubfamilia LIKE '%" . $conn->real_escape_string($_GET['descripcion_subfamilia']) . "%'";
}
if (!empty($_GET['proveedor'])) {
    $filters[] = "p.Proveedor LIKE '%" . $conn->real_escape_string($_GET['proveedor']) . "%'";
}
if (!empty($_GET['precio1'])) {
    $filters[] = "p.Precio1IVAUV = " . floatval($_GET['precio1']);
}
if (!empty($_GET['precio2'])) {
    $filters[] = "p.Precio2IVAUV = " . floatval($_GET['precio2']);
}
if (!empty($_GET['ganancia1'])) {
    $filters[] = "p.Ganancia1 = " . floatval($_GET['ganancia1']);
}
if (!empty($_GET['ganancia2'])) {
    $filters[] = "p.Ganancia2 = " . floatval($_GET['ganancia2']);
}
if (!empty($_GET['movimiento_por_año'])) {
    $filters[] = "p.MovimientoPorAño = " . intval($_GET['movimiento_por_año']);
}

// Construcción del WHERE
$where_clause = !empty($filters) ? "WHERE " . implode(" AND ", $filters) : "";

// Consulta principal
$query = "
    SELECT 
        p.SKU, 
        p.Descripcion,
        p.CodigoBarras, 
        p.DescripcionCategoria, 
        p.DescripcionSubfamilia,
        p.Proveedor, 
        p.Precio1IVAUV, 
        p.Precio2IVAUV, 
        p.CostoNeto, 
        p.Ganancia1, 
        p.Ganancia2, 
        p.MovimientoPorAño,
        i.url_imagen 
    FROM 
        productos p 
    LEFT JOIN 
        imagenes i 
    ON 
        p.SKU = i.producto_sku
    $where_clause
    LIMIT $items_per_page OFFSET $offset";

$result = $conn->query($query);

// Total de productos para paginación
$total_query = "SELECT COUNT(*) AS total FROM productos p LEFT JOIN imagenes i ON p.SKU = i.producto_sku $where_clause";
$total_result = $conn->query($total_query);
$total_row = $total_result->fetch_assoc();
$total_items = $total_row['total'];
$total_pages = ceil($total_items / $items_per_page);

// Descargar en Excel
if (isset($_GET['download']) && $_GET['download'] == 'excel') {
    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=productos_filtrados.xls");
    echo "SKU\tDescripción\tCódigo de Barras\tCategoría\tSubfamilia\tProveedor\tPrecio 1\tPrecio 2\tCosto Neto\tGanancia 1\tGanancia 2\tMovimiento por Año\tURL Imagen\n";
    $download_result = $conn->query("SELECT p.SKU, p.Descripcion, p.CodigoBarras, p.DescripcionCategoria, p.DescripcionSubfamilia, p.Proveedor, p.Precio1IVAUV, p.Precio2IVAUV, p.CostoNeto, p.Ganancia1, p.Ganancia2, p.MovimientoPorAño, i.url_imagen FROM productos p LEFT JOIN imagenes i ON p.SKU = i.producto_sku $where_clause");
    while ($row = $download_result->fetch_assoc()) {
        echo implode("\t", $row) . "\n";
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Catálogo de Productos</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        table th, table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        table th { background-color: #f4f4f4; }
        .pagination { text-align: center; margin: 20px 0; }
        .pagination a { margin: 0 5px; text-decoration: none; padding: 5px 10px; border: 1px solid #ddd; }
        .pagination a:hover { background-color: #f0f0f0; }
    </style>
</head>
<body>
    <h1>Catálogo de Productos</h1>
    <form method="GET">
        <label>SKU: <input type="text" name="sku" value="<?php echo htmlspecialchars($_GET['sku'] ?? ''); ?>"></label>
        <label>Descripción: <input type="text" name="descripcion" value="<?php echo htmlspecialchars($_GET['descripcion'] ?? ''); ?>"></label>
        <label>Subfamilia: <input type="text" name="descripcion_subfamilia" value="<?php echo htmlspecialchars($_GET['descripcion_subfamilia'] ?? ''); ?>"></label>
        <label>Proveedor: <input type="text" name="proveedor" value="<?php echo htmlspecialchars($_GET['proveedor'] ?? ''); ?>"></label>
        <label>Precio 1: <input type="number" step="0.01" name="precio1" value="<?php echo htmlspecialchars($_GET['precio1'] ?? ''); ?>"></label>
        <label>Precio 2: <input type="number" step="0.01" name="precio2" value="<?php echo htmlspecialchars($_GET['precio2'] ?? ''); ?>"></label>
        <label>Ganancia 1: <input type="number" step="0.01" name="ganancia1" value="<?php echo htmlspecialchars($_GET['ganancia1'] ?? ''); ?>"></label>
        <label>Ganancia 2: <input type="number" step="0.01" name="ganancia2" value="<?php echo htmlspecialchars($_GET['ganancia2'] ?? ''); ?>"></label>
        <label>Movimiento por Año: <input type="number" name="movimiento_por_año" value="<?php echo htmlspecialchars($_GET['movimiento_por_año'] ?? ''); ?>"></label>
        <button type="submit">Filtrar</button>
        <a href="?<?php echo http_build_query(array_merge($_GET, ['download' => 'excel'])); ?>">Descargar Excel</a>
    </form>
    <table>
        <thead>
            <tr>
                <th>SKU</th>
                <th>Descripción</th>
                <th>Código de Barras</th>
                <th>Categoría</th>
                <th>Subfamilia</th>
                <th>Proveedor</th>
                <th>Precio 1</th>
                <th>Precio 2</th>
                <th>Costo Neto</th>
                <th>Ganancia 1</th>
                <th>Ganancia 2</th>
                <th>Movimiento por Año</th>
                <th>Imagen</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $row['SKU']; ?></td>
                    <td><?php echo $row['Descripcion']; ?></td>
                    <td><?php echo $row['CodigoBarras']; ?></td>
                    <td><?php echo $row['DescripcionCategoria']; ?></td>
                    <td><?php echo $row['DescripcionSubfamilia']; ?></td>
                    <td><?php echo $row['Proveedor']; ?></td>
                    <td><?php echo $row['Precio1IVAUV']; ?></td>
                    <td><?php echo $row['Precio2IVAUV']; ?></td>
                    <td><?php echo $row['CostoNeto']; ?></td>
                    <td><?php echo $row['Ganancia1']; ?></td>
                    <td><?php echo $row['Ganancia2']; ?></td>
                    <td><?php echo $row['MovimientoPorAño']; ?></td>
                    <td>
                        <?php if ($row['url_imagen']): ?>
                            <a href="<?php echo $row['url_imagen']; ?>" target="_blank">Ver Imagen</a>
                        <?php else: ?>
                            Sin Imagen
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
    <div class="pagination">
        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <a href="?page=<?php echo $i; ?>&<?php echo http_build_query($_GET); ?>"><?php echo $i; ?></a>
        <?php endfor; ?>
    </div>
</body>
</html>

