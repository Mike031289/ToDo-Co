<?php

namespace AppBundle\Controller;

use AppBundle\Entity\User;
use AppBundle\Form\UserType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

/**
 * Class UserController
 *
 * Manages administrative user operations including list viewing, creation, and updating.
 * Access to this entire controller is restricted strictly to users with ROLE_ADMIN.
 *
 * @package AppBundle\Controller
 */
class UserController extends Controller
{
    /**
     * Display the list of all registered users.
     *
     * @Route("/users", name="user_list")
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function listAction()
    {
        // Restrict access to administrative users only
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        return $this->render('user/list.html.twig', [
            'users' => $this->getDoctrine()->getRepository('AppBundle:User')->findAll()
        ]);
    }

    /**
     * Create and store a new user.
     *
     * @Route("/users/create", name="user_create")
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function createAction(Request $request)
    {
        // Restrict access to administrative users only
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $user = new User();
        $form = $this->createForm(UserType::class, $user);

        $form->handleRequest($request);

        // Check if the form is submitted and valid before processing
        if ($form->isSubmitted() === true && $form->isValid() === true) {
            $em = $this->getDoctrine()->getManager();

            // Encode the plain text password provided in the form
            $password = $this->get('security.password_encoder')->encodePassword($user, $user->getPassword());
            $user->setPassword($password);

            $em->persist($user);
            $em->flush();

            $this->addFlash('success', "L'utilisateur a bien été ajouté.");

            return $this->redirectToRoute('user_list');
        }

        return $this->render('user/create.html.twig', ['form' => $form->createView()]);
    }

    /**
     * Edit an existing user's profile and roles.
     *
     * @Route("/users/{id}/edit", name="user_edit")
     *
     * @param User $user
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function editAction(User $user, Request $request)
    {
        // Restrict access to administrative users only
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        // Store the existing hashed password in case the password field is left blank
        $oldPassword = $user->getPassword();

        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        // Check if the form is submitted and valid before processing
        if ($form->isSubmitted() === true && $form->isValid() === true) {

            // Check if a new password has been filled in the form
            if (empty($user->getPassword()) === false) {
                // Encode and set the new password
                $password = $this->get('security.password_encoder')->encodePassword($user, $user->getPassword());
                $user->setPassword($password);
            } else {
                // Fallback to the original hashed password to prevent overwriting with null
                $user->setPassword($oldPassword);
            }

            // The CallbackTransformer in UserType automatically updates the roles array here
            $this->getDoctrine()->getManager()->flush();

            $this->addFlash('success', "L'utilisateur a bien été modifié");

            return $this->redirectToRoute('user_list');
        }

        return $this->render('user/edit.html.twig', [
            'form' => $form->createView(),
            'user' => $user
        ]);
    }
}
