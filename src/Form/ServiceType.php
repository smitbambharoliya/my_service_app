<?php

namespace App\Form;

use App\Entity\Service;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\Positive;

class ServiceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'constraints' => [
                    new NotBlank(['message' => 'Service title is required']),
                    new Length([
                        'min' => 3,
                        'max' => 255,
                        'minMessage' => 'Title must be at least 3 characters',
                        'maxMessage' => 'Title must not exceed 255 characters',
                    ])
                ]
            ])
            ->add('description', TextareaType::class, [
                'constraints' => [
                    new NotBlank(['message' => 'Service description is required']),
                    new Length([
                        'min' => 10,
                        'max' => 2000,
                        'minMessage' => 'Description must be at least 10 characters',
                        'maxMessage' => 'Description must not exceed 2000 characters',
                    ])
                ]
            ])
            ->add('price', MoneyType::class, [
                'currency' => 'INR',
                'constraints' => [
                    new NotBlank(['message' => 'Price is required']),
                    new Positive(['message' => 'Price must be greater than 0'])
                ]
            ])
            ->add('category', TextType::class, [
                'constraints' => [
                    new NotBlank(['message' => 'Category is required']),
                    new Length([
                        'min' => 2,
                        'max' => 50,
                        'minMessage' => 'Category must be at least 2 characters'
                    ])
                ]
            ])
            ->add('isPremium', CheckboxType::class, [
                'required' => false
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Service::class,
        ]);
    }
}
