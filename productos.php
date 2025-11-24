<?php include 'includes/header.php'; ?>

<?php
$conn = getConnection();
$mensaje = '';

// Procesar acciones
if(isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $conn->query("DELETE FROM productos WHERE id = $id");
    $mensaje = '<div class="alert alert-success">Producto eliminado correctamente</div>';
}

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['id'] ?? '';
    $nombre = $_POST['nombre'];
    $descripcion = $_POST['descripcion'];
    $precio = $_POST['precio'];
    $stock = $_POST['stock'];
    $categoria_id = $_POST['categoria_id'];
    
    if($id) {
        // Actualizar
        $stmt = $conn->prepare("UPDATE productos SET nombre=?, descripcion=?, precio=?, stock=?, categoria_id=? WHERE id=?");
        $stmt->bind_param("ssdiii", $nombre, $descripcion, $precio, $stock, $categoria_id, $id);
        $stmt->execute();
        $mensaje = '<div class="alert alert-success">Producto actualizado correctamente</div>';
    } else {
        // Insertar
        $stmt = $conn->prepare("INSERT INTO productos (nombre, descripcion, precio, stock, categoria_id) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("ssdii", $nombre, $descripcion, $precio, $stock, $categoria_id);
        $stmt->execute();
        $mensaje = '<div class="alert alert-success">Producto creado correctamente</div>';
    }
}

// Obtener productos
$productos = $conn->query("
    SELECT p.*, c.nombre as categoria_nombre 
    FROM productos p 
    LEFT JOIN categorias c ON p.categoria_id = c.id 
    ORDER BY p.id DESC
");

// Obtener categorías
$categorias = $conn->query("SELECT * FROM categorias");
?>

<div class="page-header">
    <h2>Gestión de Productos</h2>
</div>

<?php echo $mensaje; ?>

<div class="card">
    <div class="card-header">
        <h3>Lista de Productos</h3>
        <button onclick="openModal('modalProducto')" class="btn btn-success">+ Nuevo Producto</button>
    </div>
    <div class="card-body">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Categoría</th>
                    <th>Precio</th>
                    <th>Stock</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php while($producto = $productos->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $producto['id']; ?></td>
                        <td><?php echo $producto['nombre']; ?></td>
                        <td><?php echo $producto['categoria_nombre']; ?></td>
                        <td>$<?php echo number_format($producto['precio'], 0); ?></td>
                        <td>
                            <span class="badge badge-<?php echo $producto['stock'] < 6 ? 'warning' : 'success'; ?>">
                                <?php echo $producto['stock']; ?>
                            </span>
                        </td>
                        <td>
                            <button onclick="editarProducto(<?php echo htmlspecialchars(json_encode($producto)); ?>)" class="btn" style="background: #3b82f6; color: white; padding: 6px 12px;">✏️ Editar</button>
                            <button onclick="confirmDelete(<?php echo $producto['id']; ?>, 'productos')" class="btn btn-danger">🗑️ Eliminar</button>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal -->
<div id="modalProducto" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="modalTitle">Nuevo Producto</h3>
            <span class="close" onclick="closeModal('modalProducto')">&times;</span>
        </div>
        <form method="POST" action="">
            <input type="hidden" name="id" id="producto_id">
            
            <div class="form-group">
                <label>Nombre</label>
                <input type="text" name="nombre" id="producto_nombre" required>
            </div>
            
            <div class="form-group">
                <label>Descripción</label>
                <textarea name="descripcion" id="producto_descripcion" rows="3"></textarea>
            </div>
            
            <div class="form-group">
                <label>Categoría</label>
                <select name="categoria_id" id="producto_categoria" required>
                    <?php 
                    $categorias->data_seek(0);
                    while($cat = $categorias->fetch_assoc()): 
                    ?>
                        <option value="<?php echo $cat['id']; ?>"><?php echo $cat['nombre']; ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label>Precio</label>
                <input type="number" name="precio" id="producto_precio" step="0.01" required>
            </div>
            
            <div class="form-group">
                <label>Stock</label>
                <input type="number" name="stock" id="producto_stock" required>
            </div>
            
            <button type="submit" class="btn btn-primary">💾 Guardar</button>
            <button type="button" onclick="closeModal('modalProducto')" class="btn btn-secondary">❌ Cancelar</button>
        </form>
    </div>
</div>

<script>
function editarProducto(producto) {
    document.getElementById('modalTitle').textContent = 'Editar Producto';
    document.getElementById('producto_id').value = producto.id;
    document.getElementById('producto_nombre').value = producto.nombre;
    document.getElementById('producto_descripcion').value = producto.descripcion;
    document.getElementById('producto_categoria').value = producto.categoria_id;
    document.getElementById('producto_precio').value = producto.precio;
    document.getElementById('producto_stock').value = producto.stock;
    openModal('modalProducto');
}
</script>

<?php 
$conn->close();
include 'includes/footer.php'; 
?>