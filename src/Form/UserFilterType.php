<?php

namespace App\Form;

use App\Entity\Role;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserFilterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('search', TextType::class, [
                'required' => false,
                'label' => 'Recherche',
            ])
            ->add('actif', ChoiceType::class, [
                'required' => false,
                'label' => 'Statut',
                'placeholder' => 'Tous',
                'choices' => [
                    'Actif' => true,
                    'Inactif' => false,
                ],
            ])
            ->add('role', EntityType::class, [
                'required' => false,
                'label' => 'Rôle',
                'class' => Role::class,
                'choice_label' => 'name',
                'placeholder' => 'Tous',
            ])
            ->add('right', ChoiceType::class, [
                'required' => false,
                'label' => 'Droit',
                'placeholder' => 'Tous',
                'choices' => [
                    'Administrateur' => 'ROLE_ADMIN',
                    'Utilisateur' => 'ROLE_USER',
                ],
            ])
            ->add('dateStart', DateType::class, [
                'required' => false,
                'label' => 'Date début',
                'widget' => 'single_text',
            ])
            ->add('dateEnd', DateType::class, [
                'required' => false,
                'label' => 'Date fin',
                'widget' => 'single_text',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_protection' => false,
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'user_filter';
    }
}
