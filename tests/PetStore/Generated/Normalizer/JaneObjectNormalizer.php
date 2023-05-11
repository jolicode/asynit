<?php

namespace Asynit\Tests\PetStore\Generated\Normalizer;

use Asynit\Tests\PetStore\Generated\Runtime\Normalizer\CheckArray;
use Asynit\Tests\PetStore\Generated\Runtime\Normalizer\ValidatorTrait;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
class JaneObjectNormalizer implements DenormalizerInterface, NormalizerInterface, DenormalizerAwareInterface, NormalizerAwareInterface
{
    use DenormalizerAwareTrait;
    use NormalizerAwareTrait;
    use CheckArray;
    use ValidatorTrait;
    protected $normalizers = array('Asynit\\Tests\\PetStore\\Generated\\Model\\Order' => 'Asynit\\Tests\\PetStore\\Generated\\Normalizer\\OrderNormalizer', 'Asynit\\Tests\\PetStore\\Generated\\Model\\Category' => 'Asynit\\Tests\\PetStore\\Generated\\Normalizer\\CategoryNormalizer', 'Asynit\\Tests\\PetStore\\Generated\\Model\\User' => 'Asynit\\Tests\\PetStore\\Generated\\Normalizer\\UserNormalizer', 'Asynit\\Tests\\PetStore\\Generated\\Model\\Tag' => 'Asynit\\Tests\\PetStore\\Generated\\Normalizer\\TagNormalizer', 'Asynit\\Tests\\PetStore\\Generated\\Model\\Pet' => 'Asynit\\Tests\\PetStore\\Generated\\Normalizer\\PetNormalizer', 'Asynit\\Tests\\PetStore\\Generated\\Model\\ApiResponse' => 'Asynit\\Tests\\PetStore\\Generated\\Normalizer\\ApiResponseNormalizer', 'Asynit\\Tests\\PetStore\\Generated\\Model\\Body' => 'Asynit\\Tests\\PetStore\\Generated\\Normalizer\\BodyNormalizer', 'Asynit\\Tests\\PetStore\\Generated\\Model\\Body1' => 'Asynit\\Tests\\PetStore\\Generated\\Normalizer\\Body1Normalizer', '\\Jane\\Component\\JsonSchemaRuntime\\Reference' => '\\Asynit\\Tests\\PetStore\\Generated\\Runtime\\Normalizer\\ReferenceNormalizer'), $normalizersCache = array();
    public function supportsDenormalization($data, $type, $format = null, array $context = array()) : bool
    {
        return array_key_exists($type, $this->normalizers);
    }
    public function supportsNormalization($data, $format = null, array $context = array()) : bool
    {
        return is_object($data) && array_key_exists(get_class($data), $this->normalizers);
    }
    /**
     * @return array|string|int|float|bool|\ArrayObject|null
     */
    public function normalize($object, $format = null, array $context = array())
    {
        $normalizerClass = $this->normalizers[get_class($object)];
        $normalizer = $this->getNormalizer($normalizerClass);
        return $normalizer->normalize($object, $format, $context);
    }
    /**
     * @return mixed
     */
    public function denormalize($data, $class, $format = null, array $context = array())
    {
        $denormalizerClass = $this->normalizers[$class];
        $denormalizer = $this->getNormalizer($denormalizerClass);
        return $denormalizer->denormalize($data, $class, $format, $context);
    }
    private function getNormalizer(string $normalizerClass)
    {
        return $this->normalizersCache[$normalizerClass] ?? $this->initNormalizer($normalizerClass);
    }
    private function initNormalizer(string $normalizerClass)
    {
        $normalizer = new $normalizerClass();
        $normalizer->setNormalizer($this->normalizer);
        $normalizer->setDenormalizer($this->denormalizer);
        $this->normalizersCache[$normalizerClass] = $normalizer;
        return $normalizer;
    }
}