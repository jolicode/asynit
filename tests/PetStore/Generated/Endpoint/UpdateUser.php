<?php

namespace Asynit\Tests\PetStore\Generated\Endpoint;

class UpdateUser extends \Asynit\Tests\PetStore\Generated\Runtime\Client\BaseEndpoint implements \Asynit\Tests\PetStore\Generated\Runtime\Client\Endpoint
{
    protected $username;
    /**
     * This can only be done by the logged in user.
     *
     * @param string $username name that need to be deleted
     * @param \Asynit\Tests\PetStore\Generated\Model\User $requestBody 
     */
    public function __construct(string $username, \Asynit\Tests\PetStore\Generated\Model\User $requestBody)
    {
        $this->username = $username;
        $this->body = $requestBody;
    }
    use \Asynit\Tests\PetStore\Generated\Runtime\Client\EndpointTrait;
    public function getMethod() : string
    {
        return 'PUT';
    }
    public function getUri() : string
    {
        return str_replace(array('{username}'), array($this->username), '/user/{username}');
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
     * @throws \Asynit\Tests\PetStore\Generated\Exception\UpdateUserBadRequestException
     * @throws \Asynit\Tests\PetStore\Generated\Exception\UpdateUserNotFoundException
     *
     * @return null
     */
    protected function transformResponseBody(\Psr\Http\Message\ResponseInterface $response, \Symfony\Component\Serializer\SerializerInterface $serializer, ?string $contentType = null)
    {
        $status = $response->getStatusCode();
        $body = (string) $response->getBody();
        if (400 === $status) {
            throw new \Asynit\Tests\PetStore\Generated\Exception\UpdateUserBadRequestException($response);
        }
        if (404 === $status) {
            throw new \Asynit\Tests\PetStore\Generated\Exception\UpdateUserNotFoundException($response);
        }
    }
    public function getAuthenticationScopes() : array
    {
        return array();
    }
}