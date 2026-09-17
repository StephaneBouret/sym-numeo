<?php

namespace App\DataFixtures;

use App\Entity\Company;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;
use libphonenumber\PhoneNumberUtil;

class CompanyFixtures extends Fixture implements FixtureGroupInterface
{
    public static function getGroups(): array
    {
        return ['company'];
    }

    public function load(ObjectManager $manager): void
    {
        $phoneNumber = PhoneNumberUtil::getInstance()->parse(
            '+33634560579',
            'FR'
        );

        $company = (new Company())
            ->setName('Potentiel Consulting')
            ->setSlug('potentiel-consulting')
            ->setAdress('1B, La Fendoire')
            ->setPostalCode('44770')
            ->setCity('La Plaine sur Mer')
            ->setEmail('contact@luniversdesnombres.com')
            ->setPhone($phoneNumber)
            ->setType(Company::TYPE_SASU)
            ->setSiren('911 248 904 00029')
            ->setUrl('https://www.luniversdesnombres.com')
            ->setManager('France Tuncq');

        $manager->persist($company);
        $manager->flush();
    }
}
