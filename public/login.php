<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/Database.php';
require_once __DIR__ . '/../src/Repositories/UserRepository.php';
require_once __DIR__ . '/../src/Services/AuthService.php';
require_once __DIR__ . '/../src/helpers.php';

iniciarSesionSegura();

$pdo = Database::getConnection();
$userRepo = new UserRepository($pdo);
$authService = new AuthService($userRepo);

// si el usuario ya estaba logueado, lo mandamos directo a la aplicación
if ($authService->check()) {
    header('Location: index.php');
    exit;
}

$error = '';

// capturamos el formulario por POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    // el AuthService hace el password_verify y regenera el ID de sesión
    if ($authService->login($username, $password)) {
        header('Location: index.php');
        exit;
    } else {
        $error = 'Usuario o contraseña incorrectos.';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Acceso - DevLevel v2</title>
    <style>
        body { font-family: system-ui, sans-serif; background: #0f172a; color: #333; display: flex; justify-content: center; padding-top: 10vh; }
        .login-card { background: white; padding: 2.5rem; border-radius: 10px; box-shadow: 0 10px 25px rgba(0,0,0,0.5); width: 100%; max-width: 380px; }
        .login-card h2 { text-align: center; margin-top: 0; color: #0f172a; }
        .form-group { margin-bottom: 1.5rem; }
        label { display: block; margin-bottom: 0.5rem; font-weight: bold; font-size: 0.9rem; }
        input { width: 100%; padding: 0.75rem; border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box; }
        button { width: 100%; padding: 0.75rem; background: #0284c7; color: white; border: none; border-radius: 6px; font-weight: bold; cursor: pointer; transition: background 0.3s; }
        button:hover { background: #0369a1; }
        .error { background: #fee2e2; color: #b91c1c; padding: 0.75rem; border-radius: 6px; margin-bottom: 1.5rem; font-size: 0.9rem; text-align: center; }
    </style>
</head>
<body>
    <div class="login-card">
        <h2>DevLevel v2</h2>
        
        <!-- imprimimos el error escapando la salida por seguridad -->
        <?php if ($error): ?>
            <div class="error"><?= h($error) ?></div>
        <?php endif; ?>
        
        <form method="POST" action="login.php">
            <div class="form-group">
                <label for="username">Nombre de usuario</label>
                <input type="text" id="username" name="username" required>
            </div>
            
            <div class="form-group">
                <label for="password">Contraseña</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <button type="submit">Iniciar Sesión</button>
        </form>
    </div>
</body>
</html>