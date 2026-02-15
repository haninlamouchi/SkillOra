<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom')
            ->add('prenom')
            ->add('email')
            ->add('password')
            ->add('roles', ChoiceType::class, [
                'choices' => [
                    'Étudiant' => 'ROLE_ETUDIANT',
                    'Responsable' => 'ROLE_RESPONSABLE',
                ],
                'multiple' => false,
                'expanded' => true,

                'data' => $options['data']->getRoles()[0] ?? null,
            ])
            ->add('datelnscription')
            ->add('telephone')
            ->add('photo')
            ->add('dateNaissance')
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
