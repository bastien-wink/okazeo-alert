<?php

namespace App\Form;

use App\Entity\Subscription;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SubscriptionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email')
            ->add('frequency', ChoiceType::class, [
                'choices' => [
                    'Une fois par jour' => 24,
                    'Toutes les 12 heures' => 12,
                    'Toutes les heures' => 1,
                ],
            ])
            ->add('filterZipcode')
            ->add('filterRange')
            ->add('filterMinRank')
            ->add('filterMinYear')
            // ->add('excludedGames')
            ->add('save', SubmitType::class);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Subscription::class,
        ]);
    }
}
