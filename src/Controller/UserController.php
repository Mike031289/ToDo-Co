<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class UserController
 *
 * Manages administrative user operations including listing, creating, and updating.
 * Access is restricted to users with ROLE_ADMIN.
 */
class UserController extends AbstractController
{
    /**
     * Display the list of all registered users.
     *
     * @Route("/users", name="user_list")
     */
    public function listAction(ManagerRegistry $doctrine): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        return $this->render('user/list.html.twig', [
            'users' => $doctrine->getRepository(User::class)->findAll()
        ]);
    }

    /**
     * Create and store a new user.
     *
     * @Route("/users/create", name="user_create")
     */
    public function createAction(Request $request, ManagerRegistry $doctrine, UserPasswordHasherInterface $passwordHasher): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $user = new User();
        $form = $this->createForm(UserType::class, $user);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $doctrine->getManager();

            // Hash the password
            $hashedPassword = $passwordHasher->hashPassword($user, $user->getPassword());
            $user->setPassword($hashedPassword);

            $em->persist($user);
            $em->flush();

            $this->addFlash('success', "The user has been successfully added.");

            return $this->redirectToRoute('user_list');
        }

        return $this->render('user/create.html.twig', ['form' => $form->createView()]);
    }

    /**
     * Edit an existing user.
     *
     * @Route("/users/{id}/edit", name="user_edit")
     */
    public function editAction(User $user, Request $request, ManagerRegistry $doctrine, UserPasswordHasherInterface $hashPassword): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $currentPassword = $user->getPassword();
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Update password only if a new one is provided
            $password = $user->getPassword();

                if (!empty($password)) {
                    $user->setPassword(
                        $hashPassword->hashPassword($user, $password)
                    );
                } else {
                    $user->setPassword($currentPassword);
                }

            $doctrine->getManager()->flush();

            $this->addFlash('success', "The user has been successfully updated.");

            return $this->redirectToRoute('user_list');
        }

        return $this->render('user/edit.html.twig', [
            'form' => $form->createView(),
            'user' => $user
        ]);
    }
}
