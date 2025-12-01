<?php 
// ✅ CORRECCIÓN: Validar archivo antes de incluir
$header_path = __DIR__ . '/includes/header.php';
if (file_exists($header_path)) {
    include $header_path;
} else {
    die('Error: Archivo header.php no encontrado');
}
?>

<?php
$conn = getConnection();
$mensaje = '';

// ✅ CORRECCIÓN #1: SQL Injection - Usar prepared statements
if(isset($_GET['delete'])) {
    $id = filter_var($_GET['delete'], FILTER_VALIDATE_INT);
    if($id) {
        $stmt = $conn->prepare("DELETE FROM pedidos WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
        $mensaje = '<div class="alert alert-success">Pedido eliminado correctamente</div>';
    }
}

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = filter_var($_POST['id'] ?? '', FILTER_VALIDATE_INT);
    // ✅ CORRECCIÓN: Sanitizar y validar entradas
    $cliente_id = filter_var($_POST['cliente_id'] ?? 0, FILTER_VALIDATE_INT);
    $producto_id = filter_var($_POST['producto_id'] ?? 0, FILTER_VALIDATE_INT);
    $cantidad = filter_var($_POST['cantidad'] ?? 1, FILTER_VALIDATE_INT);
    $estado = trim($_POST['estado'] ?? 'Pendiente');
    
    // ✅ CORRECCIÓN #2: SQL Injection - Usar prepared statement para calcular total
    $stmt_producto = $conn->prepare("SELECT precio FROM productos WHERE id = ?");
    $stmt_producto->bind_param("i", $producto_id);
    $stmt_producto->execute();
    $result_producto = $stmt_producto->get_result();
    $producto = $result_producto->fetch_assoc();
    $stmt_producto->close();
    
    $total = $producto['precio'] * $cantidad;
    
    if($id) {
        // Actualizar
        $stmt = $conn->prepare("UPDATE pedidos SET cliente_id=?, producto_id=?, cantidad=?, total=?, estado=? WHERE id=?");
        $stmt->bind_param("iiidsi", $cliente_id, $producto_id, $cantidad, $total, $estado, $id);
        $stmt->execute();
        $stmt->close();
        $mensaje = '<div class="alert alert-success">Pedido actualizado correctamente</div>';
    } else {
        // Insertar
        $stmt = $conn->prepare("INSERT INTO pedidos (cliente_id, producto_id, cantidad, total, estado) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("iiids", $cliente_id, $producto_id, $cantidad, $total, $estado);
        $stmt->execute();
        $stmt->close();
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

<?php 
// ✅ CORRECCIÓN #3: XSS - Salida ya es segura
echo $mensaje; 
?>

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
                        <td><?php echo htmlspecialchars($pedido['id'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars($pedido['cliente_nombre'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars($pedido['producto_nombre'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars($pedido['cantidad'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td>$<?php echo number_format($pedido['total'], 0); ?></td>
                        <td>
                            <span class="badge badge-<?php 
                                echo $pedido['estado'] == 'Completado' ? 'success' : 
                                    ($pedido['estado'] == 'En Proceso' ? 'warning' : 'info'); 
                            ?>">
                                <?php echo htmlspecialchars($pedido['estado'], ENT_QUOTES, 'UTF-8'); ?>
                            </span>
                        </td>
                        <td><?php echo htmlspecialchars(date('Y-m-d', strtotime($pedido['fecha_pedido'])), ENT_QUOTES, 'UTF-8'); ?></td>
                        <td>
                            <button onclick="editarPedido(<?php echo htmlspecialchars(json_encode($pedido), ENT_QUOTES, 'UTF-8'); ?>)" class="btn" style="background: #3b82f6; color: white; padding: 6px 12px;">✏️ Editar</button>
                            <button onclick="confirmDelete(<?php echo intval($pedido['id']); ?>, 'pedidos')" class="btn btn-danger">🗑️ Eliminar</button>
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
                        <option value="<?php echo htmlspecialchars($cliente['id'], ENT_QUOTES, 'UTF-8'); ?>">
                            <?php echo htmlspecialchars($cliente['nombre'], ENT_QUOTES, 'UTF-8'); ?>
                        </option>
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
                        <option value="<?php echo htmlspecialchars($producto['id'], ENT_QUOTES, 'UTF-8'); ?>" 
                                data-precio="<?php echo htmlspecialchars($producto['precio'], ENT_QUOTES, 'UTF-8'); ?>">
                            <?php echo htmlspecialchars($producto['nombre'], ENT_QUOTES, 'UTF-8'); ?> - 
                            $<?php echo number_format($producto['precio'], 0); ?>
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
// ✅ CORRECCIÓN: Validar archivo antes de incluir
$footer_path = __DIR__ . '/includes/footer.php';
if (file_exists($footer_path)) {
    include $footer_path;
}
?>