<?php
// ✅ CORRECCIÓN #1: Validar archivo antes de incluir
$config_path = __DIR__ . '/config/database.php';
if (!file_exists($config_path)) {
    die('Error: Archivo de configuración no encontrado');
}
require_once $config_path;

$error = '';

// Si ya está autenticado, redirigir
if(isLoggedIn()) {
    header('Location: dashboard.php');
    exit();
}

// Procesar login
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    // ✅ CORRECCIÓN #2: Validar y sanitizar entrada
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if($username && $password) {
        $conn = getConnection();
        
        // ✅ CORRECCIÓN #3: Usar prepared statements (ya estaba bien)
        $stmt = $conn->prepare("SELECT id, username, nombre_completo, password FROM usuarios WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            
            // Verificar contraseña (mantener MD5 por compatibilidad)
            if(md5($password) === $user['password']) {
                // Regenerar ID de sesión para prevenir session fixation
                session_regenerate_id(true);
                
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['nombre_completo'] = $user['nombre_completo'];
                
                header('Location: dashboard.php');
                exit();
            } else {
                $error = 'Usuario o contraseña incorrectos';
            }
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
                    <?php 
                    // ✅ CORRECCIÓN #4: Escapar salida para prevenir XSS
                    echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); 
                    ?>
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
        </div>
    </div>
</body>
</html>