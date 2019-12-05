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

        if ($form->isSubmitted() && $form->isValid() && $this->captchaverify($request->get('g-recaptcha-response'))) {
            $contactFormData = $form->getData();

            $message =(new \Swift_Message('Nouvelle demande d\'inscription de '. $contactFormData['Nom']. ' ' . $contactFormData['Prenom']))
                ->setFrom($contactFormData['Email'])
                ->setTo('testeur21800@gmail.com')
                ->setBody(
                    $contactFormData['Message'] . '
            mail de l\'expéditeur: ' . $contactFormData['Email'],
        'text/plain'
                );
        $mailer->send($message);
        //$this->addFlash('success', 'Une nouvelle demande d\'inscription a été envoyé');

        return $this->redirectToRoute('contact');
        }
        if($form->isSubmitted() &&  $form->isValid() && !$this->captchaverify($request->get('g-recaptcha-response'))){

            $this->addFlash(
                'error',
                'Captcha Requis'
            );
        }
        return $this->render('contact/index.html.twig', [
            'email_form' => $form->createView(),
        ]);
    }


}