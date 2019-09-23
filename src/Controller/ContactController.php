<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Form\ContactForm;
use Symfony\Component\HttpFoundation\Request;

class ContactController extends AbstractController
{
    /**
     * @Route("/contact", name="contact")
     */
    public function show(Request $request)
    {
        $form = $this->createForm(ContactForm::class);
        $form->handleRequest($request);

        return $this->render('contact.html.twig', [
            'contact' => $form->createView(),
        ]);
    }
}