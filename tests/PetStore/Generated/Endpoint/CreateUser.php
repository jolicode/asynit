<?php

namespace Asynit\Tests\PetStore\Generated\Endpoint;

class CreateUser extends \Asynit\Tests\PetStore\Generated\Runtime\Client\BaseEndpoint implements \Asynit\Tests\PetStore\Generated\Runtime\Client\Endpoint
{
    /**
     * This can only be done by the logged in user.
     *
     * @param \Asynit\Tests\PetStore\Generated\Model\User $requestBody 
     */
    public function __construct(\Asynit\Tests\PetStore\Generated\Model\User $requestBody)
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
        return '/user';
    }
    public function getBody(\Symfony\Component\Serializer\SerializerInterface $serializer, $streamFactory = null) : array
    {
        if ($this->body instanceof \Asynit\Tests\PetStore\Generated\Model\User) {
            return array(array('Content-Type' => array('application/json')), $serializer->serialize($this->body, 'json'));
        }
        return array(array(), null);
    }
    /**
     * {@inheritdoc}
     *
     *
     * @return null
     */
    protected function transformResponseBody(\Psr\Http\Message\ResponseInterface $response, \Symfony\Component\Serializer\SerializerInterface $serializer, ?string $contentType = null)
    {
        $status = $response->getStatusCode();
        $body = (string) $response->getBody();
        return null;
    }
    public function getAuthenticationScopes() : array
    {
        return array();
    }
}