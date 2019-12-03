<?php
namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;
use Symfony\Component\Routing\Annotation\Route;


class EquipeController
{

    /**
     * @var Environment
     */
    private $twig;

    public function __construct(Environment $twig)
    {
        $this->twig = $twig;
    }

    /**
     * @Route("/equipe", name="equipe_index")
     */
    public function index(): Response
    {
        return new Response($this->twig->render('equipe/index.html.twig'));
    }
}