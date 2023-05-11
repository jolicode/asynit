<?php

namespace Asynit\Tests\PetStore\Generated\Authentication;

class ApiKeyAuthentication implements \Jane\Component\OpenApiRuntime\Client\AuthenticationPlugin
{
    private $apiKey;
    public function __construct(string $apiKey)
    {
        $this->{'apiKey'} = $apiKey;
    }
    public function authentication(\Psr\Http\Message\RequestInterface $request) : \Psr\Http\Message\RequestInterface
    {
        $request = $request->withHeader('api_key', $this->{'apiKey'});
        return $request;
    }
    public function getScope() : string
    {
        return 'api_key';
    }
}