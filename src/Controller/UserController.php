<?php

namespace App\Controller;

use App\Entity\Album;
use App\Entity\Image;
use App\Entity\User;
use App\Form\ImageUploadFormType;
use App\Form\UserProfileFormType;
use DateTime;
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
     * @Route("/user/{userName}", name="app_userProfile")
     */
    public function profile($userName, Request $request, UserPasswordEncoderInterface $passwordEncoder)
    {
        $user = $this->getDoctrine()->getRepository(User::class)->findOneBy(['username' => $userName]);

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
                $this->addFlash('success', 'Profile picture changed successfully');
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
                    $this->addFlash('danger', 'To change password, enter correct current password');
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
     * @Route("/admin/users", name="app_userList")
     */
    public function userList()
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN', null, 'Unable to access this page!');

        $users = $this->getDoctrine()->getRepository(User::class)->findAll();

        return $this->render('user/userList.html.twig', [
        'users' => $users
        ]);
    }

    /**
    * @Route("/admin/user/{id}/delete", name="app_userDelete")
    */
    public function userDelete($id)
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN', null, 'Unable to access this page!');

        $manager = $this->getDoctrine()->getManager();
        $user = $manager->getRepository(User::class)->find($id);
        if ($user != null) {
            $userId = $user->getId();
            //delete all user uploaded image names from database
            $sql = "DELETE FROM image WHERE user_id=$userId";
            $statement = $manager->getConnection()->prepare($sql);

            //delete all images from the server
            $images = $user->getImages();
            $fileSystem = new Filesystem();
            if ($images != null){
                foreach ($images as $image){
                    $fileSystem->remove($this->getParameter('gallery_directory') . '/' . $image->getName());
                }
            }
            //delete avatar from server
            $avatar = $user->getAvatar();
            if ($avatar != null){
                $fileSystem->remove($this->getParameter('avatar_directory') . '/' . $avatar);
            }

            $statement->execute();
            //delete user from the database
            $manager->remove($user);
            $manager->flush();
            $this->addFlash('success', 'User "' . $user->getUsername() . '" successfully deleted');
        }
        return $this->redirectToRoute("app_userList");
    }

    /**
    * @Route("/photographers", name="app_photographers")
    */
    public function photographerList()
    {
        $em = $this->getDoctrine()->getManager();
        $users = $em->getRepository(User::class)->findBy(
            ['isPhotographer' => true]
        );
        
        return $this->render('Photographer/photographerList.html.twig', [
            'users' => $users,
        ]);
    }
    
    /**
     * @Route("/photographers/{username}/gallery", name="app_userGallery")
     */
    public function photographerGallery($username)
    {
        $em = $this->getDoctrine()->getManager();
        $user = $em->getRepository(User::class)->findOneBy([
            'username' => $username
        ]);

        if ($user == null) {
            return $this->redirectToRoute('app_index');
        }

        $images = $em->getRepository(Image::class)->findBy(
            ['user' =>$user->getId()]
        );
        return $this->render('Photographer/gallery.html.twig', [
            'user' => $user,
            'images' => $images,
        ]);
    }

    /**
     * @Route("/photographers/{username}/gallery/upload", name="app_userGalleryUpload")
     */
    public function photographerGalleryUpload($username, Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $user = $em->getRepository(User::class)->findOneBy([
            'username' => $username
        ]);

        if ($user == null) {
            return $this->redirectToRoute('app_index');
        }

        $form = $this->createForm(ImageUploadFormType::class);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid())
        {
            $files = $form['pictures']->getData();

            foreach ($files as $file)
            {
                $image = new Image();
                $fileName = $this->generateUniqueFileName().'.'.$file->guessExtension();
                
                // moves the files to the directory where images are stored
                $file->move(
                    $this->getParameter('gallery_directory'),
                    $fileName
                );
                $date = new DateTime('now');

                $image->setName($fileName);
                $image->setUploadedAt($date);
                $image->setUser($user);

                $em->persist($image);
            }
            $em->flush();

            return $this->redirectToRoute('app_userGallery', [
                'username' => $user->getUsername()
            ]);
        }

        return $this->render('Photographer/galleryUpload.html.twig', [
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
