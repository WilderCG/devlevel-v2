<?php
declare(strict_types=1);

class UserRepository {
    // inyectamos la conexión PDO directamente en el constructor
    public function __construct(private PDO $pdo) {}

    // metodo seguro para validar el inicio de sesión
    public function obtenerPorUsername(string $username): array|false {
        $sql = 'SELECT * FROM usuarios WHERE username = :username';
        $stmt = $this->pdo->prepare($sql);
        
        // ejecutamos aislando el parametro externo
        $stmt->execute(['username' => $username]);
        
        return $stmt->fetch();
    }
}