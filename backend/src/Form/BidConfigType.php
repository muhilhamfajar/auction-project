<?php

namespace App\Form;

use App\Entity\BidConfig;
use App\Entity\User;
use App\Form\DataTransformer\UuidToEntityTransformer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Range;

class BidConfigType extends AbstractType
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('user', TextType::class)
            ->add('maxBidAmount', NumberType::class, [
                'scale' => 2,
                'constraints' => [
                    new NotBlank(),
                    new Range(['min' => 0]),
                ],
            ])
            ->add('bidAlertPercentage', IntegerType::class, [
                'required' => false,
                'constraints' => [
                    new Range(['min' => 0, 'max' => 100]),
                ],
            ]);
            // ->add('reservedAmount', NumberType::class, [
            //     'scale' => 2,
            //     'required' => false,
            //     'constraints' => [
            //         new Range(['min' => 0]),
            //     ],
            // ]);

            $builder->get('user')->addModelTransformer(
                new UuidToEntityTransformer($this->entityManager, User::class, 'user')
            );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => BidConfig::class,
            'csrf_protection' => false,
        ]);
    }
}
