<?php

namespace App\Controller;

use App\Entity\Task;
use App\Form\TaskType;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Class TaskController
 *
 * Handles all actions related to tasks management: listing, creation, edition,
 * status toggling, and secure deletion.
 *
 * @package App\Controller
 */
class TaskController extends AbstractController
{
    /** @var ManagerRegistry */
    private $registry;

    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }
    /**
     * List all tasks.
     *
     * @Route("/tasks", name="task_list")
     *
     * @return Response
     */
    public function listAction()
    {
        return $this->render('task/list.html.twig', [
            'tasks' => $this->registry->getRepository(Task::class)->findAll()
        ]);
    }

    /**
     * Create a new task.
     *
     * @Route("/tasks/create", name="task_create")
     *
     * @param Request $request
     * @return RedirectResponse|Response
     */
    public function createAction(Request $request)
    {
        $task = new Task();
        $form = $this->createForm(TaskType::class, $task);

        $form->handleRequest($request);

        if ($form->isSubmitted() === true && $form->isValid() === true) {
            $em = $this->registry->getManager();

            // Automandatory binding: Link the logged-in user to the created task
            $task->setUser($this->getUser());

            $em->persist($task);
            $em->flush();

            $this->addFlash('success', 'La tâche a bien été ajoutée.');

            return $this->redirectToRoute('task_list');
        }

        return $this->render('task/create.html.twig', [
            'form' => $form->createView()
        ]);
    }

    /**
     * Edit an existing task.
     *
     * @Route("/tasks/{id}/edit", name="task_edit")
     *
     * @param Task $task
     * @param Request $request
     * @return RedirectResponse|Response
     */
    public function editAction(Request $request, Task $task)
    {
        // Save the original user before handling the request
        $originalUser = $task->getUser();

        $form = $this->createForm(TaskType::class, $task);
        $form->handleRequest($request);

        if ($form->isSubmitted() === true && $form->isValid() === true) {
            // Enforce immutability: bypass any falsified request data by restoring the original user
            $task->setUser($originalUser);

            $this->registry->getManager()->flush();

            $this->addFlash('success', 'La tâche a bien été modifiée.');

            return $this->redirectToRoute('task_list');
        }

        return $this->render('task/edit.html.twig', [
            'form' => $form->createView(),
            'task' => $task,
        ]);
    }

    /**
     * Toggle the completion status of a task.
     *
     * @Route("/tasks/{id}/toggle", name="task_toggle")
     *
     * @param Task $task
     * @return RedirectResponse
     */
    public function toggleTaskAction(Task $task)
    {
        // Avoid negative operations (!) for Codacy check conformity
        $task->toggle($task->isDone() === false);
        $this->registry->getManager()->flush();

        $this->addFlash('success', sprintf('La tâche %s a bien été marquée comme faite.', $task->getTitle()));

        return $this->redirectToRoute('task_list');
    }

    /**
     * Delete a task securely.
     *
     * @Route("/tasks/{id}/delete", name="task_delete")
     *
     * @param Task $task
     * @return RedirectResponse
     */
    public function deleteTaskAction(Task $task)
    {
        // Apply security voter check: Only the author (or admin for anonymous tasks) can delete it
        $this->denyAccessUnlessGranted('delete', $task);

        $em = $this->registry->getManager();
        $em->remove($task);
        $em->flush();

        $this->addFlash('success', 'La tâche a bien été supprimée.');

        return $this->redirectToRoute('task_list');
    }
}
