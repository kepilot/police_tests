<?php

declare(strict_types=1);

namespace App\Infrastructure\Services;

use App\Domain\Entities\User;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Ramsey\Uuid\UuidInterface;

final class JwtService
{
    private string $secret;
    private int $expirationTime;

    public function __construct()
    {
        $this->secret = $_ENV['JWT_SECRET'] ?? 'default-secret-change-in-production';
        $this->expirationTime = 3600; // 1 hour
    }

    public function generateToken(User $user): string
    {
        $payload = [
            'iss' => 'ddd-app', // Issuer
            'aud' => 'ddd-app', // Audience
            'iat' => time(), // Issued at
            'exp' => time() + $this->expirationTime, // Expiration time
            'sub' => $user->getId()->toString(), // Subject (user ID)
            'email' => $user->getEmail()->value(),
            'name' => $user->getName(),
        ];

        return JWT::encode($payload, $this->secret, 'HS256');
    }

    public function validateToken(string $token): ?array
    {
        try {
            $decoded = JWT::decode($token, new Key($this->secret, 'HS256'));
            return (array) $decoded;
        } catch (\Exception $e) {
            return null;
        }
    }

    public function getUserIdFromToken(string $token): ?UuidInterface
    {
        $payload = $this->validateToken($token);
        
        if (!$payload || !isset($payload['sub'])) {
            return null;
        }

        try {
            return \Ramsey\Uuid\Uuid::fromString($payload['sub']);
        } catch (\Exception $e) {
            return null;
        }
    }

    public function isTokenExpired(string $token): bool
    {
        $payload = $this->validateToken($token);
        
        if (!$payload || !isset($payload['exp'])) {
            return true;
        }

        return $payload['exp'] < time();
    }
} 