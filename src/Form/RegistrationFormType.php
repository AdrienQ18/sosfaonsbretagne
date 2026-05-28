<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'label' => 'Adresse email* : ',
            ])
            ->add('plainPassword', PasswordType::class, [

                // ce champ n'est pas enregistré directement en base
                'mapped' => false,

                'label' => 'Mot de passe* : ',
                'attr' => [
                    'autocomplete' => 'new-password',
                ],
                'constraints' => [
                    new NotBlank(
                        message: 'Veuillez saisir un mot de passe.',
                    ),
                    new Length(
                        min: 6,
                        minMessage: 'Votre mot de passe doit contenir au moins {{ limit }} caractères.',
                        // longueur max Symfony
                        max: 4096,
                    ),
                ],
            ])
            ->add('lastname', TextType::class, [
                'label' => 'Nom* : ',
            ])
            ->add('firstname', TextType::class, [
                'label' => 'Prenom* : ',
            ])
            ->add('phone', TextType::class, [
                'label' => 'Téléphone* : ',
            ])
            ->add('address', TextType::class, [
                'label' => 'Adresse* : ',])
            ->add('city', TextType::class, [
                'label' => 'Ville* : ',])
            ->add('zipcode', TextType::class, [
                'label' => 'Code postal* : ',
            ])
            ->add('agreeTerms', CheckboxType::class, [
                'label' => 'J\'accepte les conditions d\'utilisation',
                'mapped' => false,
                'constraints' => [
                    new IsTrue(
                        message: 'Vous devez accepter les conditions d\'utilisation.',
                    ),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
