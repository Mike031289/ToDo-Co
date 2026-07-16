<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Task;
use AppBundle\Form\TaskType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class TaskController
 *
 * Handles all actions related to tasks management: listing, creation, edition,
 * status toggling, and secure deletion.
 *
 * @package AppBundle\Controller
 */
class TaskController extends Controller
{
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
            'tasks' => $this->getDoctrine()->getRepository('AppBundle:Task')->findAll()
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

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();

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
    public function editAction(Task $task, Request $request)
    {
        // Save the original user before handling the request
        $originalUser = $task->getUser();

        $form = $this->createForm(TaskType::class, $task);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Enforce immutability: bypass any falsified request data by restoring the original user
            $task->setUser($originalUser);

            $this->getDoctrine()->getManager()->flush();

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
        $this->getDoctrine()->getManager()->flush();

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

        $em = $this->getDoctrine()->getManager();
        $em->remove($task);
        $em->flush();

        $this->addFlash('success', 'La tâche a bien été supprimée.');

        return $this->redirectToRoute('task_list');
    }
}
