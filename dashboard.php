<?php include 'includes/header.php'; ?>

<?php
$conn = getConnection();

// Obtener estadísticas
$total_productos = $conn->query("SELECT COUNT(*) as total FROM productos")->fetch_assoc()['total'];
$total_clientes = $conn->query("SELECT COUNT(*) as total FROM clientes")->fetch_assoc()['total'];
$total_pedidos = $conn->query("SELECT COUNT(*) as total FROM pedidos")->fetch_assoc()['total'];

// Pedidos recientes
$pedidos_recientes = $conn->query("
    SELECT p.*, c.nombre as cliente_nombre, pr.nombre as producto_nombre 
    FROM pedidos p
    JOIN clientes c ON p.cliente_id = c.id
    JOIN productos pr ON p.producto_id = pr.id
    ORDER BY p.fecha_pedido DESC
    LIMIT 5
");

// Productos con stock bajo
$productos_bajo_stock = $conn->query("
    SELECT * FROM productos 
    WHERE stock < 6 
    ORDER BY stock ASC
");
?>

<div class="page-header">
    <h2>Dashboard</h2>
</div>

<div class="stats-grid">
    <div class="stat-card blue">
        <div>
            <div class="stat-label">Total Productos</div>
            <div class="stat-value"><?php echo $total_productos; ?></div>
        </div>
        <div style="font-size: 48px;">📦</div>
    </div>
    
    <div class="stat-card green">
        <div>
            <div class="stat-label">Total Clientes</div>
            <div class="stat-value"><?php echo $total_clientes; ?></div>
        </div>
        <div style="font-size: 48px;">👥</div>
    </div>
    
    <div class="stat-card purple">
        <div>
            <div class="stat-label">Total Pedidos</div>
            <div class="stat-value"><?php echo $total_pedidos; ?></div>
        </div>
        <div style="font-size: 48px;">📋</div>
    </div>
</div>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 24px;">
    <div class="card">
        <div class="card-header">
            <h3>Pedidos Recientes</h3>
        </div>
        <div class="card-body">
            <table>
                <thead>
                    <tr>
                        <th>Cliente</th>
                        <th>Producto</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($pedido = $pedidos_recientes->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $pedido['cliente_nombre']; ?></td>
                            <td><?php echo $pedido['producto_nombre']; ?></td>
                            <td>
                                <span class="badge badge-<?php 
                                    echo $pedido['estado'] == 'Completado' ? 'success' : 
                                        ($pedido['estado'] == 'En Proceso' ? 'warning' : 'info'); 
                                ?>">
                                    <?php echo $pedido['estado']; ?>
                                </span>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header">
            <h3>Productos con Stock Bajo</h3>
        </div>
        <div class="card-body">
            <table>
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th>Stock</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($producto = $productos_bajo_stock->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $producto['nombre']; ?></td>
                            <td>
                                <span class="badge badge-warning">
                                    <?php echo $producto['stock']; ?>
                                </span>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php 
$conn->close();
include 'includes/footer.php'; 
?>
