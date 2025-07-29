<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use PDO;
use PDOException;

final class DatabaseConnection
{
    private ?PDO $pdo = null;
    private string $host;
    private int $port;
    private string $database;
    private string $username;
    private string $password;

    public function __construct()
    {
        $this->host = $_ENV['DB_HOST'] ?? 'db';
        $this->port = (int) ($_ENV['DB_PORT'] ?? 3306);
        $this->database = $_ENV['DB_NAME'] ?? 'ddd_db';
        $this->username = $_ENV['DB_USER'] ?? 'ddd_user';
        $this->password = $_ENV['DB_PASSWORD'] ?? 'secret';
    }

    public function getConnection(): PDO
    {
        if ($this->pdo === null) {
            $this->pdo = $this->createConnection();
        }

        return $this->pdo;
    }

    private function createConnection(): PDO
    {
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
            $this->host,
            $this->port,
            $this->database
        );

        try {
            $pdo = new PDO($dsn, $this->username, $this->password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);

            return $pdo;
        } catch (PDOException $e) {
            throw new \RuntimeException(
                'Failed to connect to database: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }
} 