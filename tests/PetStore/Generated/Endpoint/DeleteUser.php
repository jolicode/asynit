<?php

namespace Asynit\Tests\PetStore\Generated\Endpoint;

class DeleteUser extends \Asynit\Tests\PetStore\Generated\Runtime\Client\BaseEndpoint implements \Asynit\Tests\PetStore\Generated\Runtime\Client\Endpoint
{
    protected $username;
    /**
     * This can only be done by the logged in user.
     *
     * @param string $username The name that needs to be deleted
     */
    public function __construct(string $username)
    {
        $this->username = $username;
    }
    use \Asynit\Tests\PetStore\Generated\Runtime\Client\EndpointTrait;
    public function getMethod() : string
    {
        return 'DELETE';
    }
    public function getUri() : string
    {
        return str_replace(array('{username}'), array($this->username), '/user/{username}');
    }
    public function getBody(\Symfony\Component\Serializer\SerializerInterface $serializer, $streamFactory = null) : array
    {
        return array(array(), null);
    }
    /**
     * {@inheritdoc}
     *
     * @throws \Asynit\Tests\PetStore\Generated\Exception\DeleteUserBadRequestException
     * @throws \Asynit\Tests\PetStore\Generated\Exception\DeleteUserNotFoundException
     *
     * @return null
     */
    protected function transformResponseBody(\Psr\Http\Message\ResponseInterface $response, \Symfony\Component\Serializer\SerializerInterface $serializer, ?string $contentType = null)
    {
        $status = $response->getStatusCode();
        $body = (string) $response->getBody();
        if (400 === $status) {
            throw new \Asynit\Tests\PetStore\Generated\Exception\DeleteUserBadRequestException($response);
        }
        if (404 === $status) {
            throw new \Asynit\Tests\PetStore\Generated\Exception\DeleteUserNotFoundException($response);
        }
    }
    public function getAuthenticationScopes() : array
    {
        return array();
    }
}