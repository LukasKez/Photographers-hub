<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class RegisterFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('username')
            ->add('email', EmailType::class)
            ->add('password_hash', RepeatedType::class, [
                'type' => PasswordType::class,
                'required' => true,
                'first_options'  => ['label' => 'Password'],
                'second_options' => ['label' => 'Repeat Password'],
                'invalid_message' => 'The password fields must match',
                'mapped' => false,
                'help' => 'At least 6 characters',
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please enter a password',
                    ]),
                    new Length([
                        'min' => 6,
                        'minMessage' => 'Your password should be at least {{ limit }} characters',
                        // max length allowed by Symfony for security reasons
                        'max' => 4096,
                    ]),
                ],
            ])
            ->add('name', null, [
                'required' => false,
                'help' => 'Enter your full name',
            ])
            ->add('address', TextType::class, [
                'required' => true,
                'mapped' => false,
                'help' => 'Start typing your city for auto complete'
            ])
            ->add('city',TextType::class,[
                'required' => true,
                'attr' => [
                    'class' => 'locality',
                    'readonly' => true,
                ]
            ])
            ->add('state',TextType::class,[
                'required' => true,
                'attr' => [
                    'class' => 'administrative_area_level_1',
                    'readonly' => true,
                ]
            ])
            ->add('country',TextType::class,[
                'required' =>true,
                'attr' => [
                    'class' => 'country',
                    'readonly' => true,
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
