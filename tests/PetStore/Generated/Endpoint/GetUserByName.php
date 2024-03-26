<?php

namespace Asynit\Tests\PetStore\Generated\Endpoint;

class GetUserByName extends \Asynit\Tests\PetStore\Generated\Runtime\Client\BaseEndpoint implements \Asynit\Tests\PetStore\Generated\Runtime\Client\Endpoint
{
    protected $username;
    protected $accept;
    /**
     * 
     *
     * @param string $username The name that needs to be fetched. Use user1 for testing.
     * @param array $accept Accept content header application/xml|application/json
     */
    public function __construct(string $username, array $accept = array())
    {
        $this->username = $username;
        $this->accept = $accept;
    }
    use \Asynit\Tests\PetStore\Generated\Runtime\Client\EndpointTrait;
    public function getMethod() : string
    {
        return 'GET';
    }
    public function getUri() : string
    {
        return str_replace(array('{username}'), array($this->username), '/user/{username}');
    }
    public function getBody(\Symfony\Component\Serializer\SerializerInterface $serializer, $streamFactory = null) : array
    {
        return array(array(), null);
    }
    public function getExtraHeaders() : array
    {
        if (empty($this->accept)) {
            return array('Accept' => array('application/xml', 'application/json'));
        }
        return $this->accept;
    }
    /**
     * {@inheritdoc}
     *
     * @throws \Asynit\Tests\PetStore\Generated\Exception\GetUserByNameBadRequestException
     * @throws \Asynit\Tests\PetStore\Generated\Exception\GetUserByNameNotFoundException
     *
     * @return null|\Asynit\Tests\PetStore\Generated\Model\User
     */
    protected function transformResponseBody(\Psr\Http\Message\ResponseInterface $response, \Symfony\Component\Serializer\SerializerInterface $serializer, ?string $contentType = null)
    {
        $status = $response->getStatusCode();
        $body = (string) $response->getBody();
        if (is_null($contentType) === false && (200 === $status && mb_strpos($contentType, 'application/json') !== false)) {
            return $serializer->deserialize($body, 'Asynit\\Tests\\PetStore\\Generated\\Model\\User', 'json');
        }
        if (400 === $status) {
            throw new \Asynit\Tests\PetStore\Generated\Exception\GetUserByNameBadRequestException($response);
        }
        if (404 === $status) {
            throw new \Asynit\Tests\PetStore\Generated\Exception\GetUserByNameNotFoundException($response);
        }
    }
    public function getAuthenticationScopes() : array
    {
        return array();
    }
}