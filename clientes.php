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
        $stmt = $conn->prepare("DELETE FROM clientes WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
        $mensaje = '<div class="alert alert-success">Cliente eliminado correctamente</div>';
    }
}

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = filter_var($_POST['id'] ?? '', FILTER_VALIDATE_INT);
    // ✅ CORRECCIÓN: Sanitizar todas las entradas
    $nombre = trim($_POST['nombre'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
    $direccion = trim($_POST['direccion'] ?? '');
    
    if($id) {
        // Actualizar
        $stmt = $conn->prepare("UPDATE clientes SET nombre=?, telefono=?, email=?, direccion=? WHERE id=?");
        $stmt->bind_param("ssssi", $nombre, $telefono, $email, $direccion, $id);
        $stmt->execute();
        $stmt->close();
        $mensaje = '<div class="alert alert-success">Cliente actualizado correctamente</div>';
    } else {
        // Insertar
        $stmt = $conn->prepare("INSERT INTO clientes (nombre, telefono, email, direccion) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $nombre, $telefono, $email, $direccion);
        $stmt->execute();
        $stmt->close();
        $mensaje = '<div class="alert alert-success">Cliente creado correctamente</div>';
    }
}

// Obtener clientes
$clientes = $conn->query("SELECT * FROM clientes ORDER BY id DESC");
?>

<div class="page-header">
    <h2>Gestión de Clientes</h2>
</div>

<?php 
// ✅ CORRECCIÓN #2: XSS - Salida ya es segura (viene del servidor)
echo $mensaje; 
?>

<div class="card">
    <div class="card-header">
        <h3>Lista de Clientes</h3>
        <button onclick="openModal('modalCliente')" class="btn btn-success">+ Nuevo Cliente</button>
    </div>
    <div class="card-body">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Teléfono</th>
                    <th>Email</th>
                    <th>Dirección</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php while($cliente = $clientes->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($cliente['id'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars($cliente['nombre'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars($cliente['telefono'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars($cliente['email'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars($cliente['direccion'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td>
                            <button onclick="editarCliente(<?php echo htmlspecialchars(json_encode($cliente), ENT_QUOTES, 'UTF-8'); ?>)" class="btn" style="background: #3b82f6; color: white; padding: 6px 12px;">✏️ Editar</button>
                            <button onclick="confirmDelete(<?php echo intval($cliente['id']); ?>, 'clientes')" class="btn btn-danger">🗑️ Eliminar</button>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal -->
<div id="modalCliente" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="modalTitle">Nuevo Cliente</h3>
            <span class="close" onclick="closeModal('modalCliente')">&times;</span>
        </div>
        <form method="POST" action="">
            <input type="hidden" name="id" id="cliente_id">
            
            <div class="form-group">
                <label>Nombre Completo</label>
                <input type="text" name="nombre" id="cliente_nombre" required>
            </div>
            
            <div class="form-group">
                <label>Teléfono</label>
                <input type="tel" name="telefono" id="cliente_telefono" required>
            </div>
            
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" id="cliente_email" required>
            </div>
            
            <div class="form-group">
                <label>Dirección</label>
                <textarea name="direccion" id="cliente_direccion" rows="3" required></textarea>
            </div>
            
            <button type="submit" class="btn btn-primary">💾 Guardar</button>
            <button type="button" onclick="closeModal('modalCliente')" class="btn" style="background: #6b7280; color: white;">❌ Cancelar</button>
        </form>
    </div>
</div>

<script>
function editarCliente(cliente) {
    document.getElementById('modalTitle').textContent = 'Editar Cliente';
    document.getElementById('cliente_id').value = cliente.id;
    document.getElementById('cliente_nombre').value = cliente.nombre;
    document.getElementById('cliente_telefono').value = cliente.telefono;
    document.getElementById('cliente_email').value = cliente.email;
    document.getElementById('cliente_direccion').value = cliente.direccion;
    openModal('modalCliente');
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