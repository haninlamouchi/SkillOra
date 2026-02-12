<?php

namespace App\Form;

use App\Entity\Commentaire;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class CommentaireType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('contenu', TextareaType::class, [
                'label' => 'Comment',
                'attr' => [
                    'placeholder' => 'Write your comment here...',
                    'rows' => 4,
                    'class' => 'form-control',
                    'minlength' => 5,
                    'maxlength' => 500,
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'comment can not be empty.',
                    ]),
                    new Length([
                        'min' => 5,
                        'minMessage' => 'comment must be at least {{ limit }} characters long.',
                        'max' => 500,
                        'maxMessage' => 'comment cannot be longer than {{ limit }} characters.',
                    ]),
                ],
                'required' => true,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Commentaire::class,
        ]);
    }
}