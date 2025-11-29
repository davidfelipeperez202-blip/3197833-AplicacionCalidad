<?php include 'includes/header.php'; ?>

<?php
$conn = getConnection();
$mensaje = '';

// Procesar acciones
if(isset($_GET['delete'])) {
    $id = $_GET['delete'];
    // Verificar si hay productos con esta categoría
    $check = $conn->query("SELECT COUNT(*) as total FROM productos WHERE categoria_id = $id")->fetch_assoc();
    
    if($check['total'] > 0) {
        $mensaje = '<div class="alert alert-danger">No se puede eliminar esta categoría porque tiene productos asociados</div>';
    } else {
        $conn->query("DELETE FROM categorias WHERE id = $id");
        $mensaje = '<div class="alert alert-success">Categoría eliminada correctamente</div>';
    }
}

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['id'] ?? '';
    $nombre = $_POST['nombre'];
    $descripcion = $_POST['descripcion'];
    
    if($id) {
        // Actualizar
        $stmt = $conn->prepare("UPDATE categorias SET nombre=?, descripcion=? WHERE id=?");
        $stmt->bind_param("ssi", $nombre, $descripcion, $id);
        $stmt->execute();
        $mensaje = '<div class="alert alert-success">Categoría actualizada correctamente</div>';
    } else {
        // Insertar
        $stmt = $conn->prepare("INSERT INTO categorias (nombre, descripcion) VALUES (?, ?)");
        $stmt->bind_param("ss", $nombre, $descripcion);
        $stmt->execute();
        $mensaje = '<div class="alert alert-success">Categoría creada correctamente</div>';
    }
}

// Obtener categorías con conteo de productos
$categorias = $conn->query("
    SELECT c.*, COUNT(p.id) as total_productos 
    FROM categorias c 
    LEFT JOIN productos p ON c.id = p.categoria_id 
    GROUP BY c.id 
    ORDER BY c.id DESC
");
?>

<div class="page-header">
    <h2>Gestión de Categorías (Tipos de Producto)</h2>
</div>

<?php echo $mensaje; ?>

<div class="card">
    <div class="card-header">
        <h3>Lista de Categorías</h3>
        <button onclick="openModal('modalCategoria')" class="btn btn-success">+ Nueva Categoría</button>
    </div>
    <div class="card-body">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Descripción</th>
                    <th>Productos Asociados</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php while($categoria = $categorias->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $categoria['id']; ?></td>
                        <td><strong><?php echo $categoria['nombre']; ?></strong></td>
                        <td><?php echo $categoria['descripcion']; ?></td>
                        <td>
                            <span class="badge badge-info">
                                <?php echo $categoria['total_productos']; ?> productos
                            </span>
                        </td>
                        <td>
                            <button onclick="editarCategoria(<?php echo htmlspecialchars(json_encode($categoria)); ?>)" class="btn" style="background: #3b82f6; color: white; padding: 6px 12px;">✏️ Editar</button>
                            <?php if($categoria['total_productos'] == 0): ?>
                                <button onclick="confirmDelete(<?php echo $categoria['id']; ?>, 'categorias')" class="btn btn-danger">🗑️ Eliminar</button>
                            <?php else: ?>
                                <button class="btn" style="background: #9ca3af; color: white; padding: 6px 12px; cursor: not-allowed;" disabled title="No se puede eliminar porque tiene productos">🔒 Bloqueada</button>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="card" style="margin-top: 24px;">
    <div class="card-header">
        <h3>💡 Ejemplos de Categorías</h3>
    </div>
    <div class="card-body">
        <p><strong>Categorías sugeridas para carpintería:</strong></p>
        <ul style="margin-left: 24px; line-height: 2;">
            <li><strong>Muebles de Hogar</strong> - Mesas, sillas, camas, armarios</li>
            <li><strong>Muebles de Oficina</strong> - Escritorios, archivadores, estanterías</li>
            <li><strong>Herramientas Eléctricas</strong> - Sierras, taladros, lijadoras</li>
            <li><strong>Herramientas Manuales</strong> - Martillos, serruchos, formones</li>
            <li><strong>Puertas y Ventanas</strong> - Marcos, puertas, ventanas de madera</li>
            <li><strong>Decoración</strong> - Marcos, espejos, repisas decorativas</li>
            <li><strong>Muebles de Jardín</strong> - Bancas, pérgolas, jardineras</li>
            <li><strong>Materiales</strong> - Maderas, barnices, pegamentos</li>
        </ul>
    </div>
</div>

<!-- Modal -->
<div id="modalCategoria" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="modalTitle">Nueva Categoría</h3>
            <span class="close" onclick="closeModal('modalCategoria')">&times;</span>
        </div>
        <form method="POST" action="">
            <input type="hidden" name="id" id="categoria_id">
            
            <div class="form-group">
                <label>Nombre de la Categoría *</label>
                <input type="text" name="nombre" id="categoria_nombre" placeholder="Ej: Muebles de Jardín" required>
            </div>
            
            <div class="form-group">
                <label>Descripción</label>
                <textarea name="descripcion" id="categoria_descripcion" rows="4" placeholder="Describe el tipo de productos que incluye esta categoría..."></textarea>
            </div>
            
            <div style="background: #fef3c7; padding: 12px; border-radius: 6px; margin-bottom: 16px;">
                <p style="margin: 0; font-size: 14px; color: #92400e;">
                    <strong>💡 Consejo:</strong> Las categorías te permiten organizar mejor tus productos. Por ejemplo: "Muebles de Oficina", "Herramientas Eléctricas", "Decoración", etc.
                </p>
            </div>
            
            <button type="submit" class="btn btn-primary">💾 Guardar Categoría</button>
            <button type="button" onclick="closeModal('modalCategoria')" class="btn" style="background: #6b7280; color: white;">❌ Cancelar</button>
        </form>
    </div>
</div>

<script>
function editarCategoria(categoria) {
    document.getElementById('modalTitle').textContent = 'Editar Categoría';
    document.getElementById('categoria_id').value = categoria.id;
    document.getElementById('categoria_nombre').value = categoria.nombre;
    document.getElementById('categoria_descripcion').value = categoria.descripcion;
    openModal('modalCategoria');
}
</script>

<?php 
$conn->close();
include 'includes/footer.php'; 
?>