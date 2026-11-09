<?php
declare(strict_types=1);

class AuthService {
    public function __construct(private UserRepository $userRepo) {}

    public function login(string $username, string $password): bool {
        $user = $this->userRepo->obtenerPorUsername($username);
        
        // verificamos si existe el usuario y si la contraseña coincide con el hash de la BD
        if ($user && password_verify($password, $user['password_hash'])) {
            // regeneramos el ID de la sesión tras un login exitoso
            session_regenerate_id(true); 
            
            // guardo los datos criticos en memoria de sesión
            $_SESSION['usuario_id'] = $user['id'];
            $_SESSION['rol'] = $user['rol'];
            $_SESSION['nivel'] = $user['nivel'];
            
            return true;
        }
        return false;
    }
    
    public function check(): bool {
        return isset($_SESSION['usuario_id']);
    }
    
    public function getUsuarioId(): int {
        return $_SESSION['usuario_id'] ?? 0;
    }

    public function getNivel(): int {
        return $_SESSION['nivel'] ?? 1;
    }

    public function getRol(): string {
        return $_SESSION['rol'] ?? '';
    }

    public function logout(): void {
        session_unset();
        session_destroy();
    }
}