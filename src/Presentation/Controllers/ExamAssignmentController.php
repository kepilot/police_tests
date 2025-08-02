<?php

declare(strict_types=1);

namespace App\Presentation\Controllers;

use App\Infrastructure\Container\Container;
use App\Application\Commands\AssignExamCommand;
use App\Domain\ValueObjects\UserId;
use App\Domain\ValueObjects\ExamId;
use App\Domain\ValueObjects\ExamAssignmentId;

final class ExamAssignmentController
{
    public function __construct(
        private readonly Container $container
    ) {
    }

    public function assignExamToUser(string $userId, string $examId, string $assignedBy, ?string $dueDate = null): array
    {
        try {
            $command = new AssignExamCommand(
                UserId::fromString($userId),
                ExamId::fromString($examId),
                UserId::fromString($assignedBy),
                $dueDate ? new \DateTimeImmutable($dueDate) : null
            );

            $handler = $this->container->get(\App\Application\Commands\AssignExamHandler::class);
            $assignment = $handler($command);

            return [
                'success' => true,
                'message' => 'Exam assigned successfully',
                'data' => [
                    'id' => $assignment->getId()->toString(),
                    'user_id' => $assignment->getUserId()->toString(),
                    'exam_id' => $assignment->getExamId()->toString(),
                    'assigned_by' => $assignment->getAssignedBy()->toString(),
                    'assigned_at' => $assignment->getAssignedAt()->format('Y-m-d H:i:s'),
                    'due_date' => $assignment->getDueDate()?->format('Y-m-d H:i:s'),
                    'is_completed' => $assignment->isCompleted()
                ]
            ];
        } catch (\InvalidArgumentException $e) {
            return [
                'success' => false,
                'message' => 'Invalid data: ' . $e->getMessage()
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error assigning exam: ' . $e->getMessage()
            ];
        }
    }

    public function getUserAssignments(string $userId): array
    {
        try {
            $repository = $this->container->get(\App\Domain\Repositories\ExamAssignmentRepositoryInterface::class);
            $assignments = $repository->findByUserId(UserId::fromString($userId));

            $assignmentsData = [];
            foreach ($assignments as $assignment) {
                $assignmentsData[] = [
                    'id' => $assignment->getId()->toString(),
                    'exam_id' => $assignment->getExamId()->toString(),
                    'assigned_by' => $assignment->getAssignedBy()->toString(),
                    'assigned_at' => $assignment->getAssignedAt()->format('Y-m-d H:i:s'),
                    'due_date' => $assignment->getDueDate()?->format('Y-m-d H:i:s'),
                    'is_completed' => $assignment->isCompleted(),
                    'completed_at' => $assignment->getCompletedAt()?->format('Y-m-d H:i:s'),
                    'is_overdue' => $assignment->isOverdue()
                ];
            }

            return [
                'success' => true,
                'data' => $assignmentsData
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error fetching user assignments: ' . $e->getMessage()
            ];
        }
    }

    public function getPendingAssignments(string $userId): array
    {
        try {
            $repository = $this->container->get(\App\Domain\Repositories\ExamAssignmentRepositoryInterface::class);
            $assignments = $repository->findPendingByUserId(UserId::fromString($userId));

            $assignmentsData = [];
            foreach ($assignments as $assignment) {
                $assignmentsData[] = [
                    'id' => $assignment->getId()->toString(),
                    'exam_id' => $assignment->getExamId()->toString(),
                    'assigned_by' => $assignment->getAssignedBy()->toString(),
                    'assigned_at' => $assignment->getAssignedAt()->format('Y-m-d H:i:s'),
                    'due_date' => $assignment->getDueDate()?->format('Y-m-d H:i:s'),
                    'is_overdue' => $assignment->isOverdue()
                ];
            }

            return [
                'success' => true,
                'data' => $assignmentsData
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error fetching pending assignments: ' . $e->getMessage()
            ];
        }
    }

    public function getOverdueAssignments(string $userId): array
    {
        try {
            $repository = $this->container->get(\App\Domain\Repositories\ExamAssignmentRepositoryInterface::class);
            $assignments = $repository->findOverdueByUserId(UserId::fromString($userId));

            $assignmentsData = [];
            foreach ($assignments as $assignment) {
                $assignmentsData[] = [
                    'id' => $assignment->getId()->toString(),
                    'exam_id' => $assignment->getExamId()->toString(),
                    'assigned_by' => $assignment->getAssignedBy()->toString(),
                    'assigned_at' => $assignment->getAssignedAt()->format('Y-m-d H:i:s'),
                    'due_date' => $assignment->getDueDate()?->format('Y-m-d H:i:s')
                ];
            }

            return [
                'success' => true,
                'data' => $assignmentsData
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error fetching overdue assignments: ' . $e->getMessage()
            ];
        }
    }

    public function markAssignmentAsCompleted(string $assignmentId): array
    {
        try {
            $repository = $this->container->get(\App\Domain\Repositories\ExamAssignmentRepositoryInterface::class);
            $assignment = $repository->findById(ExamAssignmentId::fromString($assignmentId));

            if (!$assignment) {
                return [
                    'success' => false,
                    'message' => 'Assignment not found'
                ];
            }

            $assignment->markAsCompleted();
            $repository->save($assignment);

            return [
                'success' => true,
                'message' => 'Assignment marked as completed'
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error marking assignment as completed: ' . $e->getMessage()
            ];
        }
    }

    public function getAllAssignments(): array
    {
        try {
            $repository = $this->container->get(\App\Domain\Repositories\ExamAssignmentRepositoryInterface::class);
            $userRepository = $this->container->get(\App\Domain\Repositories\UserRepositoryInterface::class);
            $examRepository = $this->container->get(\App\Domain\Repositories\ExamRepositoryInterface::class);
            
            $assignments = $repository->findAll();

            $assignmentsData = [];
            foreach ($assignments as $assignment) {
                try {
                    // Get user information
                    $userIdString = $assignment->getUserId()->toString();
                    $user = $userRepository->findById(\Ramsey\Uuid\Uuid::fromString($userIdString));
                    $userName = $user ? $user->getName() : 'Unknown User (ID: ' . $userIdString . ')';
                    
                    // Get exam information
                    $exam = $examRepository->findById($assignment->getExamId());
                    $examTitle = $exam ? $exam->getTitle()->value() : 'Unknown Exam';
                    
                    // Get assigned by user information
                    $assignedByString = $assignment->getAssignedBy()->toString();
                    $assignedByUser = $userRepository->findById(\Ramsey\Uuid\Uuid::fromString($assignedByString));
                    $assignedByName = $assignedByUser ? $assignedByUser->getName() : 'Unknown Admin (ID: ' . $assignedByString . ')';
                    
                    $assignmentsData[] = [
                        'id' => $assignment->getId()->toString(),
                        'user_id' => $assignment->getUserId()->toString(),
                        'user_name' => $userName,
                        'exam_id' => $assignment->getExamId()->toString(),
                        'exam_title' => $examTitle,
                        'assigned_by' => $assignment->getAssignedBy()->toString(),
                        'assigned_by_name' => $assignedByName,
                        'assigned_at' => $assignment->getAssignedAt()->format('Y-m-d H:i:s'),
                        'due_date' => $assignment->getDueDate()?->format('Y-m-d H:i:s'),
                        'is_completed' => $assignment->isCompleted(),
                        'completed_at' => $assignment->getCompletedAt()?->format('Y-m-d H:i:s'),
                        'is_overdue' => $assignment->isOverdue()
                    ];
                } catch (\Exception $e) {
                    // If there's an error with this assignment, skip it and continue with others
                    $assignmentsData[] = [
                        'id' => $assignment->getId()->toString(),
                        'user_id' => $assignment->getUserId()->toString(),
                        'user_name' => 'Error loading user: ' . $e->getMessage(),
                        'exam_id' => $assignment->getExamId()->toString(),
                        'exam_title' => 'Error loading exam',
                        'assigned_by' => $assignment->getAssignedBy()->toString(),
                        'assigned_by_name' => 'Error loading admin',
                        'assigned_at' => $assignment->getAssignedAt()->format('Y-m-d H:i:s'),
                        'due_date' => $assignment->getDueDate()?->format('Y-m-d H:i:s'),
                        'is_completed' => $assignment->isCompleted(),
                        'completed_at' => $assignment->getCompletedAt()?->format('Y-m-d H:i:s'),
                        'is_overdue' => $assignment->isOverdue()
                    ];
                }
            }

            return [
                'success' => true,
                'data' => $assignmentsData
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error fetching all assignments: ' . $e->getMessage()
            ];
        }
    }

    public function getAssignmentStatistics(string $userId): array
    {
        try {
            $repository = $this->container->get(\App\Domain\Repositories\ExamAssignmentRepositoryInterface::class);
            
            $totalAssignments = $repository->countByUserId(UserId::fromString($userId));
            $completedAssignments = $repository->countCompletedByUserId(UserId::fromString($userId));
            $overdueAssignments = $repository->countOverdueByUserId(UserId::fromString($userId));
            $pendingAssignments = $totalAssignments - $completedAssignments;

            return [
                'success' => true,
                'data' => [
                    'total' => $totalAssignments,
                    'completed' => $completedAssignments,
                    'pending' => $pendingAssignments,
                    'overdue' => $overdueAssignments,
                    'completion_rate' => $totalAssignments > 0 ? round(($completedAssignments / $totalAssignments) * 100, 2) : 0
                ]
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error fetching assignment statistics: ' . $e->getMessage()
            ];
        }
    }
} 