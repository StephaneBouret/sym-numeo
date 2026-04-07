<?php

namespace App\Entity;

use libphonenumber\PhoneNumber;
use App\Repository\CompanyRepository;
use Doctrine\ORM\Mapping as ORM;
use ZipCodeValidator\Constraints\ZipCode;
use Symfony\Component\Validator\Constraints as Assert;
use Misd\PhoneNumberBundle\Validator\Constraints\PhoneNumber as AssertPhoneNumber;

#[ORM\Entity(repositoryClass: CompanyRepository::class)]
class Company
{
    public const TYPE_ASSOCIATION = 'Association';
    public const TYPE_MICRO = 'Micro-entrepreneur';
    public const TYPE_EURL = 'EURL';
    public const TYPE_SARL = 'SARL';
    public const TYPE_SASU = 'SASU';
    public const TYPE_SAS = 'SAS';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(
        message: 'Merci d\'indiquer le nom de l\'entreprise'
    )]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    private ?string $slug = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(
        message: 'Merci d\'indiquer votre adresse'
    )]
    private ?string $adress = null;

    #[ORM\Column(length: 255)]
    #[ZipCode([
        'iso' => 'FR',
        'message' => 'Le code postal n\'est pas valide'
    ])]
    private ?string $postalCode = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(
        message: 'Merci d\'indiquer votre ville'
    )]
    private ?string $city = null;

    #[ORM\Column(length: 255)]
    #[Assert\Email(
        message: 'L\'adresse e-mail {{ value }} est incorrecte',
    )]
    private ?string $email = null;

    #[ORM\Column(type: 'phone_number')]
    #[AssertPhoneNumber(defaultRegion: 'FR')]
    private ?PhoneNumber $phone = null;

    #[ORM\Column(length: 255)]
    #[Assert\Choice(choices: [self::TYPE_ASSOCIATION, self::TYPE_EURL, self::TYPE_MICRO, self::TYPE_SARL, self::TYPE_SAS, self::TYPE_SASU], message: "Le type de contenu sélectionné est invalide.")]
    private ?string $type = null;

    #[ORM\Column(length: 255)]
    #[Assert\Length(
        min: 9,
        minMessage: 'Votre SIREN doit comporter au moins {{ limit }} caractères',
    )]
    private ?string $siren = null;

    #[ORM\Column(length: 255)]
    #[Assert\Url(
        message: 'L\'url {{ value }} n\'est pas valide'
    )]
    private ?string $url = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(
        message: 'Merci d\'indiquer le prénom et le nom du dirigeant'
    )]
    private ?string $manager = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): static
    {
        $this->slug = $slug;

        return $this;
    }

    public function getAdress(): ?string
    {
        return $this->adress;
    }

    public function setAdress(string $adress): static
    {
        $this->adress = $adress;

        return $this;
    }

    public function getPostalCode(): ?string
    {
        return $this->postalCode;
    }

    public function setPostalCode(string $postalCode): static
    {
        $this->postalCode = $postalCode;

        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(string $city): static
    {
        $this->city = $city;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

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

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getSiren(): ?string
    {
        return $this->siren;
    }

    public function setSiren(string $siren): static
    {
        $this->siren = $siren;

        return $this;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(string $url): static
    {
        $this->url = $url;

        return $this;
    }

    public function getManager(): ?string
    {
        return $this->manager;
    }

    public function setManager(string $manager): static
    {
        $this->manager = $manager;

        return $this;
    }
}
