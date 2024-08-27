<?php

namespace App\Form;

use App\Entity\AutoBid;
use App\Entity\Item;
use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use App\Form\DataTransformer\UuidToEntityTransformer;
use Doctrine\ORM\EntityManagerInterface;

class AutoBidType extends AbstractType
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('user', TextType::class)
            ->add('item', TextType::class);

            $builder->get('user')->addModelTransformer(
                new UuidToEntityTransformer($this->entityManager, User::class, 'user')
            );

            $builder->get('item')->addModelTransformer(
                new UuidToEntityTransformer($this->entityManager, Item::class, 'item')
            );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => AutoBid::class,
            'csrf_protection' => false,
        ]);
    }
}
