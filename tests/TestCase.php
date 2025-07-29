<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;
use App\Infrastructure\Container\Container;
use App\Infrastructure\Persistence\DatabaseConnection;
use PDO;

abstract class TestCase extends BaseTestCase
{
    protected Container $container;
    protected PDO $pdo;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Load test environment variables
        $this->loadTestEnvironment();
        
        // Create container
        $this->container = new Container();
        
        // Setup test database connection
        $this->setupTestDatabase();
    }

    protected function tearDown(): void
    {
        // Clean up test database
        $this->cleanupTestDatabase();
        
        parent::tearDown();
    }

    private function loadTestEnvironment(): void
    {
        // Set test environment variables
        $_ENV['APP_ENV'] = 'testing';
        $_ENV['DB_HOST'] = 'localhost';
        $_ENV['DB_PORT'] = '3306';
        $_ENV['DB_NAME'] = 'ddd_test';
        $_ENV['DB_USER'] = 'ddd_user';
        $_ENV['DB_PASSWORD'] = 'secret';
        $_ENV['JWT_SECRET'] = 'test-secret-key';
        $_ENV['PASSWORD_HASH_COST'] = '4';
    }

    private function setupTestDatabase(): void
    {
        try {
            // Connect to test database
            $this->pdo = new PDO(
                "mysql:host={$_ENV['DB_HOST']};port={$_ENV['DB_PORT']};dbname={$_ENV['DB_NAME']}",
                $_ENV['DB_USER'],
                $_ENV['DB_PASSWORD'],
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
                ]
            );

            // Create test tables
            $this->createTestTables();
        } catch (\PDOException $e) {
            $this->markTestSkipped('Test database not available: ' . $e->getMessage());
        }
    }

    private function createTestTables(): void
    {
        $sql = "
            CREATE TABLE IF NOT EXISTS users (
                id CHAR(36) PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                email VARCHAR(255) UNIQUE NOT NULL,
                password_hash VARCHAR(255) NOT NULL,
                is_active BOOLEAN DEFAULT TRUE NOT NULL,
                last_login_at DATETIME NULL,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                INDEX idx_created_at (created_at),
                INDEX idx_is_active (is_active)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";

        $this->pdo->exec($sql);
    }

    private function cleanupTestDatabase(): void
    {
        if (isset($this->pdo)) {
            // Clear all test data
            $this->pdo->exec('DELETE FROM users');
        }
    }

    protected function getContainer(): Container
    {
        return $this->container;
    }

    protected function getPdo(): PDO
    {
        return $this->pdo;
    }

    protected function assertDatabaseHas(string $table, array $data): void
    {
        $columns = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));
        
        $sql = "SELECT COUNT(*) FROM {$table} WHERE {$columns} = {$placeholders}";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($data);
        
        $count = $stmt->fetchColumn();
        $this->assertGreaterThan(0, $count, "Record not found in {$table}");
    }

    protected function assertDatabaseMissing(string $table, array $data): void
    {
        $columns = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));
        
        $sql = "SELECT COUNT(*) FROM {$table} WHERE {$columns} = {$placeholders}";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($data);
        
        $count = $stmt->fetchColumn();
        $this->assertEquals(0, $count, "Record found in {$table} when it should not exist");
    }

    protected function assertDatabaseCount(string $table, int $expectedCount): void
    {
        $sql = "SELECT COUNT(*) FROM {$table}";
        $count = $this->pdo->query($sql)->fetchColumn();
        $this->assertEquals($expectedCount, $count, "Expected {$expectedCount} records in {$table}, found {$count}");
    }
} 