<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/Database.php';
require_once __DIR__ . '/../src/Repositories/UserRepository.php';
require_once __DIR__ . '/../src/Repositories/TaskRepository.php';
require_once __DIR__ . '/../src/Services/AuthService.php';
require_once __DIR__ . '/../src/Services/LevelService.php';
require_once __DIR__ . '/../src/helpers.php';

iniciarSesionSegura();

// instanciamos dependencias
$pdo = Database::getConnection();
$userRepo = new UserRepository($pdo);
$authService = new AuthService($userRepo);

// si no esta logueado, lo echamos al login
if (!$authService->check()) {
    header('Location: login.php');
    exit;
}

$taskRepo = new TaskRepository($pdo);
$levelService = new LevelService($pdo);

$usuarioId = $authService->getUsuarioId();
$nivelUsuario = $authService->getNivel();
$mensajeExito = '';
$mensajeError = '';

// procesar la acción de completar tarea mediante POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['completar_tarea_id'])) {
    $tareaId = (int)$_POST['completar_tarea_id'];
    $puntos = (int)$_POST['puntos_tarea'];
    
    // Ejecutamos la transacción ACID
    if ($levelService->completarTarea($usuarioId, $tareaId, $puntos, $nivelUsuario)) {
        // CE 6.f y PRG: Recargamos los datos del usuario en memoria para que vea sus nuevos puntos inmediatamente
        $usuarioActualizado = $userRepo->obtenerPorUsername($authService->getRol()); 
        
        $mensajeExito = "¡Misión completada! Has ganado $puntos XP.";
    } else {
        $mensajeError = "No se pudo procesar la recompensa. Es posible que ya la hayas completado.";
    }
}

// 3. CE 6.d: Lógica de Paginación en servidor (LIMIT / OFFSET)
$porPagina = 5;
// Capturamos la página actual de la URL de forma segura (por defecto la 1)
$paginaActual = max(1, (int)($_GET['page'] ?? 1));
$offset = ($paginaActual - 1) * $porPagina;

// Contamos el total para calcular las páginas
$totalTareas = $taskRepo->contarDisponibles($usuarioId, $nivelUsuario);
$totalPaginas = max(1, (int)ceil($totalTareas / $porPagina));

// Obtenemos solo las 5 tareas de esta página
$tareas = $taskRepo->obtenerDisponibles($usuarioId, $nivelUsuario, $porPagina, $offset);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Catálogo de Misiones - DevLevel v2</title>
    <style>
        body { font-family: system-ui, sans-serif; background: #f8fafc; color: #1e293b; padding: 2rem; max-width: 1000px; margin: 0 auto; }
        .header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #e2e8f0; padding-bottom: 1rem; margin-bottom: 2rem; }
        .tarea-card { background: white; border: 1px solid #cbd5e1; border-radius: 8px; padding: 1.5rem; margin-bottom: 1rem; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
        .tarea-info h3 { margin: 0 0 0.5rem 0; color: #0369a1; }
        .badge { background: #e0f2fe; color: #0284c7; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.8rem; font-weight: bold; margin-right: 0.5rem; }
        .btn-completar { background: #10b981; color: white; border: none; padding: 0.75rem 1.5rem; border-radius: 6px; font-weight: bold; cursor: pointer; transition: background 0.3s; }
        .btn-completar:hover { background: #059669; }
        .btn-logout { background: #ef4444; color: white; text-decoration: none; padding: 0.5rem 1rem; border-radius: 6px; font-weight: bold; }
        .paginacion { display: flex; justify-content: center; gap: 1rem; margin-top: 2rem; align-items: center; }
        .paginacion a { padding: 0.5rem 1rem; background: white; border: 1px solid #cbd5e1; border-radius: 4px; text-decoration: none; color: #333; }
        .paginacion a:hover { background: #f1f5f9; }
        .alerta-exito { background: #dcfce7; color: #166534; padding: 1rem; border-radius: 6px; margin-bottom: 1.5rem; border-left: 4px solid #10b981; }
        .alerta-error { background: #fee2e2; color: #b91c1c; padding: 1rem; border-radius: 6px; margin-bottom: 1.5rem; border-left: 4px solid #ef4444; }
    </style>
</head>
<body>

    <header class="header">
        <div>
            <h1>DevLevel v2</h1>
            <p>
                <!-- CE 6.d: Escapado de salida (h) -->
                Nivel Actual: <strong><?= h((string)$nivelUsuario) ?></strong> | 
                Rol: <strong><?= h($authService->getRol()) ?></strong>
            </p>
        </div>
        <a href="logout.php" class="btn-logout">Cerrar Sesión</a>
    </header>

    <?php if ($mensajeExito): ?>
        <div class="alerta-exito"><?= h($mensajeExito) ?></div>
    <?php endif; ?>
    
    <?php if ($mensajeError): ?>
        <div class="alerta-error"><?= h($mensajeError) ?></div>
    <?php endif; ?>

    <h2>Misiones Disponibles (<?= h((string)$totalTareas) ?>)</h2>

    <?php if (empty($tareas)): ?>
        <p>No hay misiones disponibles para tu nivel en este momento.</p>
    <?php else: ?>
        <?php foreach ($tareas as $tarea): ?>
            <div class="tarea-card">
                <div class="tarea-info">
                    <!-- CE 6.d: Renderizado HTML seguro para evitar XSS -->
                    <h3><?= h($tarea['titulo']) ?></h3>
                    <p><?= h($tarea['descripcion']) ?></p>
                    <div>
                        <span class="badge">Nivel <?= h((string)$tarea['nivel_minimo']) ?></span>
                        <span class="badge">Dificultad: <?= h(ucfirst($tarea['dificultad'])) ?></span>
                        <span class="badge">+<?= h((string)$tarea['puntos']) ?> XP</span>
                    </div>
                </div>
                
                <!-- Formulario POST para completar la tarea de forma segura -->
                <form method="POST" action="index.php" style="margin:0;">
                    <input type="hidden" name="completar_tarea_id" value="<?= h((string)$tarea['id']) ?>">
                    <input type="hidden" name="puntos_tarea" value="<?= h((string)$tarea['puntos']) ?>">
                    <button type="submit" class="btn-completar">Completar Misión</button>
                </form>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <!-- CE 6.d: Controles de navegación de paginación -->
    <div class="paginacion">
        <?php if ($paginaActual > 1): ?>
            <a href="index.php?page=<?= h((string)($paginaActual - 1)) ?>">← Anterior</a>
        <?php endif; ?>
        
        <span>Página <?= h((string)$paginaActual) ?> de <?= h((string)$totalPaginas) ?></span>
        
        <?php if ($paginaActual < $totalPaginas): ?>
            <a href="index.php?page=<?= h((string)($paginaActual + 1)) ?>">Siguiente →</a>
        <?php endif; ?>
    </div>

</body>
</html>