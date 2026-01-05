<?php

namespace App\Controller;

use App\Dto\CapInput;
use App\Form\CapType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home', methods: ['GET'])]
    public function __invoke(): Response
    {
        $input = new CapInput();
        $form = $this->createForm(CapType::class, $input);

        return $this->render('home/index.html.twig', [
            'capForm' => $form,
        ]);
    }
}
