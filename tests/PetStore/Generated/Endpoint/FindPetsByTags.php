<?php

namespace Asynit\Tests\PetStore\Generated\Endpoint;

class FindPetsByTags extends \Asynit\Tests\PetStore\Generated\Runtime\Client\BaseEndpoint implements \Asynit\Tests\PetStore\Generated\Runtime\Client\Endpoint
{
    protected $accept;
    /**
     * Multiple tags can be provided with comma separated strings. Use tag1, tag2, tag3 for testing.
     *
     * @param array $queryParameters {
     *     @var array $tags Tags to filter by
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
        return '/pet/findByTags';
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
        $optionsResolver->setDefined(array('tags'));
        $optionsResolver->setRequired(array('tags'));
        $optionsResolver->setDefaults(array());
        $optionsResolver->addAllowedTypes('tags', array('array'));
        return $optionsResolver;
    }
    /**
     * {@inheritdoc}
     *
     * @throws \Asynit\Tests\PetStore\Generated\Exception\FindPetsByTagsBadRequestException
     *
     * @return null|\Asynit\Tests\PetStore\Generated\Model\Pet[]
     */
    protected function transformResponseBody(\Psr\Http\Message\ResponseInterface $response, \Symfony\Component\Serializer\SerializerInterface $serializer, ?string $contentType = null)
    {
        $status = $response->getStatusCode();
        $body = (string) $response->getBody();
        if (is_null($contentType) === false && (200 === $status && mb_strpos($contentType, 'application/json') !== false)) {
            return $serializer->deserialize($body, 'Asynit\\Tests\\PetStore\\Generated\\Model\\Pet[]', 'json');
        }
        if (400 === $status) {
            throw new \Asynit\Tests\PetStore\Generated\Exception\FindPetsByTagsBadRequestException($response);
        }
    }
    public function getAuthenticationScopes() : array
    {
        return array('petstore_auth');
    }
}