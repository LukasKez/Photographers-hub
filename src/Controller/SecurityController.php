<?php

namespace App\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class SecurityController extends AbstractController
{
    /**
     * @Route("/login", name="app_login")
     */
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_index');
        }

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    /**
     * @Route("/reset_password", name="app_resetPassword")
     */
    public function resetPassword(Request $request, \Swift_Mailer $mailer)
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_index');
        }

        $formBuilder = $this->createFormBuilder()
            ->add('email', EmailType::class)
            ->add('submit', SubmitType::class);
        $form = $formBuilder->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $email = array_values($form->getData())[0];
            $em = $this->getDoctrine()->getManager();
            $user = $em->getRepository(User::class)->findOneBy([
                'email' => $email
            ]);

            if ($user == null) {
                $this->addFlash('danger', 'User with email "'. $email. '" doesn\'t exist!');
                return $this->redirectToRoute('app_resetPasswordForm');
            }
            else {
                //send email
                $message = (new \Swift_Message('Reset password on Photographers Hub'))
                    ->setFrom('noreply@photographershub.com')
                    ->setTo($email)
                    ->setBody(
                        $this->renderView(
                            'emails/resetPassword.html.twig',
                            ['user' => $user]
                        ),
                        'text/html'
                    );

                $mailer->send($message);
                $this->addFlash('success', 'Email with password reset link sent!');
                return $this->redirectToRoute('app_index');
            }
        }

        return $this->render('baseForm.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/reset_password/{id}", name="app_resetPasswordForm")
     */
    public function resetPasswordForm($id, Request $request, UserPasswordEncoderInterface $passwordEncoder)
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_index');
        }
        
        $formBuilder = $this->createFormBuilder()
        ->add('password', RepeatedType::class, [
            'type' => PasswordType::class,
            'required' => true,
            'first_options'  => ['label' => 'Password'],
            'second_options' => ['label' => 'Repeat Password'],
            'invalid_message' => 'The password fields must match',
            'help' => 'At least 6 characters',
            'constraints' => [
                new NotBlank([
                    'message' => 'Please enter a password',
                ]),
                new Length([
                    'min' => 6,
                    'minMessage' => 'Your password should be at least {{ limit }} characters',
                    // max length allowed by Symfony for security reasons
                    'max' => 4096,
                ]),
            ],
        ])
        ->add('submit', SubmitType::class);

        $form = $formBuilder->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $password = array_values($form->getData())[0];
            $em = $this->getDoctrine()->getManager();
            $user = $em->getRepository(User::class)->find($id);
            
            $user->setPassword($passwordEncoder->encodePassword(
                $user,
                $password
            ));
            $em->persist($user);
            $em->flush();

            $this->addFlash('success', 'Password succesfully changed');
            return $this->redirectToRoute('app_index');
        }

        return $this->render('baseForm.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/logout", name="app_logout")
     */
    public function logout()
    {
    }
}
