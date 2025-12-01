<?php
// ✅ CORRECCIÓN: Validar archivo antes de incluir
$config_path = __DIR__ . '/../config/database.php';
if (!file_exists($config_path)) {
    die('Error: Archivo de configuración no encontrado');
}
require_once $config_path;

requireLogin();

$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carpintería El Roble - Sistema de Gestión</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="navbar-container">
            <div class="navbar-brand">
                <span>🪵</span>
                <div>
                    <h1>Carpintería El Roble</h1>
                    <p style="font-size: 12px; opacity: 0.9;">Sistema de Gestión</p>
                </div>
            </div>
            <div class="navbar-user">
                <span>👤 <?php 
                    // ✅ CORRECCIÓN: Escapar salida del nombre de usuario
                    echo htmlspecialchars($_SESSION['username'], ENT_QUOTES, 'UTF-8'); 
                ?></span>
                <a href="logout.php" class="btn btn-danger">Salir</a>
            </div>
        </div>
    </nav>
    
    <div class="navbar-menu">
        <ul>
            <li><a href="dashboard.php" class="<?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">📊 Dashboard</a></li>
            <li><a href="categorias.php" class="<?php echo $current_page == 'categorias.php' ? 'active' : ''; ?>">🏷️ Categorías</a></li>
            <li><a href="productos.php" class="<?php echo $current_page == 'productos.php' ? 'active' : ''; ?>">📦 Productos</a></li>
            <li><a href="clientes.php" class="<?php echo $current_page == 'clientes.php' ? 'active' : ''; ?>">👥 Clientes</a></li>
            <li><a href="pedidos.php" class="<?php echo $current_page == 'pedidos.php' ? 'active' : ''; ?>">📋 Pedidos</a></li>
        </ul>
    </div>
    
    <div class="container">


</div>
    
    <script>
        function openModal(modalId) {
            document.getElementById(modalId).classList.add('active');
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('active');
        }
        
        function confirmDelete(id, type) {
            if(confirm('¿Está seguro de eliminar este registro?')) {
                window.location.href = type + '.php?delete=' + id;
            }
        }
    </script>
</body>
</html>