<?php

namespace App\Serializer;

use ApiPlatform\JsonLd\Serializer\ItemNormalizer;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class ApiNormalizer implements NormalizerInterface
{
    public function __construct(private ItemNormalizer $normalizer)
    {
    }

    public function normalize(
        mixed $object,
        ?string $format = null,
        array $context = []
    ): array|string|int|float|bool|\ArrayObject|null {
        $data = $this->normalizer->normalize($object, $format, $context);

        if (is_array($data) && array_key_exists('hydra:member', $data)) {
            return $data['hydra:member'];
        }

        return $data;
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $this->normalizer->supportsNormalization($data, $format, $context);
    }

    public function getSupportedTypes(?string $format): array
    {
        return $this->normalizer->getSupportedTypes($format);
    }

    public function hasCacheableSupportsMethod(): bool
    {
        return $this->normalizer->hasCacheableSupportsMethod();
    }
}
