<?php

namespace App\Form;

use App\Entity\Donation;
use App\Enum\DonorType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DonationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('amount', MoneyType::class, [
                'currency' => false,
                'label' => 'Montant du don',
            ])
            ->add('donorType', ChoiceType::class, [
                'label' => 'Vous êtes',
                'choices' => [
                    'Particulier' => DonorType::PARTICULIER,
                    'Entreprise' => DonorType::ENTREPRISE,
                ],
                'choice_value' => fn (?DonorType $choice) => $choice?->value,
                'expanded' => true,
                'multiple' => false,
            ])
            ->add('companyName', TextType::class, [
                'label' => 'Nom de l’entreprise',
                'required' => false,
            ])
            ->add('companySiret', TextType::class, [
                'label' => 'SIRET',
                'required' => false,
            ])
            ->add('lastname', TextType::class, [
                'label' => 'Nom'
            ])
            ->add('firstname', TextType::class, [
                'label' => 'Prenom'
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email'
            ])
            ->add('address', TextType::class, [
                'label' => 'Adresse'
            ])
            ->add('city', TextType::class, [
                'label' => 'Ville'
            ])
            ->add('zipcode', TextType::class, [
                'label' => 'Code postal'
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Donation::class,
        ]);
    }
}
