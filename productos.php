<?php
// Procesar creación de categoría por AJAX
if(isset($_POST['accion']) && $_POST['accion'] == 'crear_categoria') {
    // NO cargar header ni nada
    require_once __DIR__ . '/config/database.php';
    
    // Limpiar cualquier salida previa
    ob_clean();
    
    $conn = getConnection();
    $nombre = trim($_POST['nombre'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    
    // Forzar tipo de contenido JSON
    header('Content-Type: application/json; charset=utf-8');
    
    if($nombre) {
        $stmt = $conn->prepare("INSERT INTO categorias (nombre, descripcion) VALUES (?, ?)");
        $stmt->bind_param("ss", $nombre, $descripcion);
        
        if($stmt->execute()) {
            $nuevo_id = $conn->insert_id;
            $stmt->close();
            $conn->close();
            
            echo json_encode([
                'success' => true, 
                'id' => $nuevo_id, 
                'nombre' => $nombre
            ], JSON_UNESCAPED_UNICODE);
            exit;
        } else {
            $error = $conn->error;
            $stmt->close();
            $conn->close();
            
            echo json_encode([
                'success' => false, 
                'mensaje' => 'Error BD: ' . $error
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
    } else {
        echo json_encode([
            'success' => false, 
            'mensaje' => 'Nombre requerido'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}
?>
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

if($_SERVER['REQUEST_METHOD'] == 'POST' && !isset($_POST['accion'])) {
    $id = $_POST['id'] ?? '';
    $nombre = $_POST['nombre'];
    $descripcion = $_POST['descripcion'];
    $precio = $_POST['precio'];
    $stock = $_POST['stock'];
    $categoria_id = $_POST['categoria_id'];
    
    if($id) {
        $stmt = $conn->prepare("UPDATE productos SET nombre=?, descripcion=?, precio=?, stock=?, categoria_id=? WHERE id=?");
        $stmt->bind_param("ssdiii", $nombre, $descripcion, $precio, $stock, $categoria_id, $id);
        $stmt->execute();
        $mensaje = '<div class="alert alert-success">Producto actualizado correctamente</div>';
    } else {
        $stmt = $conn->prepare("INSERT INTO productos (nombre, descripcion, precio, stock, categoria_id) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("ssdii", $nombre, $descripcion, $precio, $stock, $categoria_id);
        $stmt->execute();
        $mensaje = '<div class="alert alert-success">Producto creado correctamente</div>';
    }
}

$productos = $conn->query("SELECT p.*, c.nombre as categoria_nombre FROM productos p LEFT JOIN categorias c ON p.categoria_id = c.id ORDER BY p.id DESC");
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
                <div style="display: flex; gap: 8px;">
                    <select name="categoria_id" id="producto_categoria" required style="flex: 1;">
                        <?php 
                        $categorias->data_seek(0);
                        while($cat = $categorias->fetch_assoc()): 
                        ?>
                            <option value="<?php echo $cat['id']; ?>"><?php echo $cat['nombre']; ?></option>
                        <?php endwhile; ?>
                    </select>
                    <button type="button" onclick="abrirModalNuevaCategoria()" class="btn" style="background: #10b981; color: white; padding: 8px 16px; white-space: nowrap;">
                        ➕ Nueva
                    </button>
                </div>
                <p style="font-size: 12px; color: #6b7280; margin-top: 4px;">
                    💡 Si no encuentras la categoría, créala con el botón "Nueva"
                </p>
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
            <button type="button" onclick="closeModal('modalProducto')" class="btn" style="background: #6b7280; color: white;">❌ Cancelar</button>
        </form>
    </div>
</div>

<div id="modalNuevaCategoria" class="modal">
    <div class="modal-content" style="max-width: 400px;">
        <div class="modal-header">
            <h3>🏷️ Crear Nueva Categoría</h3>
            <span class="close" onclick="closeModal('modalNuevaCategoria')">&times;</span>
        </div>
        <form id="formNuevaCategoria">
            <div class="form-group">
                <label>Nombre de la Categoría *</label>
                <input type="text" id="nueva_categoria_nombre" placeholder="Ej: Muebles de Jardín" required>
            </div>
            
            <div class="form-group">
                <label>Descripción</label>
                <textarea id="nueva_categoria_descripcion" rows="3" placeholder="Descripción opcional..."></textarea>
            </div>
            
            <button type="button" onclick="guardarNuevaCategoria()" class="btn btn-primary">💾 Crear Categoría</button>
            <button type="button" onclick="closeModal('modalNuevaCategoria')" class="btn" style="background: #6b7280; color: white;">❌ Cancelar</button>
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

function abrirModalNuevaCategoria() {
    document.getElementById('nueva_categoria_nombre').value = '';
    document.getElementById('nueva_categoria_descripcion').value = '';
    openModal('modalNuevaCategoria');
}

function guardarNuevaCategoria() {
    const nombre = document.getElementById('nueva_categoria_nombre').value;
    const descripcion = document.getElementById('nueva_categoria_descripcion').value;
    
    if(!nombre) {
        alert('El nombre de la categoría es obligatorio');
        return;
    }
    
    const formData = new FormData();
    formData.append('accion', 'crear_categoria');
    formData.append('nombre', nombre);
    formData.append('descripcion', descripcion);
    
    fetch('productos.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if(data.success) {
            const select = document.getElementById('producto_categoria');
            const option = document.createElement('option');
            option.value = data.id;
            option.text = data.nombre;
            option.selected = true;
            select.appendChild(option);
            
            closeModal('modalNuevaCategoria');
            alert('✅ Categoría "' + data.nombre + '" creada correctamente');
        } else {
            alert('❌ Error: ' + data.mensaje);
        }
    })
    .catch(error => {
        alert('❌ Error al crear la categoría');
        console.error(error);
    });
}
</script>

<?php 
$conn->close();
include 'includes/footer.php'; 
?>