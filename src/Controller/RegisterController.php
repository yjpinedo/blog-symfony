<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class RegisterController extends AbstractController
{
    /**
     * @Route("/register", name="register")
     */
    public function index(Request $request, ManagerRegistry $doctrine, UserPasswordHasherInterface $passwordHasher)
    {
        $user = new User();
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $doctrine->getManager();
            $user->setPassword($passwordHasher->hashPassword(
                $user,
                $form['password']->getData()
            ));
            $user->setRoles(['bloggers']);
            $entityManager->persist($user);
            $entityManager->flush();
            $this->addFlash('success', 'Registered User successfull');
            return $this->redirectToRoute('register');
        }
        return $this->render('register/index.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
