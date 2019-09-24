<?php

namespace App\Controller;

use App\Form\ContactFormType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;


class ContactController extends AbstractController
{
    /**
     * @Route("/contact", name="contact")
     */
    public function index(Request $request, \Swift_Mailer $mailer)
    {
        $form = $this->createForm(ContactFormType::class);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid())
        {
            $contactFormData = $form->getData();

            // if ()
            // {
            //     $this->addFlash('danger', 'Error');
            // }

            $message = (new \Swift_Message('Contact form: from '. $contactFormData['name']. ' subject: '.$contactFormData['subject']))
                ->setFrom($contactFormData['email'])
                ->setTo('l.kezevicius@gmail.com')
                ->setBody(
                    $contactFormData['message'],
                    'text/plain'
                )
            ;

            $mailer->send($message);
            $this->addFlash('success', 'Email sent!');
            $this->redirectToRoute('contact');
        }

        return $this->render('contact.html.twig', [
            'contact_form' => $form->createView(),
        ]);
    }
}
