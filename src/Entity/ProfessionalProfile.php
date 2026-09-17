<?php

namespace App\Entity;

use App\Repository\ProfessionalProfileRepository;
use Doctrine\ORM\Mapping as ORM;
use libphonenumber\PhoneNumber;
use Misd\PhoneNumberBundle\Validator\Constraints\PhoneNumber as AssertPhoneNumber;
use Symfony\Component\Validator\Constraints as Assert;
use ZipCodeValidator\Constraints\ZipCode;

#[ORM\Entity(repositoryClass: ProfessionalProfileRepository::class)]
class ProfessionalProfile
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'professionalProfile')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\Column(length: 120, nullable: true)]
    #[Assert\Length(max: 120, maxMessage: 'Le nom professionnel ne peut pas dépasser {{ limit }} caractères.')]
    private ?string $professionalName = null;

    #[ORM\Column(length: 120, nullable: true)]
    #[Assert\Length(max: 120, maxMessage: 'La raison sociale ne peut pas dépasser {{ limit }} caractères.')]
    private ?string $legalName = null;

    #[ORM\Column(length: 14, nullable: true)]
    #[Assert\Regex(pattern: '/^\d{14}$/', message: 'Le SIRET doit contenir exactement 14 chiffres.')]
    private ?string $siret = null;

    #[ORM\Column(length: 180, nullable: true)]
    #[Assert\Email(message: 'L\'adresse email professionnelle {{ value }} est incorrecte.')]
    #[Assert\Length(max: 180, maxMessage: 'L\'adresse email ne peut pas dépasser {{ limit }} caractères.')]
    private ?string $email = null;

    #[ORM\Column(type: 'phone_number', nullable: true)]
    #[AssertPhoneNumber(defaultRegion: 'FR')]
    private ?PhoneNumber $phone = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Length(max: 255, maxMessage: 'L\'adresse ne peut pas dépasser {{ limit }} caractères.')]
    private ?string $address = null;

    #[ORM\Column(length: 20, nullable: true)]
    #[ZipCode([
        'iso' => 'FR',
        'message' => 'Le code postal n\'est pas valide.',
    ])]
    private ?string $postalCode = null;

    #[ORM\Column(length: 120, nullable: true)]
    #[Assert\Length(max: 120, maxMessage: 'La ville ne peut pas dépasser {{ limit }} caractères.')]
    private ?string $city = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Url(
        protocols: ['http', 'https'],
        message: 'L\'URL du site Internet n\'est pas valide.'
    )]
    #[Assert\Length(max: 255, maxMessage: 'L\'URL du site Internet ne peut pas dépasser {{ limit }} caractères.')]
    private ?string $websiteUrl = null;

    #[ORM\OneToOne(mappedBy: 'professionalProfile', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private ?ProfessionalLogo $logo = null;

    public function __construct(?User $user = null)
    {
        if (null !== $user) {
            $this->setUser($user);
        }
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        if (null !== $user && $user->getProfessionalProfile() !== $this) {
            $user->setProfessionalProfile($this);
        }

        return $this;
    }

    public function getProfessionalName(): ?string
    {
        return $this->professionalName;
    }

    public function setProfessionalName(?string $professionalName): static
    {
        $this->professionalName = $this->normalizeNullableString($professionalName);

        return $this;
    }

    public function getLegalName(): ?string
    {
        return $this->legalName;
    }

    public function setLegalName(?string $legalName): static
    {
        $this->legalName = $this->normalizeNullableString($legalName);

        return $this;
    }

    public function getSiret(): ?string
    {
        return $this->siret;
    }

    public function setSiret(?string $siret): static
    {
        if (null === $siret) {
            $this->siret = null;

            return $this;
        }

        $normalized = preg_replace('/\s+/u', '', trim($siret)) ?? trim($siret);
        $this->siret = '' === $normalized ? null : $normalized;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): static
    {
        $email = $this->normalizeNullableString($email);
        $this->email = null === $email ? null : mb_strtolower($email, 'UTF-8');

        return $this;
    }

    public function getPhone(): ?PhoneNumber
    {
        return $this->phone;
    }

    public function setPhone(?PhoneNumber $phone): static
    {
        $this->phone = $phone;

        return $this;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(?string $address): static
    {
        $this->address = $this->normalizeNullableString($address);

        return $this;
    }

    public function getPostalCode(): ?string
    {
        return $this->postalCode;
    }

    public function setPostalCode(?string $postalCode): static
    {
        $this->postalCode = $this->normalizeNullableString($postalCode);

        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(?string $city): static
    {
        $this->city = $this->normalizeNullableString($city);

        return $this;
    }

    public function getWebsiteUrl(): ?string
    {
        return $this->websiteUrl;
    }

    public function setWebsiteUrl(?string $websiteUrl): static
    {
        $this->websiteUrl = $this->normalizeNullableString($websiteUrl);

        return $this;
    }

    public function getLogo(): ?ProfessionalLogo
    {
        return $this->logo;
    }

    public function setLogo(?ProfessionalLogo $logo): static
    {
        if (null === $logo && null !== $this->logo) {
            $this->logo->setProfessionalProfile(null);
        }

        if (null !== $logo && $logo->getProfessionalProfile() !== $this) {
            $logo->setProfessionalProfile($this);
        }

        $this->logo = $logo;

        return $this;
    }

    public function hasBusinessData(): bool
    {
        return null !== $this->professionalName
            || null !== $this->legalName
            || null !== $this->siret
            || null !== $this->email
            || null !== $this->phone
            || null !== $this->address
            || null !== $this->postalCode
            || null !== $this->city
            || null !== $this->websiteUrl
            || (null !== $this->logo && $this->logo->hasImage());
    }

    private function normalizeNullableString(?string $value): ?string
    {
        if (null === $value) {
            return null;
        }

        $normalized = trim(preg_replace('/\s+/u', ' ', $value) ?? $value);

        return '' === $normalized ? null : $normalized;
    }
}
