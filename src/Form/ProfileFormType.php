<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

class ProfileFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('fullName', TextType::class, [
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
            ->add('email', EmailType::class, [
                'constraints' => [
                    new NotBlank(['message' => 'Email is required.']),
                    new Email(['message' => 'Please enter a valid email address.']),
                    new Length(['max' => 180, 'maxMessage' => 'Email must not exceed {{ limit }} characters.']),
                ],
            ])
            ->add('phoneNumber', TextType::class, [
                'property_path' => 'mobile',
                'required' => false,
                'constraints' => [
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
            ->add('bookingInAppNotifications', CheckboxType::class, [
                'required' => false,
            ])
            ->add('bookingEmailNotifications', CheckboxType::class, [
                'required' => false,
            ])
            ->add('messageInAppNotifications', CheckboxType::class, [
                'required' => false,
            ])
            ->add('messageEmailNotifications', CheckboxType::class, [
                'required' => false,
            ])
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'mapped' => false,
                'required' => false,
                'first_options'  => ['label' => 'New Password', 'attr' => ['autocomplete' => 'new-password']],
                'second_options' => ['label' => 'Repeat Password', 'attr' => ['autocomplete' => 'new-password']],
                'invalid_message' => 'The password fields must match.',
                'constraints' => [
                    new Length([
                        'min' => 6,
                        'max' => 4096,
                        'minMessage' => 'Password must be at least {{ limit }} characters.',
                    ]),
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
