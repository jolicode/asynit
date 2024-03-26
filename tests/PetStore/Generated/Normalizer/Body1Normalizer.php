<?php

namespace Asynit\Tests\PetStore\Generated\Normalizer;

use Jane\Component\JsonSchemaRuntime\Reference;
use Asynit\Tests\PetStore\Generated\Runtime\Normalizer\CheckArray;
use Asynit\Tests\PetStore\Generated\Runtime\Normalizer\ValidatorTrait;
use Symfony\Component\Serializer\Exception\InvalidArgumentException;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
class Body1Normalizer implements DenormalizerInterface, NormalizerInterface, DenormalizerAwareInterface, NormalizerAwareInterface
{
    use DenormalizerAwareTrait;
    use NormalizerAwareTrait;
    use CheckArray;
    use ValidatorTrait;
    public function supportsDenormalization($data, $type, $format = null, array $context = array()) : bool
    {
        return $type === 'Asynit\\Tests\\PetStore\\Generated\\Model\\Body1';
    }
    public function supportsNormalization($data, $format = null, array $context = array()) : bool
    {
        return is_object($data) && get_class($data) === 'Asynit\\Tests\\PetStore\\Generated\\Model\\Body1';
    }
    /**
     * @return mixed
     */
    public function denormalize($data, $class, $format = null, array $context = array())
    {
        if (isset($data['$ref'])) {
            return new Reference($data['$ref'], $context['document-origin']);
        }
        if (isset($data['$recursiveRef'])) {
            return new Reference($data['$recursiveRef'], $context['document-origin']);
        }
        $object = new \Asynit\Tests\PetStore\Generated\Model\Body1();
        if (null === $data || false === \is_array($data)) {
            return $object;
        }
        if (\array_key_exists('additionalMetadata', $data)) {
            $object->setAdditionalMetadata($data['additionalMetadata']);
            unset($data['additionalMetadata']);
        }
        if (\array_key_exists('file', $data)) {
            $object->setFile($data['file']);
            unset($data['file']);
        }
        foreach ($data as $key => $value) {
            if (preg_match('/.*/', (string) $key)) {
                $object[$key] = $value;
            }
        }
        return $object;
    }
    /**
     * @return array|string|int|float|bool|\ArrayObject|null
     */
    public function normalize($object, $format = null, array $context = array())
    {
        $data = array();
        if ($object->isInitialized('additionalMetadata') && null !== $object->getAdditionalMetadata()) {
            $data['additionalMetadata'] = $object->getAdditionalMetadata();
        }
        if ($object->isInitialized('file') && null !== $object->getFile()) {
            $data['file'] = $object->getFile();
        }
        foreach ($object as $key => $value) {
            if (preg_match('/.*/', (string) $key)) {
                $data[$key] = $value;
            }
        }
        return $data;
    }
}