<?php

namespace App\Form;

use App\Entity\Bid;
use App\Entity\Item;
use App\Entity\User;
use App\Form\DataTransformer\UuidToEntityTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;

class BidType extends AbstractType
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('bidder', TextType::class)
            ->add('item', TextType::class)
            ->add('bidTime', DateTimeType::class, [
                'widget' => 'single_text',
            ])
            ->add('amount', NumberType::class, [
                'constraints' => [
                    new GreaterThanOrEqual([
                        'value' => 0,
                        'message' => 'The amount must be greater than or equal to {{ compared_value }}.',
                    ]),
                ],
            ])
            ->add('isAutoBid', CheckboxType::class, [
                'required' => false,
            ]);

        $builder->get('bidder')->addModelTransformer(
            new UuidToEntityTransformer($this->entityManager, User::class, 'bidder')
        );

        $builder->get('item')->addModelTransformer(
            new UuidToEntityTransformer($this->entityManager, Item::class, 'item')
        );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Bid::class,
            'csrf_protection' => false,
        ]);
    }
}
