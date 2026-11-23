<?php
declare(strict_types=1);

final class Database {
    // propiedad estática para guardar la única instancia de la conexión (Singleton)
    private static ?PDO $connection = null;

    // constructor privado: bloquea que otros archivos hagan un "new Database()"
    private function __construct() {}

    public static function getConnection(): PDO {
        // si no hay conexión previa, la creamos. Si ya existe, devolvemos la misma.
        if (self::$connection === null) {
            try {
                // cargamos las credenciales del archivo de configuración de forma segura
                $config = require __DIR__ . '/../config/database.php';
                $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset=utf8mb4";
                
                self::$connection = new PDO($dsn, $config['user'], $config['password'], [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,        // captura segura de excepciones
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,   // fetch predeterminado asociativo
                    PDO::ATTR_EMULATE_PREPARES => false                 // seguridad extra contra SQL Injection
                ]);
            } catch (PDOException $e) {
                // registro del error en el log interno sin exponer credenciales
                error_log("Error crítico de BD: " . $e->getMessage());
                die("Error interno del servidor. Inténtelo más tarde.");
            }
        }
        return self::$connection;
    }
}