<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final class CapInput
{
    #[Assert\NotBlank()]
    #[Assert\Length(max: 200)]
    public string $firstNames = '';

    #[Assert\NotBlank()]
    #[Assert\Length(max: 200)]
    public string $lastName = '';

    #[Assert\NotBlank()]
    public ?\DateTimeImmutable $birthDate = null;
}
