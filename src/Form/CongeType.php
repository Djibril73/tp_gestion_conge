<?php
// src/Form/CongeType.php

namespace App\Form;

use App\Entity\Conge;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CongeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if ($options['include_user']) {
            $builder->add('user', EntityType::class, [
                'class' => User::class,
                'choice_label' => function (User $user) {
                    return $user->getPrenom() . ' ' . $user->getNom();
                },
                'placeholder' => 'Sélectionnez un utilisateur',
                'required' => true,
            ]);
        }
        
        $builder
            ->add('type', ChoiceType::class, [
                'choices' => [
                    'Congé annuel' => 'Congé annuel',
                    'Congé maladie' => 'Congé maladie',
                    'Congé maternité/paternité' => 'Congé maternité/paternité',
                    'Congé sans solde' => 'Congé sans solde',
                ],
            ])
            ->add('dateDebut', DateType::class, [
                'widget' => 'single_text',
            ])
            ->add('dateFin', DateType::class, [
                'widget' => 'single_text',
            ])
            ->add('statut', ChoiceType::class, [
                'choices' => [
                    'En attente' => 'en_attente',
                    'Approuvé' => 'approuve',
                    'Refusé' => 'refuse',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Conge::class,
            'include_user' => false,
        ]);
    }
}