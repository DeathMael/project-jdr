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

            $message = (new \Swift_Message('Nouvelle demande d\'inscription de ' . $contactFormData['Nom'] . ' ' . $contactFormData['Prenom']))
                ->setFrom($contactFormData['Email'])
                ->setTo('taliesincollectif@gmail.com')
                ->setBody(
                    $contactFormData['Message'] . '
            mail de l\'expéditeur: ' . $contactFormData['Email'],
                    'text/plain'
                );
            $mailer->send($message);
            //$this->addFlash('success', 'Une nouvelle demande d\'inscription a été envoyé');

            return $this->redirectToRoute('contact');
        }
        if ($form->isSubmitted() && $form->isValid() && !$this->captchaverify($request->get('g-recaptcha-response'))) {

            $this->addFlash(
                'error',
                'Captcha Requis'
            );
        }
        return $this->render('contact/index.html.twig', [
            'email_form' => $form->createView(),
        ]);
    }

    function captchaverify($recaptcha)
    {
        $url = "https://www.google.com/recaptcha/api/siteverify";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, array(
            "secret" => "6LfbncgUAAAAAGlrWEcPQs1BYHMRfo0KRzRpCzOf", "response" => $recaptcha));
        $response = curl_exec($ch);
        curl_close($ch);
        $data = json_decode($response);

        return $data->success;


    }
}
