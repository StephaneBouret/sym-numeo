<?php

declare(strict_types=1);

namespace App\Dto;

final readonly class ResolvedProfessionalIdentity
{
    public function __construct(
        public string $name,
        public ?string $legalName,
        public ?string $siret,
        public string $email,
        public ?string $phone,
        public ?string $address,
        public ?string $postalCode,
        public ?string $city,
        public ?string $websiteUrl,
        public ?string $logoImageName,
    ) {
    }
}
