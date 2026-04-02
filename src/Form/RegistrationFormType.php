<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email')
            ->add('fullName', TextType::class, [
                'label' => 'Full Name',
                'attr' => ['placeholder' => 'Enter Your Full Name']
            ])
            ->add('plainPassword', PasswordType::class, [
                'mapped' => false,
                'attr' => ['autocomplete' => 'new-password'],
                'constraints' => [
                    new NotBlank(['message' => 'Please enter a password']),
                    new Length(['min' => 6, 'max' => 4096]),
                ],
            ])
            ->add('roles', ChoiceType::class, [
                'choices' => [
                    'Customer' => 'ROLE_USER',
                    'Service Provider' => 'ROLE_PROVIDER',
                ],
                'expanded' => false,
                'multiple' => true,
                'label' => 'Register as:'
            ])
            ->add('gender', ChoiceType::class, [
                'choices' => [
                    'Male' => 'Male',
                    'Female' => 'Female',
                    'Other' => 'Other',
                ],
                'label' => 'Gender'
            ])
            // MOBILE NAAM RAKHYU CHE JETHI ENTITY SATHE MATCH THAY
            ->add('mobile', TextType::class, [
                'label' => 'Mobile number',
                'attr' => ['class' => 'form-control', 'placeholder'=> 'Enter 10 Digit Mobile Number'],
                'constraints' => [
                    new Length([
                        'min' => 10,
                        'max' => 10,
                        'exactMessage' => 'Please enter 10 digits.',
                    ]),
                    new Regex([
                        'pattern' => '/^[0-9]+$/',
                        'message' => 'Please enter a valid mobile number.',
                    ])
                ]
            ])
            ->add('dot', DateType::class, [
                'label' => 'Date of Birth',
                'widget' => 'single_text',
                'required' => false
            ])
            ->add('address', TextType::class, ['required' => false])
            ->add('city', TextType::class, ['required' => false])
            ->add('pincode', TextType::class, ['required' => false])
            ->add('agreeTerms', CheckboxType::class, [
                'mapped' => false,
                'constraints' => [
                    new IsTrue(['message' => 'You should agree to our terms.']),
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