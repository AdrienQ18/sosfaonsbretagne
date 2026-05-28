<?php

namespace App\Form;

use App\Entity\Availability;
use App\Entity\Role;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstname')
            ->add('lastname')
            ->add('email')
            ->add('phone')
            ->add('address')
            ->add('city')
            ->add('zipcode')
            ->add('actif')
            ->add('Availabilitys', EntityType::class, [
                'class' => Availability::class,
                'choice_label' => 'label', // Le champ à afficher dans le select
                'choice_value' => 'id',   // Le champ à utiliser comme valeur
                'multiple' => true,
                'expanded' => false,
            ])
            ->add('userRole', EntityType::class, [
                'label' => 'Role',
                'class' => Role::class,
                'choice_label' => 'name', // Le champ à afficher dans le select
                'choice_value' => 'id',   // Le champ à utiliser comme valeur
                'expanded' => false,
                'multiple' => false,
            ]);
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
