<?php
declare(strict_types=1);

class TaskRepository {
    public function __construct(private PDO $pdo) {}

    // filtra las tareas por el nivel del usuario y oculta las que ya hizo
    public function obtenerDisponibles(int $usuarioId, int $nivelUsuario, int $limite, int $offset): array {
        $sql = '
            SELECT t.* 
            FROM tareas t
            LEFT JOIN progreso_tareas pt ON t.id = pt.tarea_id AND pt.usuario_id = :uid
            WHERE t.activa = 1 
              AND t.nivel_minimo <= :nivel 
              AND pt.id IS NULL
            ORDER BY t.created_at DESC
            LIMIT :limite OFFSET :offset
        ';
        
        $stmt = $this->pdo->prepare($sql);
        
        // para la paginación, PDO exige forzar el tipo a PARAM_INT
        $stmt->bindValue(':uid', $usuarioId, PDO::PARAM_INT);
        $stmt->bindValue(':nivel', $nivelUsuario, PDO::PARAM_INT);
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // cuenta el total de tareas reales para que luego sepamos cuántas páginas dibujar
    public function contarDisponibles(int $usuarioId, int $nivelUsuario): int {
        $sql = '
            SELECT COUNT(*) 
            FROM tareas t
            LEFT JOIN progreso_tareas pt ON t.id = pt.tarea_id AND pt.usuario_id = :uid
            WHERE t.activa = 1 AND t.nivel_minimo <= :nivel AND pt.id IS NULL
        ';
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'uid' => $usuarioId, 
            'nivel' => $nivelUsuario
        ]);
        
        return (int) $stmt->fetchColumn();
    }
}