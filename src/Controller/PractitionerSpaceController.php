<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\ProfessionalProfile;
use App\Entity\User;
use App\Form\ProfessionalProfileFormType;
use App\Services\ProfessionalIdentityResolver;
use App\Services\ProfessionalProfileService;
use App\Services\UserActiveSubscriptionService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER', message: 'Vous devez être connecté pour accéder à cette page.')]
final class PractitionerSpaceController extends AbstractController
{
    #[Route('/mon-espace', name: 'app_practitioner_space', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        ProfessionalIdentityResolver $identityResolver,
        ProfessionalProfileService $professionalProfileService,
        UserActiveSubscriptionService $activeSubscriptionService,
    ): Response {
        $user = $this->getUser();

        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        if (!$activeSubscriptionService->hasActiveSubscription($user)) {
            $this->addFlash(
                'warning',
                'Un abonnement actif est nécessaire pour accéder à votre espace praticien.'
            );

            return $this->redirectToRoute('app_user_subscription_show');
        }

        $profile = $user->getProfessionalProfile() ?? new ProfessionalProfile();

        $resolvedIdentity = $identityResolver->resolve($user);
        $fallbackIdentity = $identityResolver->resolveUserFallback($user);

        $form = $this->createForm(ProfessionalProfileFormType::class, $profile, [
            'fallback_identity' => $fallbackIdentity,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $logoFile = $form->get('logo')->get('imageFile')->getData();

            $savedProfile = $professionalProfileService->save(
                $user,
                $profile,
                $logoFile instanceof UploadedFile ? $logoFile : null,
            );

            if ($savedProfile instanceof ProfessionalProfile) {
                $this->addFlash(
                    'success',
                    'Votre identité professionnelle a bien été enregistrée.'
                );
            } else {
                $this->addFlash(
                    'info',
                    'Aucune identité professionnelle spécifique n\'est enregistrée. '
                    .'Les informations de votre profil utilisateur seront utilisées.'
                );
            }

            return $this->redirectToRoute('app_practitioner_space');
        }

        return $this->render('practitioner_space/edit.html.twig', [
            'form' => $form,
            'professionalProfile' => $user->getProfessionalProfile(),
            'resolvedIdentity' => $resolvedIdentity,
            'fallbackIdentity' => $fallbackIdentity,
        ]);
    }

    #[Route('/mon-espace/logo', name: 'app_practitioner_space_logo_delete', methods: ['DELETE'])]
    public function deleteLogo(
        Request $request,
        ProfessionalProfileService $professionalProfileService,
        UserActiveSubscriptionService $activeSubscriptionService,
        LoggerInterface $logger,
    ): JsonResponse
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            return $this->json(
                [
                    'success' => false,
                    'message' => 'Vous devez être connecté.',
                ],
                JsonResponse::HTTP_UNAUTHORIZED
            );
        }

        if (!$activeSubscriptionService->hasActiveSubscription($user)) {
            return $this->json(
                [
                    'success' => false,
                    'message' => 'Un abonnement actif est nécessaire.',
                ],
                JsonResponse::HTTP_FORBIDDEN
            );
        }

        $csrfToken = $request->headers->get('X-CSRF-Token');
        $csrfTokenId = 'delete-professional-logo-'.$user->getId();

        if (!$this->isCsrfTokenValid($csrfTokenId, $csrfToken)) {
            return $this->json(
                [
                    'success' => false,
                    'message' => 'Le jeton de sécurité est invalide.',
                ],
                JsonResponse::HTTP_FORBIDDEN
            );
        }

        try {
            $removed = $professionalProfileService->removeLogo($user);

            if (!$removed) {
                return $this->json(
                    [
                        'success' => false,
                        'message' => 'Aucun logo professionnel n\'est enregistré.',
                    ],
                    JsonResponse::HTTP_NOT_FOUND
                );
            }
        } catch (\Throwable $exception) {
            $logger->error(
                'Échec de la suppression du logo professionnel.',
                [
                    'exception' => $exception,
                    'user_id' => $user->getId(),
                ]
            );

            return $this->json(
                [
                    'success' => false,
                    'message' => 'Le logo n\'a pas pu être supprimé.',
                ],
                JsonResponse::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        return $this->json([
            'success' => true,
            'message' => 'Le logo professionnel a bien été supprimé.',
        ]);
    }
}
