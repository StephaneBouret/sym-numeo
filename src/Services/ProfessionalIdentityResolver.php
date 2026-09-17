<?php

declare(strict_types=1);

namespace App\Services;

use App\Dto\ResolvedProfessionalIdentity;
use App\Entity\User;
use libphonenumber\PhoneNumber;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;

final class ProfessionalIdentityResolver
{
    public function resolve(User $user): ResolvedProfessionalIdentity
    {
        $profile = $user->getProfessionalProfile();
        $logo = $profile?->getLogo();

        return new ResolvedProfessionalIdentity(
            name: $this->firstFilled($profile?->getProfessionalName(), $user->getFullname()) ?? '',
            legalName: $profile?->getLegalName(),
            siret: $profile?->getSiret(),
            email: $this->firstFilled($profile?->getEmail(), $user->getEmail()) ?? '',
            phone: $this->formatPhone($profile?->getPhone() ?? $user->getPhone()),
            address: $this->firstFilled($profile?->getAddress(), $user->getAdress()),
            postalCode: $this->firstFilled($profile?->getPostalCode(), $user->getPostalCode()),
            city: $this->firstFilled($profile?->getCity(), $user->getCity()),
            websiteUrl: $profile?->getWebsiteUrl(),
            logoImageName: $logo?->getImageName(),
        );
    }

    public function resolveUserFallback(User $user): ResolvedProfessionalIdentity
    {
        return new ResolvedProfessionalIdentity(
            name: $user->getFullname(),
            legalName: null,
            siret: null,
            email: $user->getEmail() ?? '',
            phone: $this->formatPhone($user->getPhone()),
            address: $this->firstFilled($user->getAdress()),
            postalCode: $this->firstFilled($user->getPostalCode()),
            city: $this->firstFilled($user->getCity()),
            websiteUrl: null,
            logoImageName: null,
        );
    }

    private function firstFilled(?string ...$values): ?string
    {
        foreach ($values as $value) {
            if (null !== $value && '' !== trim($value)) {
                return trim($value);
            }
        }

        return null;
    }

    private function formatPhone(?PhoneNumber $phone): ?string
    {
        if (null === $phone) {
            return null;
        }

        return PhoneNumberUtil::getInstance()->format($phone, PhoneNumberFormat::NATIONAL);
    }
}
