<?php

namespace App\Form;

use App\Entity\Exercise;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Type;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ExerciseType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', null, [
                'label' => 'Nombre',
                'constraints' => [
                    new NotBlank(),
                    new Type('string'),
                ],
            ])
            ->add('series', null, [
                'label' => 'Series',
                'constraints' => [
                    new NotBlank(),
                    new Type('integer'),
                ],
            ])
            ->add('repetitions', null, [
                'label' => 'Repeticiones',
                'constraints' => [
                    new NotBlank(),
                    new Type('integer'),
                ],
            ])
            /*
            ->add('routine', EntityType::class, [
                'class' => Routine::class,
                'choice_label' => 'id',
            ])
                */
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Exercise::class,
        ]);
    }
}
