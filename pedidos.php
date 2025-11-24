<?php include 'includes/header.php'; ?>

<?php
$conn = getConnection();
$mensaje = '';

// Procesar acciones
if(isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $conn->query("DELETE FROM pedidos WHERE id = $id");
    $mensaje = '<div class="alert alert-success">Pedido eliminado correctamente</div>';
}

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['id'] ?? '';
    $cliente_id = $_POST['cliente_id'];
    $producto_id = $_POST['producto_id'];
    $cantidad = $_POST['cantidad'];
    $estado = $_POST['estado'];
    
    // Calcular total
    $producto = $conn->query("SELECT precio FROM productos WHERE id = $producto_id")->fetch_assoc();
    $total = $producto['precio'] * $cantidad;
    
    if($id) {
        // Actualizar
        $stmt = $conn->prepare("UPDATE pedidos SET cliente_id=?, producto_id=?, cantidad=?, total=?, estado=? WHERE id=?");
        $stmt->bind_param("iiidsi", $cliente_id, $producto_id, $cantidad, $total, $estado, $id);
        $stmt->execute();
        $mensaje = '<div class="alert alert-success">Pedido actualizado correctamente</div>';
    } else {
        // Insertar
        $stmt = $conn->prepare("INSERT INTO pedidos (cliente_id, producto_id, cantidad, total, estado) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("iiids", $cliente_id, $producto_id, $cantidad, $total, $estado);
        $stmt->execute();
        $mensaje = '<div class="alert alert-success">Pedido creado correctamente</div>';
    }
}

// Obtener pedidos
$pedidos = $conn->query("
    SELECT p.*, c.nombre as cliente_nombre, pr.nombre as producto_nombre, pr.precio as producto_precio
    FROM pedidos p
    JOIN clientes c ON p.cliente_id = c.id
    JOIN productos pr ON p.producto_id = pr.id
    ORDER BY p.id DESC
");

// Obtener clientes y productos para el formulario
$clientes = $conn->query("SELECT id, nombre FROM clientes ORDER BY nombre");
$productos = $conn->query("SELECT id, nombre, precio FROM productos ORDER BY nombre");
?>

<div class="page-header">
    <h2>Gestión de Pedidos</h2>
</div>

<?php echo $mensaje; ?>

<div class="card">
    <div class="card-header">
        <h3>Lista de Pedidos</h3>
        <button onclick="openModal('modalPedido')" class="btn btn-success">+ Nuevo Pedido</button>
    </div>
    <div class="card-body">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Cliente</th>
                    <th>Producto</th>
                    <th>Cantidad</th>
                    <th>Total</th>
                    <th>Estado</th>
                    <th>Fecha</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php while($pedido = $pedidos->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $pedido['id']; ?></td>
                        <td><?php echo $pedido['cliente_nombre']; ?></td>
                        <td><?php echo $pedido['producto_nombre']; ?></td>
                        <td><?php echo $pedido['cantidad']; ?></td>
                        <td>$<?php echo number_format($pedido['total'], 0); ?></td>
                        <td>
                            <span class="badge badge-<?php 
                                echo $pedido['estado'] == 'Completado' ? 'success' : 
                                    ($pedido['estado'] == 'En Proceso' ? 'warning' : 'info'); 
                            ?>">
                                <?php echo $pedido['estado']; ?>
                            </span>
                        </td>
                        <td><?php echo date('Y-m-d', strtotime($pedido['fecha_pedido'])); ?></td>
                        <td>
                            <button onclick="editarPedido(<?php echo htmlspecialchars(json_encode($pedido)); ?>)" class="btn" style="background: #3b82f6; color: white; padding: 6px 12px;">✏️ Editar</button>
                            <button onclick="confirmDelete(<?php echo $pedido['id']; ?>, 'pedidos')" class="btn btn-danger">🗑️ Eliminar</button>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal -->
<div id="modalPedido" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="modalTitle">Nuevo Pedido</h3>
            <span class="close" onclick="closeModal('modalPedido')">&times;</span>
        </div>
        <form method="POST" action="">
            <input type="hidden" name="id" id="pedido_id">
            
            <div class="form-group">
                <label>Cliente</label>
                <select name="cliente_id" id="pedido_cliente" required>
                    <option value="">Seleccione un cliente</option>
                    <?php 
                    $clientes->data_seek(0);
                    while($cliente = $clientes->fetch_assoc()): 
                    ?>
                        <option value="<?php echo $cliente['id']; ?>"><?php echo $cliente['nombre']; ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label>Producto</label>
                <select name="producto_id" id="pedido_producto" required>
                    <option value="">Seleccione un producto</option>
                    <?php 
                    $productos->data_seek(0);
                    while($producto = $productos->fetch_assoc()): 
                    ?>
                        <option value="<?php echo $producto['id']; ?>" data-precio="<?php echo $producto['precio']; ?>">
                            <?php echo $producto['nombre']; ?> - $<?php echo number_format($producto['precio'], 0); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label>Cantidad</label>
                <input type="number" name="cantidad" id="pedido_cantidad" min="1" value="1" required>
            </div>
            
            <div class="form-group">
                <label>Estado</label>
                <select name="estado" id="pedido_estado" required>
                    <option value="Pendiente">Pendiente</option>
                    <option value="En Proceso">En Proceso</option>
                    <option value="Completado">Completado</option>
                </select>
            </div>
            
            <button type="submit" class="btn btn-primary">💾 Guardar</button>
            <button type="button" onclick="closeModal('modalPedido')" class="btn" style="background: #6b7280; color: white;">❌ Cancelar</button>
        </form>
    </div>
</div>

<script>
function editarPedido(pedido) {
    document.getElementById('modalTitle').textContent = 'Editar Pedido';
    document.getElementById('pedido_id').value = pedido.id;
    document.getElementById('pedido_cliente').value = pedido.cliente_id;
    document.getElementById('pedido_producto').value = pedido.producto_id;
    document.getElementById('pedido_cantidad').value = pedido.cantidad;
    document.getElementById('pedido_estado').value = pedido.estado;
    openModal('modalPedido');
}
</script>

<?php 
$conn->close();
include 'includes/footer.php'; 
?>
