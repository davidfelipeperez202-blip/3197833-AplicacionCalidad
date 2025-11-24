<?php include 'includes/header.php'; ?>

<?php
$conn = getConnection();
$mensaje = '';

// Procesar acciones
if(isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $conn->query("DELETE FROM clientes WHERE id = $id");
    $mensaje = '<div class="alert alert-success">Cliente eliminado correctamente</div>';
}

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['id'] ?? '';
    $nombre = $_POST['nombre'];
    $telefono = $_POST['telefono'];
    $email = $_POST['email'];
    $direccion = $_POST['direccion'];
    
    if($id) {
        // Actualizar
        $stmt = $conn->prepare("UPDATE clientes SET nombre=?, telefono=?, email=?, direccion=? WHERE id=?");
        $stmt->bind_param("ssssi", $nombre, $telefono, $email, $direccion, $id);
        $stmt->execute();
        $mensaje = '<div class="alert alert-success">Cliente actualizado correctamente</div>';
    } else {
        // Insertar
        $stmt = $conn->prepare("INSERT INTO clientes (nombre, telefono, email, direccion) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $nombre, $telefono, $email, $direccion);
        $stmt->execute();
        $mensaje = '<div class="alert alert-success">Cliente creado correctamente</div>';
    }
}

// Obtener clientes
$clientes = $conn->query("SELECT * FROM clientes ORDER BY id DESC");
?>

<div class="page-header">
    <h2>Gestión de Clientes</h2>
</div>

<?php echo $mensaje; ?>

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
                        <td><?php echo $cliente['id']; ?></td>
                        <td><?php echo $cliente['nombre']; ?></td>
                        <td><?php echo $cliente['telefono']; ?></td>
                        <td><?php echo $cliente['email']; ?></td>
                        <td><?php echo $cliente['direccion']; ?></td>
                        <td>
                            <button onclick="editarCliente(<?php echo htmlspecialchars(json_encode($cliente)); ?>)" class="btn" style="background: #3b82f6; color: white; padding: 6px 12px;">✏️ Editar</button>
                            <button onclick="confirmDelete(<?php echo $cliente['id']; ?>, 'clientes')" class="btn btn-danger">🗑️ Eliminar</button>
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
include 'includes/footer.php'; 
?>