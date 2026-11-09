<?php
declare(strict_types=1);

class LevelService {
    public function __construct(private PDO $pdo) {}

    public function completarTarea(int $usuarioId, int $tareaId, int $puntosObtenidos, int $nivelActual): bool {
        try {
            // iniciamos la transacción
            $this->pdo->beginTransaction();

            // registramos que el usuario ha hecho la tarea 
            $stmtHistorial = $this->pdo->prepare('INSERT INTO progreso_tareas (usuario_id, tarea_id, puntos_obtenidos) VALUES (:uid, :tid, :puntos)');
            $stmtHistorial->execute([
                'uid' => $usuarioId,
                'tid' => $tareaId,
                'puntos' => $puntosObtenidos
            ]);

            // calculamos la subida de puntos basica 
            $nuevoNivel = $nivelActual; 
            $nuevoRol = 'DEVELOPER_JUNIOR';

            // actualizamos el perfil del usuario
            $stmtUser = $this->pdo->prepare('UPDATE usuarios SET puntos = puntos + :puntos, nivel = :nivel WHERE id = :id');
            $stmtUser->execute([
                'puntos' => $puntosObtenidos,
                'nivel' => $nuevoNivel,
                'id' => $usuarioId
            ]);

            // como todo ha ido bien guardamos los cambios definitivamente
            $this->pdo->commit();
            return true;

        } catch (Exception $e) {
            // si el usuario intentó enviar dos veces la misma tarea o hubo otro error, revertimos
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            error_log("Error transaccional al completar tarea: " . $e->getMessage());
            return false;
        }
    }
}