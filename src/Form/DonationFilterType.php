<?php

namespace App\Form;

use App\Enum\DonationStatus;
use App\Enum\DonorType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class DonationFilterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('search', TextType::class, [
                'required' => false,
                'label' => 'Recherche globale: ',
            ])

            ->add('donorType', ChoiceType::class, [
                'required' => false,
                'label' => 'Particulier ou Entreprise: ',
                'placeholder' => 'Tous',
                'choices' => [
                    'Particulier' => DonorType::PARTICULIER,
                    'Entreprise' => DonorType::ENTREPRISE,
                ],
            ])

            ->add('status', ChoiceType::class, [
                'required' => false,
                'label' => 'Statut de la donation: ',
                'placeholder' => 'Tous',
                'choices' => [
                    'Validée' => DonationStatus::DONATION_VALIDEE,
                    'Refusée' => DonationStatus::DONATION_REFUSEE,
                    'Passée' => DonationStatus::DONATION_PASSEE,
                ],
            ])

            ->add('email', TextType::class, [
                'required' => false,
                'label' => 'Email: ',
            ])

            ->add('companySiret', TextType::class, [
                'required' => false,
                'label' => 'Siret: ',
            ]);
    }
}
