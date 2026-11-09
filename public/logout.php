<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/Database.php';
require_once __DIR__ . '/../src/Repositories/UserRepository.php';
require_once __DIR__ . '/../src/Services/AuthService.php';
require_once __DIR__ . '/../src/helpers.php';

iniciarSesionSegura();

// instanciamos la conexión y los servicios
$pdo = Database::getConnection();
$userRepo = new UserRepository($pdo);
$authService = new AuthService($userRepo);

// destruimos la sesión de forma segura
$authService->logout();

// redirigimos al usuario y bloqueamos la ejecución posterior
header('Location: login.php');
exit;