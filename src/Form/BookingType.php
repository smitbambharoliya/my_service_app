<?php

namespace App\Form;

use App\Entity\Booking;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class BookingType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('bookingDate', DateTimeType::class, [
                'widget' => 'single_text',
                'label' => 'Preferred Date & Time',
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
            ])
            ->add('notes', TextareaType::class, [
                'required' => false,
                'label' => 'Additional Notes (optional)',
                'attr' => ['rows' => 3, 'placeholder' => 'Any specific requirements or address...'],
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
