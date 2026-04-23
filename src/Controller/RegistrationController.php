<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Google\GoogleService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

final class RegistrationController extends AbstractController
{
    #[Route('/inscription', name: 'app_register')]
    public function index(
        Request $request,
        RequestStack $requestStack,
        UserPasswordHasherInterface $userPasswordHasher,
        Security $security,
        EntityManagerInterface $em,
        GoogleService $googleService
    ): Response {
        $user = new User();

        // Gestion propre du _target_path
        $session = $requestStack->getSession();
        $targetPath = $request->query->get('_target_path', $session->get('_security.main.target_path'));

        if ($targetPath) {
            $session->set('_security.main.target_path', $targetPath);
        }

        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var string $plainPassword */
            $plainPassword = $form->get('plainPassword')->getData();
            $firstname = $form->get('firstname')->getData();
            $lastname = $form->get('lastname')->getData();

            $user->setPassword(
                $userPasswordHasher->hashPassword($user, $plainPassword)
            );

            $user->setFirstname(ucfirst($firstname))
                ->setLastname(mb_strtoupper($lastname));

            $em->persist($user);
            $em->flush();

            // 🔥 Symfony gère la redirection automatiquement
            return $security->login($user, 'security.authenticator.form_login.main', 'main');
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form,
            'google_api_key' => $googleService->getGoogleKey()
        ]);
    }
}
