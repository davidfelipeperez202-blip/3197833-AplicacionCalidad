<?php
require_once 'config/database.php';

$error = '';

// Si ya está autenticado, redirigir
if(isLoggedIn()) {
    header('Location: dashboard.php');
    exit();
}

// Procesar login
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if($username && $password) {
        $conn = getConnection();
        $stmt = $conn->prepare("SELECT id, username, nombre_completo FROM usuarios WHERE username = ? AND password = MD5(?)");
        $stmt->bind_param("ss", $username, $password);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['nombre_completo'] = $user['nombre_completo'];
            
            header('Location: dashboard.php');
            exit();
        } else {
            $error = 'Usuario o contraseña incorrectos';
        }
        
        $stmt->close();
        $conn->close();
    } else {
        $error = 'Por favor complete todos los campos';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Carpintería El Roble</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <div class="login-icon">🪵</div>
                <h1>Carpintería El Roble</h1>
                <p>Sistema de Gestión Integral</p>
            </div>
            
            <?php if($error): ?>
                <div class="alert alert-danger">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label>Usuario</label>
                    <input type="text" name="username" placeholder="Ingrese su usuario" required>
                </div>
                
                <div class="form-group">
                    <label>Contraseña</label>
                    <input type="password" name="password" placeholder="Ingrese su contraseña" required>
                </div>
                
                <button type="submit" class="btn btn-primary">
                    Iniciar Sesión
                </button>
            </form>
            
            <div style="margin-top: 20px; text-align: center; font-size: 14px; color: #6b7280;">
                <p>Usuario de prueba: <strong>admin</strong></p>
                <p>Contraseña: <strong>admin123</strong></p>
            </div>
        </div>
    </div>
</body>
</html>