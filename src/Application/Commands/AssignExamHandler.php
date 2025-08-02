<?php

declare(strict_types=1);

namespace App\Application\Commands;

use App\Domain\Entities\ExamAssignment;
use App\Domain\Repositories\ExamAssignmentRepositoryInterface;
use App\Domain\Repositories\UserRepositoryInterface;
use App\Domain\Repositories\ExamRepositoryInterface;

final class AssignExamHandler
{
    public function __construct(
        private readonly ExamAssignmentRepositoryInterface $examAssignmentRepository,
        private readonly UserRepositoryInterface $userRepository,
        private readonly ExamRepositoryInterface $examRepository
    ) {
    }

    public function __invoke(AssignExamCommand $command): ExamAssignment
    {
        // Verify that the user exists
        $user = $this->userRepository->findById(\Ramsey\Uuid\Uuid::fromString($command->getUserId()->toString()));
        if (!$user) {
            throw new \InvalidArgumentException('User not found');
        }

        // Verify that the exam exists
        $exam = $this->examRepository->findById($command->getExamId());
        if (!$exam) {
            throw new \InvalidArgumentException('Exam not found');
        }

        // Verify that the exam is active
        if (!$exam->isActive()) {
            throw new \InvalidArgumentException('Exam is not active');
        }

        // Verify that the assigner exists
        $assigner = $this->userRepository->findById(\Ramsey\Uuid\Uuid::fromString($command->getAssignedBy()->toString()));
        if (!$assigner) {
            throw new \InvalidArgumentException('Assigner not found');
        }

        // Check if assignment already exists
        $existingAssignment = $this->examAssignmentRepository->findByUserIdAndExamId(
            $command->getUserId(),
            $command->getExamId()
        );

        if ($existingAssignment) {
            throw new \InvalidArgumentException('Exam is already assigned to this user');
        }

        // Create the assignment
        $assignment = new ExamAssignment(
            $command->getUserId(),
            $command->getExamId(),
            $command->getAssignedBy(),
            $command->getDueDate()
        );

        $this->examAssignmentRepository->save($assignment);

        return $assignment;
    }
} 