<?php
declare(strict_types=1);

class SkillRepository {
    public function __construct(private PDO $pdo) {}

    public function obtenerTodas(): array {
        // usamos un query directo ya que es seguro por que no intervienen datos del usuario
        $stmt = $this->pdo->query('SELECT id, nombre, categoria FROM skills ORDER BY categoria, nombre');
        return $stmt->fetchAll();
    }
}