<?php

namespace Asynit\HttpClient;

use Http\Discovery\Psr17Factory;
use Symfony\Component\Serializer\Encoder\JsonDecode;
use Symfony\Component\Serializer\Encoder\JsonEncode;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Serializer;

trait JaneClientCaseTrait {
    use HttpCreateClientCaseTrait;

    public function __construct() {
        $client = $this->createHttpClient(['Accept' => 'application/json']);
        $factory = new Psr17Factory();

        $normalizers = array(new ArrayDenormalizer(), $this->getNormalizer());
        $serializer = new Serializer($normalizers, array(new JsonEncoder(new JsonEncode(), new JsonDecode(array('json_decode_associative' => true)))));

        parent::__construct($client, $factory, $serializer, $factory);
    }

    abstract protected function getNormalizer(): NormalizerInterface;
}