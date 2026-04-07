<?php

namespace App\Controller\Admin;

use App\Entity\Company;
use libphonenumber\PhoneNumberFormat;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Field\UrlField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use Misd\PhoneNumberBundle\Form\Type\PhoneNumberType;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use Misd\PhoneNumberBundle\Templating\Helper\PhoneNumberHelper;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

class CompanyCrudController extends AbstractCrudController
{
    public function __construct(protected PhoneNumberHelper $phoneNumberHelper)
    {
    }

    public static function getEntityFqcn(): string
    {
        return Company::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityPermission('ROLE_ADMIN')
            ->setPageTitle('index', 'Entreprise :')
            ->setPageTitle('new', 'Créer une entreprise')
            ->setPageTitle('edit', fn (Company $company) => (string) $company->getName())
            ->setPageTitle('detail', fn (Company $company) => (string) $company->getName())
            ->setEntityLabelInSingular('une entreprise')
            ->setDefaultSort(['id' => 'ASC']);
    }

    public function configureActions(Actions $actions): Actions
    {
        $actions = parent::configureActions($actions);
        return $actions;
    }

    public function configureFields(string $pageName): iterable
    {
        $types = [
            Company::TYPE_ASSOCIATION,
            Company::TYPE_EURL,
            Company::TYPE_MICRO,
            Company::TYPE_SARL,
            Company::TYPE_SAS,
            Company::TYPE_SASU
        ];

        return [
            IdField::new('id')->onlyOnIndex(),
            TextField::new('name', 'Raison sociale :'),
            ChoiceField::new('type')
                ->setChoices(array_combine($types, $types))
                ->renderExpanded()
                ->renderAsBadges(),
            TextField::new('adress', 'Adresse :')
                ->setFormTypeOptions(['attr' => ['placeholder' => 'Adresse de l\'entreprise']])
                ->setColumns(6)
                ->hideOnIndex(),
            TextField::new('postalCode', 'Code postal :')
                ->setFormTypeOptions(['attr' => ['placeholder' => 'Code postal de l\'entreprise']])
                ->setColumns(6)
                ->hideOnIndex(),
            TextField::new('city', 'Ville :')
                ->setFormTypeOptions(['attr' => ['placeholder' => 'Ville de l\'entreprise']])
                ->setColumns(6)
                ->hideOnIndex(),
            TextField::new('phone', 'Téléphone')
                ->setFormType(PhoneNumberType::class)
                ->setFormTypeOptions([
                    'default_region' => 'FR',
                    'format' => PhoneNumberFormat::NATIONAL,
                    'attr' => ['placeholder' => 'Téléphone de l\'entreprise']
                ])
                ->setColumns(6)
                ->onlyOnForms(),
            TextField::new('phone', 'Téléphone')
                ->formatValue(function ($value, $entity) {
                    $value = $entity->getPhone();
                    $formattedValue = $this->phoneNumberHelper->format($value, 2);
                    return $formattedValue;
                })
                ->onlyOnIndex(),
            EmailField::new('email', 'Email de l\'entreprise :')
                ->setFormTypeOptions(['attr' => ['placeholder' => 'Email de l\'entreprise']]),
            TextField::new('siren', 'SIRET :')
                ->setFormTypeOptions(['attr' => ['placeholder' => 'SIRET de l\'entreprise']])
                ->setColumns(6)
                ->hideOnIndex(),
            TextField::new('manager', 'Prénom et Nom du dirigeant')
                ->hideOnIndex()
                ->setHelp('Merci d\'indiquer le prénom et le nom du dirigeant'),
            UrlField::new('url', 'Site web de l\'entreprise')
                ->hideOnIndex(),
        ];
    }
}
