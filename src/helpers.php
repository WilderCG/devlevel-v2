<?php
declare(strict_types=1);

// función para evitar ataques XSS 
function h(string|null $texto): string {
    if ($texto === null) return '';
    return htmlspecialchars($texto, ENT_QUOTES, 'UTF-8');
}

// arranca la sesión solo si no estaba ya iniciada, evitando errores de PHP
function iniciarSesionSegura(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}