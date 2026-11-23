<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/Database.php';
require_once __DIR__ . '/../src/Repositories/UserRepository.php';
require_once __DIR__ . '/../src/Repositories/SkillRepository.php';
require_once __DIR__ . '/../src/Services/AuthService.php';
require_once __DIR__ . '/../src/helpers.php';

iniciarSesionSegura();

$pdo = Database::getConnection();
$userRepo = new UserRepository($pdo);
$skillRepo = new SkillRepository($pdo);
$authService = new AuthService($userRepo);

// solo el project manager puede entrar aquí
if (!$authService->check() || $authService->getRol() !== 'PROJECT_MANAGER') {
    header('Location: index.php');
    exit;
}

$mensaje = '';

// iInserción transaccional de nueva tarea con habilidades asociadas
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = trim($_POST['titulo'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $puntos = (int)($_POST['puntos'] ?? 0);
    $nivelMinimo = (int)($_POST['nivel_minimo'] ?? 1);
    $dificultad = $_POST['dificultad'] ?? 'media';
    $skills = $_POST['skills'] ?? []; // Array de IDs de habilidades seleccionadas
    
    if ($titulo !== '' && $descripcion !== '' && $puntos > 0 && !empty($skills)) {
        try {
            // iniciamos transacción para crear la tarea y sus relaciones N:M
            $pdo->beginTransaction();
            
            // 1. insertamos la tarea principal
            $sqlTarea = 'INSERT INTO tareas (titulo, descripcion, puntos, nivel_minimo, dificultad) VALUES (:titulo, :desc, :puntos, :nivel, :dif)';
            $stmtTarea = $pdo->prepare($sqlTarea);
            $stmtTarea->execute([
                'titulo' => $titulo,
                'desc' => $descripcion,
                'puntos' => $puntos,
                'nivel' => $nivelMinimo,
                'dif' => $dificultad
            ]);
            
            // extraemos el ID generado  
            $tareaId = (int)$pdo->lastInsertId();
            
            // 2. asociamos las habilidades marcadas en la tabla intermedia (N:M)
            $sqlRelacion = 'INSERT INTO tarea_skill (tarea_id, skill_id) VALUES (:tid, :sid)';
            $stmtRelacion = $pdo->prepare($sqlRelacion);
            
            foreach ($skills as $skillId) {
                $stmtRelacion->execute([
                    'tid' => $tareaId,
                    'sid' => (int)$skillId
                ]);
            }
            
            $pdo->commit();
            $mensaje = 'Tarea creada y asignada correctamente.';
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $mensaje = 'Error al crear la tarea: ' . $e->getMessage();
        }
    } else {
        $mensaje = 'Por favor, completa todos los campos y selecciona al menos una habilidad.';
    }
}

// obtenemos datos para mostrar en la interfaz
$habilidades = $skillRepo->obtenerTodas();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel de Manager - DevLevel v2</title>
    <style>
        body { font-family: system-ui, sans-serif; background: #f8fafc; color: #1e293b; padding: 2rem; max-width: 800px; margin: 0 auto; }
        .card { background: white; border: 1px solid #cbd5e1; border-radius: 8px; padding: 2rem; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
        .form-group { margin-bottom: 1rem; }
        label { display: block; font-weight: bold; margin-bottom: 0.5rem; }
        input[type="text"], input[type="number"], select, textarea { width: 100%; padding: 0.75rem; border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box; }
        .checkbox-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 0.5rem; }
        .btn-crear { background: #0284c7; color: white; border: none; padding: 0.75rem 1.5rem; border-radius: 6px; font-weight: bold; cursor: pointer; width: 100%; margin-top: 1rem; }
        .btn-crear:hover { background: #0369a1; }
        .alerta { background: #e0f2fe; color: #0369a1; padding: 1rem; border-radius: 6px; margin-bottom: 1.5rem; }
    </style>
</head>
<body>
    <div class="card">
        <h2>Panel de Control | Creación de Tareas</h2>
        
        <?php if ($mensaje): ?>
            <div class="alerta"><?= h($mensaje) ?></div>
        <?php endif; ?>
        
        <form method="POST" action="manager.php">
            <div class="form-group">
                <label>Título de la Tarea</label>
                <input type="text" name="titulo" required>
            </div>
            
            <div class="form-group">
                <label>Descripción</label>
                <textarea name="descripcion" rows="3" required></textarea>
            </div>
            
            <div style="display: flex; gap: 1rem;">
                <div class="form-group" style="flex: 1;">
                    <label>Puntos XP</label>
                    <input type="number" name="puntos" min="10" required>
                </div>
                <div class="form-group" style="flex: 1;">
                    <label>Nivel Mínimo</label>
                    <input type="number" name="nivel_minimo" min="1" max="5" value="1" required>
                </div>
                <div class="form-group" style="flex: 1;">
                    <label>Dificultad</label>
                    <select name="dificultad">
                        <option value="facil">Fácil</option>
                        <option value="media" selected>Media</option>
                        <option value="dificil">Difícil</option>
                    </select>
                </div>
            </div>
            
            <div class="form-group">
                <label>Habilidades Requeridas</label>
                <div class="checkbox-grid">
                    <?php foreach ($habilidades as $habilidad): ?>
                        <label style="font-weight: normal; font-size: 0.9rem;">
                            <input type="checkbox" name="skills[]" value="<?= h((string)$habilidad['id']) ?>">
                            <?= h($habilidad['nombre']) ?> 
                            <span style="color:#64748b;">(<?= h($habilidad['categoria']) ?>)</span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <button type="submit" class="btn-crear">Crear Misión Oficial</button>
            <a href="logout.php" style="display:block; text-align:center; margin-top:1rem; color:#ef4444;">Cerrar sesión</a>
        </form>
    </div>
</body>
</html>