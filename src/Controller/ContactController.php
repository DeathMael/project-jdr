<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use App\Form\ContactType;

class ContactController extends AbstractController
{
    /**
     * @Route("/contact",name="contact")
     */
    public function index(request $request, \Swift_Mailer $mailer)
    {
        $form = $this->createForm(ContactType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $contactFormData = $form->getData();

            $message =(new \Swift_Message('Nouvelle demande d\'inscription'))
                ->setFrom($contactFormData['fromEmail'])
                ->setTo('testeur21800@gmail.com')
                ->setBody(
                    $contactFormData['message'],
        'text/plain'
                );
        $mailer->send($message);
        $this->addFlash('success', 'Votre message a bien été envoyé !');

        return $this->redirectToRoute('contact');
        }
        return $this->render('contact/index.html.twig', [
            'email_form' => $form->createView(),
        ]);
    }
}