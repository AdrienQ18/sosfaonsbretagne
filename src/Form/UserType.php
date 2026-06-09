<?php

namespace App\Form;

use App\Entity\Availability;
use App\Entity\Role;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserType extends AbstractType
{
    public function __construct(private Security $security)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstname', null, [
                'label' => 'Prénom : ',
            ])
            ->add('lastname', null, [
                'label' => 'Nom : ',
            ])
            ->add('email', null, [
                'label' => 'Email : ',
            ])
            ->add('phone', null, [
                'label' => 'Téléphone : ',
            ])
            ->add('address', null, [
                'label' => 'Adresse : ',
            ])
            ->add('city', null, [
                'label' => 'Ville : ',
            ])
            ->add('zipcode', null, [
                'label' => 'Code postal : ',
            ])
            ->add('actif', null, [
                'label' => 'Compte actif : ',
            ])
            ->add('birthday', null, [
                'label' => 'Date de naissance : ',
            ])
            ->add('userRole', EntityType::class, [
                'label' => 'Rôle association : ',
                'class' => Role::class,
                'choice_label' => 'name',
                'choice_value' => 'id',
                'expanded' => false,
                'multiple' => false,
            ])
            ->add('availabilitys', EntityType::class, [
                'label' => 'Disponibilités : ',
                'class' => Availability::class,
                'choice_label' => 'label',
                'choice_value' => 'id',
                'multiple' => true,
                'expanded' => true,
            ]);

        if ($this->security->isGranted('ROLE_ADMIN')) {
            $builder->add('roles', ChoiceType::class, [
                'label' => 'Droits du site :',
                'choices' => [
                    'Administrateur' => 'ROLE_ADMIN',
                    'Utilisateur' => 'ROLE_USER',
                ],
                'multiple' => true,
                'expanded' => true,
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
