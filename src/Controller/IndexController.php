<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Entity\User;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class IndexController extends AbstractController
{
    /**
     * @Route("/", name="app_index")
     */
    public function show()
    {

        $users = $this->getDoctrine()
            ->getRepository(User::class)->findAll();

        return $this->render('index.html.twig',
            [
                'users' => $users,
            ]);
    }
}