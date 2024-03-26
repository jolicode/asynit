<?php

namespace Asynit\Tests\PetStore\Generated\Endpoint;

class LoginUser extends \Asynit\Tests\PetStore\Generated\Runtime\Client\BaseEndpoint implements \Asynit\Tests\PetStore\Generated\Runtime\Client\Endpoint
{
    protected $accept;
    /**
     * 
     *
     * @param array $queryParameters {
     *     @var string $username The user name for login
     *     @var string $password The password for login in clear text
     * }
     * @param array $accept Accept content header application/xml|application/json
     */
    public function __construct(array $queryParameters = array(), array $accept = array())
    {
        $this->queryParameters = $queryParameters;
        $this->accept = $accept;
    }
    use \Asynit\Tests\PetStore\Generated\Runtime\Client\EndpointTrait;
    public function getMethod() : string
    {
        return 'GET';
    }
    public function getUri() : string
    {
        return '/user/login';
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
    protected function getQueryOptionsResolver() : \Symfony\Component\OptionsResolver\OptionsResolver
    {
        $optionsResolver = parent::getQueryOptionsResolver();
        $optionsResolver->setDefined(array('username', 'password'));
        $optionsResolver->setRequired(array('username', 'password'));
        $optionsResolver->setDefaults(array());
        $optionsResolver->addAllowedTypes('username', array('string'));
        $optionsResolver->addAllowedTypes('password', array('string'));
        return $optionsResolver;
    }
    /**
     * {@inheritdoc}
     *
     * @throws \Asynit\Tests\PetStore\Generated\Exception\LoginUserBadRequestException
     *
     * @return null
     */
    protected function transformResponseBody(\Psr\Http\Message\ResponseInterface $response, \Symfony\Component\Serializer\SerializerInterface $serializer, ?string $contentType = null)
    {
        $status = $response->getStatusCode();
        $body = (string) $response->getBody();
        if (is_null($contentType) === false && (200 === $status && mb_strpos($contentType, 'application/json') !== false)) {
            return json_decode($body);
        }
        if (400 === $status) {
            throw new \Asynit\Tests\PetStore\Generated\Exception\LoginUserBadRequestException($response);
        }
    }
    public function getAuthenticationScopes() : array
    {
        return array();
    }
}