<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegisterFormType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;


class RegistrationController extends AbstractController
{
    /**
     * @Route("/register", name="app_register")
     */
    public function register(Request $request, UserPasswordEncoderInterface $passwordEncoder)
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_index');
        }

        $form = $this->createForm(RegisterFormType::class);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user = $form->getData();

            $user->setPassword($passwordEncoder->encodePassword(
                $user,
                $form->get('password_hash')->getData()
            ));
            $user->setIsPhotographer(false);
            $user->setAvatar(null);

            $em = $this->getDoctrine()->getManager();
            $em->persist($user);
            $em->flush();

            $this->addFlash('success', 'User '. $user->getUsername(). ' created successfully!');
            return $this->redirectToRoute('app_index');
        }

        return $this->render('Security/register.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
