<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserProfileFormType;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;


class UserController extends AbstractController
{
    /**
     * @Route("/user/{userID}", name="app_userProfile")
     */
    public function profile($userID, Request $request, UserPasswordEncoderInterface $passwordEncoder)
    {
        $user = $this->getDoctrine()->getRepository(User::class)->find($userID);

        //authenticate

        if ($user == null) {
            return $this->redirectToRoute('app_index');
        }

        $form = $this->createForm(UserProfileFormType::class, $user);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid())
        {
            $user = $form->getData();

            $fileSystem = new Filesystem();
            $oldAvatar = $user->getAvatar();

            /** @var UploadedFile $file */
            $file = $form['avatar']->getData();
            $password = $form['password_hash']->getData();

            if ($file)
            {
                if ($oldAvatar != null)
                {
                    $fileSystem->remove($this->getParameter('avatar_directory') . '/' . $oldAvatar);
                }
                $fileName = $this->generateUniqueFileName().'.'.$file->guessExtension();

                // moves the file to the directory where avatars are stored
                $file->move(
                    $this->getParameter('avatar_directory'),
                    $fileName
                );

                $user->setAvatar($fileName);
                $this->addFlash('success', 'Avatar changed successfully');
            }
            if ($password)
            {
                $currentPsw = $form['current_password']->getData();
                if ($passwordEncoder->isPasswordValid($user, $currentPsw))
                {
                    $user->setPassword($passwordEncoder->encodePassword($user, $password));
                    $this->addFlash('success', 'Password changed successfully');
                }
                else
                {
                    $this->addFlash('danger', 'To change password, enter your old password');
                }
            }

            $em = $this->getDoctrine()->getManager();
            $em->persist($user);
            $em->flush();
        }
        return $this->render('user/profile.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @return string
     */
    private function generateUniqueFileName()
    {
        // md5() reduces the similarity of the file names generated by
        // uniqid(), which is based on timestamps
        return md5(uniqid());
    }
}