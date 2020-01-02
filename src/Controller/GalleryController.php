<?php

namespace App\Controller;


use App\Entity\Image;
use App\Entity\User;
use App\Form\ImageUploadFormType;
use DateTime;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Knp\Component\Pager\PaginatorInterface;

class GalleryController extends AbstractController
{
    /**
     * @Route("/photographers", name="app_photographers")
     */
    public function photographerList(Request $request, PaginatorInterface $paginator)
    {
        $em = $this->getDoctrine()->getManager();
        // $users = $em->getRepository(User::class)->findBy(
        //     ['isPhotographer' => true]
        // );

        $queryBuilder = $em->getRepository(User::class)->createQueryBuilder('user');

        if ($request->query->getAlnum('filter')) {
            $queryBuilder
                ->where('user.city LIKE :filter')
                ->orWhere('user.country LIKE :filter')
                ->orWhere('user.state LIKE :filter')
                ->andWhere('user.isPhotographer = 1')
                ->setParameter('filter', '%' . $request->query->getAlnum('filter') . '%');
            //$users = $queryBuilder->getQuery()->getResult();
        }
        $users = $paginator->paginate(
            // Doctrine Query, not results
            $queryBuilder,
            // Define the page parameter
            $request->query->getInt('page', 1),
            // Items per page
            5
        );

        return $this->render('Photographer/photographerList.html.twig', [
            'users' => $users,
        ]);
    }

    /**
     * @Route("/photographers/{username}/gallery", name="app_userGallery")
     */
    public function photographerGallery($username, PaginatorInterface $paginator, Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $user = $em->getRepository(User::class)->findOneBy([
            'username' => $username
        ]);

        if ($user == null) {
            return $this->redirectToRoute('app_index');
        }

        // $images = $em->getRepository(Image::class)->findBy(
        //     ['user' => $user->getId()]
        // );
        $queryBuilder = $em->getRepository(Image::class)->createQueryBuilder('image');
        $queryBuilder
                ->where('image.user = :userId')
                ->setParameter('userId', $user->getId() );

        $images = $paginator->paginate(
            // Doctrine Query, not results
            $queryBuilder,
            // Define the page parameter
            $request->query->getInt('page', 1),
            // Items per page
            30
        );

        return $this->render('Photographer/gallery.html.twig', [
            'user' => $user,
            'images' => $images,
        ]);
    }

    /**
     * @Route("/photo/{imageName}", name="app_viewPhoto")
     */
    public function viewPhoto($imageName)
    {
        $em = $this->getDoctrine()->getManager();
        $image = $em->getRepository(Image::class)->findOneBy([
            'name' => $imageName
        ]);
        $user = $image->getUser();

        return $this->render('Photographer/photo.html.twig', [
            'user' => $user,
            'image' => $image,
        ]);
    }

    /**
     * @Route("/photo/{imageName}/delete", name="app_deletePhoto")
     */
    public function deletePhoto($imageName)
    {
        if (!$this->getUser()) {
            return $this->redirectToRoute('app_index');
        } else {
            $loggedUser = $this->getUser();
        }
        $em = $this->getDoctrine()->getManager();
        $image = $em->getRepository(Image::class)->findOneBy([
            'name' => $imageName
        ]);

        if ($image != null) {
            $user = $image->getUser();
            if ($user == $loggedUser || array_values($loggedUser->getRoles())[0] == "ROLE_ADMIN") {
                $fileSystem = new Filesystem();
                $fileSystem->remove($this->getParameter('gallery_directory') . '/' . $image->getName());

                $em->remove($image);
                $em->flush();

                $this->addFlash('success', 'Image successfully deleted');

                return $this->redirectToRoute("app_userGallery", [
                    'username' => $user->getUsername()
                ]);
            } else {
                $this->addFlash('danger', 'You can\'t do that!');
                return $this->redirectToRoute("app_userGallery", [
                    'username' => $user->getUsername()
                ]);
            }
        }
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

        if (!$this->getUser()) {
            return $this->redirectToRoute('app_index');
        } else {
            $loggedUser = $this->getUser();
            if ($user != $loggedUser) {
                $this->addFlash('danger', 'You don\'t have permission to do that!');
                return $this->redirectToRoute('app_index');
            }
        }

        $form = $this->createForm(ImageUploadFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $files = $form['pictures']->getData();

            foreach ($files as $file) {
                $image = new Image();
                $fileName = $this->generateUniqueFileName() . '.' . $file->guessExtension();

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

        return $this->render('baseForm.html.twig', [
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
