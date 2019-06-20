<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Range;

class CoordinatesType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('latCoord', NumberType::class, [
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Latitude',
                ],
                'constraints' => [
                    new Range([
                        'max' => 90,
                        'min' => -90,
                    ])
                ]
            ])
            ->add('longCoord', NumberType::class, [
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Longitude',
                ],
                'constraints' => [
                    new Range([
                        'max' => 180,
                        'min' => -180,
                    ])
                ]
            ])
        ;
    }

    /*public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            // Configure your form options here
        ]);
    }*/
}
