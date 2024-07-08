<?php

namespace App\Form;

use App\Entity\Routine;
use App\Entity\User;
use App\Form\ExerciseType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Type;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;


class RoutineType extends AbstractType
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
            ->add('focus', null, [
                'label' => 'Enfoque',
                'constraints' => [
                    new NotBlank(),
                    new Type('string'),
                ],
            ])
            ->add('exercises', CollectionType::class, [
                'entry_type' => ExerciseType::class,
                'entry_options' => ['label' => false],
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'prototype' => true,
                'prototype_name' => '__name__',
            ]);
            /*
            ->add('user', EntityType::class, [
                'class' => User::class,
                'choice_label' => 'id',
            ])*/
        // Add user field only for ROLE_COACH
        if (in_array('ROLE_COACH', $options['user']->getRoles(), true)) {
            $builder
            ->add('assignedTo', EntityType::class, [
                'class' => User::class,
                'choice_label' => 'email', 
                'choices' => $options['users'], 
                'required' => true,
                'label' => 'Asignar a Usuario',
            ]);
        }
        
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Routine::class,
            'user' => null, 
            'users' => [], 
        ]);
    }
}
