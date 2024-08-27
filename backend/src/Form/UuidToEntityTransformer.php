<?php

namespace App\Form\DataTransformer;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

class UuidToEntityTransformer implements DataTransformerInterface
{
    private $entityManager;
    private $entityClass;
    private $entityName;

    public function __construct(EntityManagerInterface $entityManager, string $entityClass, string $entityName)
    {
        $this->entityManager = $entityManager;
        $this->entityClass = $entityClass;
        $this->entityName = $entityName;
    }

    public function transform(mixed $value): mixed
    {
        if (null === $value) {
            return '';
        }

        if (! is_object($value) || ! method_exists($value, 'getUuid')) {
            throw new TransformationFailedException(sprintf(
                'Expected an object with a getUuid method. Got: %s',
                is_object($value) ? get_class($value) : gettype($value)
            ));
        }

        return $value->getUuid();
    }

    public function reverseTransform(mixed $value): mixed
    {
        if (! $value) {
            return null;
        }

        $entity = $this->entityManager
            ->getRepository($this->entityClass)
            ->findOneBy(['uuid' => $value]);

        if (null === $entity) {
            throw new TransformationFailedException(sprintf(
                'A %s with UUID "%s" does not exist!',
                $this->entityName,
                $value
            ));
        }

        return $entity;
    }
}
