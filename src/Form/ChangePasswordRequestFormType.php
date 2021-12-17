<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Validator\Constraints\UserPassword;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class ChangePasswordRequestFormType extends AbstractType
{
    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('old_password', PasswordType::class, array(
                'mapped' => false,
                'constraints' => [
                    new UserPassword(array(
                        'message' => 'The password typed must match with your password.'
                        )
                    )
                ],
                'invalid_message' => 'The password typed must match with your password.'
                )
            )
            ->add('new_password', RepeatedType::class, array(
                'mapped' => false,
                'constraints' => [
                    new NotBlank(array(
                        'message' => 'Please enter a valid password.'
                        )
                    ),
                    new Regex(array(
                        'pattern' => '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&-_])[A-Za-z\d@$!%*?&-_]{8,}$/',
                        'message' => 'A password must contain at least an uppercase letter, a lower case letter, a digit and 1 special character (-,_,\',...) and must be at least of length 8.'
                        )
                    )
                ],
                'type' => PasswordType::class,
                'invalid_message' => 'The password fields must match.'
                )
            )
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
    }
}
