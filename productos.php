<?php
// Procesar creación de categoría por AJAX
if(isset($_POST['accion']) && $_POST['accion'] == 'crear_categoria') {
    // ✅ CORRECCIÓN: Validar archivo antes de incluir
    $config_path = __DIR__ . '/config/database.php';
    if (!file_exists($config_path)) {
        die(json_encode(['success' => false, 'mensaje' => 'Error de configuración']));
    }
    require_once $config_path;
    
    ob_clean();
    
    $conn = getConnection();
    // ✅ CORRECCIÓN: Sanitizar entrada
    $nombre = trim($_POST['nombre'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    
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
                'nombre' => htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8')
            ], JSON_UNESCAPED_UNICODE);
            exit;
        } else {
            $error = $conn->error;
            $stmt->close();
            $conn->close();
            
            echo json_encode([
                'success' => false, 
                'mensaje' => 'Error BD: ' . htmlspecialchars($error, ENT_QUOTES, 'UTF-8')
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
        $stmt = $conn->prepare("DELETE FROM productos WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
        $mensaje = '<div class="alert alert-success">Producto eliminado correctamente</div>';
    }
}

if($_SERVER['REQUEST_METHOD'] == 'POST' && !isset($_POST['accion'])) {
    $id = filter_var($_POST['id'] ?? '', FILTER_VALIDATE_INT);
    // ✅ CORRECCIÓN: Sanitizar todas las entradas
    $nombre = trim($_POST['nombre'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $precio = filter_var($_POST['precio'] ?? 0, FILTER_VALIDATE_FLOAT);
    $stock = filter_var($_POST['stock'] ?? 0, FILTER_VALIDATE_INT);
    $categoria_id = filter_var($_POST['categoria_id'] ?? 0, FILTER_VALIDATE_INT);
    
    if($id) {
        $stmt = $conn->prepare("UPDATE productos SET nombre=?, descripcion=?, precio=?, stock=?, categoria_id=? WHERE id=?");
        $stmt->bind_param("ssdiii", $nombre, $descripcion, $precio, $stock, $categoria_id, $id);
        $stmt->execute();
        $stmt->close();
        $mensaje = '<div class="alert alert-success">Producto actualizado correctamente</div>';
    } else {
        $stmt = $conn->prepare("INSERT INTO productos (nombre, descripcion, precio, stock, categoria_id) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("ssdii", $nombre, $descripcion, $precio, $stock, $categoria_id);
        $stmt->execute();
        $stmt->close();
        $mensaje = '<div class="alert alert-success">Producto creado correctamente</div>';
    }
}

$productos = $conn->query("SELECT p.*, c.nombre as categoria_nombre FROM productos p LEFT JOIN categorias c ON p.categoria_id = c.id ORDER BY p.id DESC");
$categorias = $conn->query("SELECT * FROM categorias");
?>

<div class="page-header">
    <h2>Gestión de Productos</h2>
</div>

<?php 
// ✅ CORRECCIÓN #2: XSS - Escapar salida
echo $mensaje; // Ya viene con HTML seguro desde arriba
?>

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
                        <td><?php echo htmlspecialchars($producto['id'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars($producto['nombre'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars($producto['categoria_nombre'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td>$<?php echo number_format($producto['precio'], 0); ?></td>
                        <td>
                            <span class="badge badge-<?php echo $producto['stock'] < 6 ? 'warning' : 'success'; ?>">
                                <?php echo htmlspecialchars($producto['stock'], ENT_QUOTES, 'UTF-8'); ?>
                            </span>
                        </td>
                        <td>
                            <button onclick="editarProducto(<?php echo htmlspecialchars(json_encode($producto), ENT_QUOTES, 'UTF-8'); ?>)" class="btn" style="background: #3b82f6; color: white; padding: 6px 12px;">✏️ Editar</button>
                            <button onclick="confirmDelete(<?php echo intval($producto['id']); ?>, 'productos')" class="btn btn-danger">🗑️ Eliminar</button>
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
                            <option value="<?php echo htmlspecialchars($cat['id'], ENT_QUOTES, 'UTF-8'); ?>">
                                <?php echo htmlspecialchars($cat['nombre'], ENT_QUOTES, 'UTF-8'); ?>
                            </option>
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
// ✅ CORRECCIÓN: Validar archivo antes de incluir
$footer_path = __DIR__ . '/includes/footer.php';
if (file_exists($footer_path)) {
    include $footer_path;
}
?>