<?php

// src/Controller/TaskController.php

namespace App\Controller;

use App\Entity\Task;
use App\Repository\TaskRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class TaskController extends AbstractController
{
    #[Route('/tasks', methods: ['POST'])]
    public function createTask(Request $request, EntityManagerInterface $em, ValidatorInterface $validator): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        if (!isset($data['title']) || !isset($data['description']) || !isset($data['status'])) {
            return new JsonResponse(['error' => 'Missing required fields'], 400);
        }

        $task = new Task();
        $task->setTitle($data['title']);
        $task->setDescription($data['description']);
        $task->setStatus($data['status']);
        $task->setCreatedAt(new \DateTime());
        $task->setUpdatedAt(new \DateTime());

        $errors = $validator->validate($task);
        if (count($errors) > 0) {
            return new JsonResponse(['errors' => (string) $errors], 400);
        }

        $em->persist($task);
        $em->flush();

        return new JsonResponse(['id' => $task->getId()], 201);
    }

    #[Route('/tasks/{id}', methods: ['PUT'])]
    public function updateTask(int $id, Request $request, EntityManagerInterface $em, TaskRepository $taskRepo): JsonResponse
    {
        $task = $taskRepo->find($id);
        
        if (!$task) {
            return new JsonResponse(['error' => 'Task not found'], 404);
        }

        $data = json_decode($request->getContent(), true);

        $task->setTitle($data['title'] ?? $task->getTitle());
        $task->setDescription($data['description'] ?? $task->getDescription());
        $task->setStatus($data['status'] ?? $task->getStatus());
        $task->setUpdatedAt(new \DateTime());

        $errors = $validator->validate($task);
        if (count($errors) > 0) {
            return new JsonResponse(['errors' => (string) $errors], 400);
        }
    
        $em->flush();
    
        return new JsonResponse(['message' => 'Task updated successfully']);
    }
    
    #[Route('/tasks/{id}', methods: ['DELETE'])]
    public function deleteTask(int $id, TaskRepository $taskRepo, EntityManagerInterface $em): JsonResponse
    {
        $task = $taskRepo->find($id);
    
        if (!$task) {
            return new JsonResponse(['error' => 'Task not found'], 404);
        }
    
        $em->remove($task);
        $em->flush();
    
        return new JsonResponse(['message' => 'Task deleted successfully']);
    }

    use Knp\Component\Pager\PaginatorInterface;

    #[Route('/tasks', methods: ['GET'])]
    public function listTasks(Request $request, TaskRepository $taskRepo, PaginatorInterface $paginator): JsonResponse
    {
        $pagination = $paginator->paginate(
            $query, 
            $request->query->getInt('page', 1), 
            10 
        );

        return new JsonResponse($pagination->getItems());
    }

        
}
