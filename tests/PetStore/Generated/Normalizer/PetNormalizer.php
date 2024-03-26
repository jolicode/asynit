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
class PetNormalizer implements DenormalizerInterface, NormalizerInterface, DenormalizerAwareInterface, NormalizerAwareInterface
{
    use DenormalizerAwareTrait;
    use NormalizerAwareTrait;
    use CheckArray;
    use ValidatorTrait;
    public function supportsDenormalization($data, $type, $format = null, array $context = array()) : bool
    {
        return $type === 'Asynit\\Tests\\PetStore\\Generated\\Model\\Pet';
    }
    public function supportsNormalization($data, $format = null, array $context = array()) : bool
    {
        return is_object($data) && get_class($data) === 'Asynit\\Tests\\PetStore\\Generated\\Model\\Pet';
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
        $object = new \Asynit\Tests\PetStore\Generated\Model\Pet();
        if (null === $data || false === \is_array($data)) {
            return $object;
        }
        if (\array_key_exists('id', $data)) {
            $object->setId($data['id']);
            unset($data['id']);
        }
        if (\array_key_exists('category', $data)) {
            $object->setCategory($this->denormalizer->denormalize($data['category'], 'Asynit\\Tests\\PetStore\\Generated\\Model\\Category', 'json', $context));
            unset($data['category']);
        }
        if (\array_key_exists('name', $data)) {
            $object->setName($data['name']);
            unset($data['name']);
        }
        if (\array_key_exists('photoUrls', $data)) {
            $values = array();
            foreach ($data['photoUrls'] as $value) {
                $values[] = $value;
            }
            $object->setPhotoUrls($values);
            unset($data['photoUrls']);
        }
        if (\array_key_exists('tags', $data)) {
            $values_1 = array();
            foreach ($data['tags'] as $value_1) {
                $values_1[] = $this->denormalizer->denormalize($value_1, 'Asynit\\Tests\\PetStore\\Generated\\Model\\Tag', 'json', $context);
            }
            $object->setTags($values_1);
            unset($data['tags']);
        }
        if (\array_key_exists('status', $data)) {
            $object->setStatus($data['status']);
            unset($data['status']);
        }
        foreach ($data as $key => $value_2) {
            if (preg_match('/.*/', (string) $key)) {
                $object[$key] = $value_2;
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
        if ($object->isInitialized('id') && null !== $object->getId()) {
            $data['id'] = $object->getId();
        }
        if ($object->isInitialized('category') && null !== $object->getCategory()) {
            $data['category'] = $this->normalizer->normalize($object->getCategory(), 'json', $context);
        }
        $data['name'] = $object->getName();
        $values = array();
        foreach ($object->getPhotoUrls() as $value) {
            $values[] = $value;
        }
        $data['photoUrls'] = $values;
        if ($object->isInitialized('tags') && null !== $object->getTags()) {
            $values_1 = array();
            foreach ($object->getTags() as $value_1) {
                $values_1[] = $this->normalizer->normalize($value_1, 'json', $context);
            }
            $data['tags'] = $values_1;
        }
        if ($object->isInitialized('status') && null !== $object->getStatus()) {
            $data['status'] = $object->getStatus();
        }
        foreach ($object as $key => $value_2) {
            if (preg_match('/.*/', (string) $key)) {
                $data[$key] = $value_2;
            }
        }
        return $data;
    }
}