<?php

namespace App\Form;

use App\Entity\Booking;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\GreaterThan;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class BookingType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('bookingDate', DateTimeType::class, [
                'widget' => 'single_text',
                'label' => 'Preferred Date & Time',
                'input' => 'datetime_immutable',
                'constraints' => [
                    new NotBlank(['message' => 'Please select a booking date and time.']),
                    new GreaterThan([
                        'value' => 'now',
                        'message' => 'Booking date must be in the future.',
                    ]),
                ],
            ])
            ->add('bookingType', ChoiceType::class, [
                'choices' => [
                    'Book Online' => 'online',
                    'Visit (In-Person)' => 'visit',
                ],
                'expanded' => true,
                'multiple' => false,
                'label' => 'Booking Mode',
                'data' => 'online',
                'constraints' => [
                    new NotBlank(['message' => 'Please select a booking mode.']),
                ],
            ])
            ->add('notes', TextareaType::class, [
                'required' => false,
                'label' => 'Additional Notes (optional)',
                'attr' => ['rows' => 3, 'placeholder' => 'Any specific requirements or address...'],
                'constraints' => [
                    new Length([
                        'max' => 2000,
                        'maxMessage' => 'Notes must not exceed {{ limit }} characters.',
                    ]),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Booking::class,
        ]);
    }
}
