<?php

namespace App\Form;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;

class UserType extends AbstractType
{
    private UserRepository $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'constraints' => [
                    new NotBlank(),
                    new Email(),
                ],
            ])
            ->add('roles', ChoiceType::class, [
                'choices' => [
                    'User' => 'ROLE_USER',
                    'Coach' => 'ROLE_COACH',
                ],
                'multiple' => true,
                'expanded' => true,
                'data' => ['ROLE_USER'],
            ])
            ->add('password', PasswordType::class)
        ;

        // Add a listener to conditionally add the 'coach' field
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            $form = $event->getForm();
            /** @var User $user */
            $user = $event->getData();

            $this->addCoachField($form, $user->getRoles());
        });

        /***  Add a listener to handle changes in roles
        $builder->get('roles')->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
            $form = $event->getForm()->getParent();
            $roles = $event->getForm()->getData();
            $this->addCoachField($form, $roles);
        });
        */
    }

    private function addCoachField(FormInterface $form, array $roles): void
    {
        if (in_array('ROLE_USER', $roles)) {
            $form->add('coach', EntityType::class, [
                'class' => User::class,
                'choice_label' => 'email',
                'query_builder' => function (UserRepository $userRepository) {
                    return $userRepository->createQueryBuilder('u')
                        ->andWhere('u.roles LIKE :role')
                        ->setParameter('role', '%"ROLE_COACH"%');
                },
                'required' => false,
                'placeholder' => 'Choose a coach',
            ]);
        } else {
            $form->remove('coach');
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}