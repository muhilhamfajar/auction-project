<?php

namespace App\Serializer;

use App\Entity\BaseEntity;
use Doctrine\ORM\PersistentCollection;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\SerializerAwareInterface;
use Symfony\Component\Serializer\SerializerInterface;

class CustomEntityNormalizer implements NormalizerInterface, SerializerAwareInterface
{
    private $normalizer;

    public function __construct(ObjectNormalizer $normalizer)
    {
        $this->normalizer = $normalizer;
    }

    public function normalize(mixed $object, string $format = null, array $context = []): array|\ArrayObject|bool|float|int|string|null
    {
        if (! isset($context['visited_objects'])) {
            $context['visited_objects'] = new \SplObjectStorage();
        }

        if ($context['visited_objects']->contains($object)) {
            return $object instanceof BaseEntity ? $object->getUuid() : null;
        }

        $context['visited_objects']->attach($object);

        $context[ObjectNormalizer::CIRCULAR_REFERENCE_HANDLER] = function ($object) {
            return $object instanceof BaseEntity ? $object->getUuid() : null;
        };

        $context[ObjectNormalizer::MAX_DEPTH_HANDLER] = function ($object) {
            return $object instanceof BaseEntity ? $object->getUuid() : null;
        };

        $this->handleEntityRelationships($object, $context);

        $data = $this->normalizer->normalize($object, $format, $context);

        if (is_array($data)) {
            foreach ($data as $key => $value) {
                if ($value instanceof PersistentCollection) {
                    $data[$key] = array_map(function ($item) use ($format, $context) {
                        if ($item instanceof BaseEntity) {
                            return $this->normalize($item, $format, $context);
                        }
                        return null;
                    }, $value->toArray());
                }
            }
        }

        $context['visited_objects']->detach($object);

        return $data;
    }

    private function handleEntityRelationships($object, array &$context): void
    {
        $reflectionClass = new \ReflectionClass($object);

        foreach ($reflectionClass->getProperties() as $property) {
            $propertyType = $property->getType();
            if ($propertyType instanceof \ReflectionNamedType && is_a($propertyType->getName(), PersistentCollection::class, true)) {
                $context['excluded_attributes'][] = $property->getName();
            }
        }
    }

    public function supportsNormalization(mixed $data, string $format = null, array $context = []): bool
    {
        return $data instanceof BaseEntity;
    }

    public function setSerializer(SerializerInterface $serializer): void
    {
        if ($this->normalizer instanceof SerializerAwareInterface) {
            $this->normalizer->setSerializer($serializer);
        }
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            BaseEntity::class => true,
        ];
    }
}
