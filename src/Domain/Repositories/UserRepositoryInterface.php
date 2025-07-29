<?php

declare(strict_types=1);

namespace App\Domain\Repositories;

use App\Domain\Entities\User;
use Ramsey\Uuid\UuidInterface;

interface UserRepositoryInterface
{
    public function save(User $user): void;
    
    public function findById(UuidInterface $id): ?User;
    
    public function findByEmail(string $email): ?User;
    
    public function delete(User $user): void;
} 