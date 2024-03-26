<?php

namespace Asynit\Tests\PetStore\Generated\Endpoint;

class UpdatePet extends \Asynit\Tests\PetStore\Generated\Runtime\Client\BaseEndpoint implements \Asynit\Tests\PetStore\Generated\Runtime\Client\Endpoint
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
        return 'PUT';
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
     * @throws \Asynit\Tests\PetStore\Generated\Exception\UpdatePetBadRequestException
     * @throws \Asynit\Tests\PetStore\Generated\Exception\UpdatePetNotFoundException
     * @throws \Asynit\Tests\PetStore\Generated\Exception\UpdatePetMethodNotAllowedException
     *
     * @return null
     */
    protected function transformResponseBody(\Psr\Http\Message\ResponseInterface $response, \Symfony\Component\Serializer\SerializerInterface $serializer, ?string $contentType = null)
    {
        $status = $response->getStatusCode();
        $body = (string) $response->getBody();
        if (400 === $status) {
            throw new \Asynit\Tests\PetStore\Generated\Exception\UpdatePetBadRequestException($response);
        }
        if (404 === $status) {
            throw new \Asynit\Tests\PetStore\Generated\Exception\UpdatePetNotFoundException($response);
        }
        if (405 === $status) {
            throw new \Asynit\Tests\PetStore\Generated\Exception\UpdatePetMethodNotAllowedException($response);
        }
    }
    public function getAuthenticationScopes() : array
    {
        return array('petstore_auth');
    }
}