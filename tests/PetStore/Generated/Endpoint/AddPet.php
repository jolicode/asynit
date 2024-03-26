<?php

namespace Asynit\Tests\PetStore\Generated\Endpoint;

class AddPet extends \Asynit\Tests\PetStore\Generated\Runtime\Client\BaseEndpoint implements \Asynit\Tests\PetStore\Generated\Runtime\Client\Endpoint
{
    /**
     * 
     *
     * @param \Asynit\Tests\PetStore\Generated\Model\Pet $requestBody 
     */
    public function __construct(\Asynit\Tests\PetStore\Generated\Model\Pet $requestBody)
    {
        $this->body = $requestBody;
    }
    use \Asynit\Tests\PetStore\Generated\Runtime\Client\EndpointTrait;
    public function getMethod() : string
    {
        return 'POST';
    }
    public function getUri() : string
    {
        return '/pet';
    }
    public function getBody(\Symfony\Component\Serializer\SerializerInterface $serializer, $streamFactory = null) : array
    {
        if ($this->body instanceof \Asynit\Tests\PetStore\Generated\Model\Pet) {
            return array(array('Content-Type' => array('application/json')), $serializer->serialize($this->body, 'json'));
        }
        if ($this->body instanceof \Asynit\Tests\PetStore\Generated\Model\Pet) {
            return array(array('Content-Type' => array('application/xml')), $this->body);
        }
        return array(array(), null);
    }
    /**
     * {@inheritdoc}
     *
     * @throws \Asynit\Tests\PetStore\Generated\Exception\AddPetMethodNotAllowedException
     *
     * @return null
     */
    protected function transformResponseBody(\Psr\Http\Message\ResponseInterface $response, \Symfony\Component\Serializer\SerializerInterface $serializer, ?string $contentType = null)
    {
        $status = $response->getStatusCode();
        $body = (string) $response->getBody();
        if (405 === $status) {
            throw new \Asynit\Tests\PetStore\Generated\Exception\AddPetMethodNotAllowedException($response);
        }
    }
    public function getAuthenticationScopes() : array
    {
        return array('petstore_auth');
    }
}