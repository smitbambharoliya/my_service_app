<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\LessThan;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'label' => 'Email Address',
                'attr' => ['placeholder' => 'Enter your email address'],
                'constraints' => [
                    new NotBlank(['message' => 'Email address is required.']),
                    new Email(['message' => 'Please enter a valid email address.']),
                    new Length(['max' => 180, 'maxMessage' => 'Email must not exceed {{ limit }} characters.']),
                ],
            ])
            ->add('fullName', TextType::class, [
                'label' => 'Full Name',
                'attr' => ['placeholder' => 'Enter Your Full Name'],
                'constraints' => [
                    new NotBlank(['message' => 'Full name is required.']),
                    new Length([
                        'min' => 2,
                        'max' => 255,
                        'minMessage' => 'Name must be at least {{ limit }} characters.',
                        'maxMessage' => 'Name must not exceed {{ limit }} characters.',
                    ]),
                    new Regex([
                        'pattern' => '/^[a-zA-Z\s\-\']+$/',
                        'message' => 'Name should only contain letters, spaces and hyphens.',
                    ]),
                ],
            ])
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'mapped' => false,
                'first_options'  => ['label' => 'Password', 'attr' => ['autocomplete' => 'new-password']],
                'second_options' => ['label' => 'Repeat Password', 'attr' => ['autocomplete' => 'new-password']],
                'invalid_message' => 'The password fields must match.',
                'constraints' => [
                    new NotBlank(['message' => 'Please enter a password.']),
                    new Length([
                        'min' => 6,
                        'max' => 4096,
                        'minMessage' => 'Password must be at least {{ limit }} characters.',
                    ]),
                    new Regex([
                        'pattern' => '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/',
                        'message' => 'Password must contain at least one uppercase letter, one lowercase letter and one number.',
                    ]),
                ],
            ])
            ->add('roles', ChoiceType::class, [
                'choices' => [
                    'Sovereign Client (Customer)' => 'ROLE_USER',
                    'Heritage Artisan (Provider)' => 'ROLE_PROVIDER',
                ],
                'expanded' => false,
                'multiple' => false,
                'mapped' => false,
                'label' => 'Register as:',
                'attr' => ['class' => 'auth-input'],
                'constraints' => [
                    new NotBlank(['message' => 'Please select a role.']),
                ],
            ])
            ->add('gender', ChoiceType::class, [
                'choices' => [
                    'Male' => 'Male',
                    'Female' => 'Female',
                    'Other' => 'Other',
                ],
                'label' => 'Gender',
                'constraints' => [
                    new NotBlank(['message' => 'Please select your gender.']),
                ],
            ])
            // MOBILE NAAM RAKHYU CHE JETHI ENTITY SATHE MATCH THAY
            ->add('mobile', TextType::class, [
                'label' => 'Mobile number',
                'attr' => ['class' => 'form-control', 'placeholder'=> 'Enter 10 Digit Mobile Number'],
                'constraints' => [
                    new NotBlank(['message' => 'Mobile number is required.']),
                    new Length([
                        'min' => 10,
                        'max' => 10,
                        'exactMessage' => 'Mobile number must be exactly {{ limit }} digits.',
                    ]),
                    new Regex([
                        'pattern' => '/^[0-9]{10}$/',
                        'message' => 'Please enter a valid 10-digit mobile number.',
                    ]),
                ],
            ])
            ->add('dot', DateType::class, [
                'label' => 'Date of Birth',
                'widget' => 'single_text',
                'required' => false,
                'constraints' => [
                    new LessThan([
                        'value' => 'today',
                        'message' => 'Date of birth must be in the past.',
                    ]),
                ],
            ])
            ->add('address', TextType::class, [
                'required' => false,
                'constraints' => [
                    new Length([
                        'max' => 500,
                        'maxMessage' => 'Address must not exceed {{ limit }} characters.',
                    ]),
                ],
            ])
            ->add('city', TextType::class, [
                'required' => false,
                'constraints' => [
                    new Length([
                        'max' => 40,
                        'maxMessage' => 'City name must not exceed {{ limit }} characters.',
                    ]),
                    new Regex([
                        'pattern' => '/^[a-zA-Z\s\-]*$/',
                        'message' => 'City should only contain letters, spaces and hyphens.',
                    ]),
                ],
            ])
            ->add('pincode', TextType::class, [
                'required' => false,
                'constraints' => [
                    new Length([
                        'min' => 6,
                        'max' => 6,
                        'exactMessage' => 'Pincode must be exactly {{ limit }} digits.',
                    ]),
                    new Regex([
                        'pattern' => '/^[0-9]{6}$/',
                        'message' => 'Please enter a valid 6-digit pincode.',
                    ]),
                ],
            ])
            ->add('agreeTerms', CheckboxType::class, [
                'mapped' => false,
                'constraints' => [
                    new IsTrue(['message' => 'You must agree to the terms and conditions.']),
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