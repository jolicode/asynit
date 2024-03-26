<?php

namespace Asynit\Tests\PetStore\Generated\Endpoint;

class DeletePet extends \Asynit\Tests\PetStore\Generated\Runtime\Client\BaseEndpoint implements \Asynit\Tests\PetStore\Generated\Runtime\Client\Endpoint
{
    protected $petId;
    /**
     * 
     *
     * @param int $petId Pet id to delete
     * @param array $headerParameters {
     *     @var string $api_key 
     * }
     */
    public function __construct(int $petId, array $headerParameters = array())
    {
        $this->petId = $petId;
        $this->headerParameters = $headerParameters;
    }
    use \Asynit\Tests\PetStore\Generated\Runtime\Client\EndpointTrait;
    public function getMethod() : string
    {
        return 'DELETE';
    }
    public function getUri() : string
    {
        return str_replace(array('{petId}'), array($this->petId), '/pet/{petId}');
    }
    public function getBody(\Symfony\Component\Serializer\SerializerInterface $serializer, $streamFactory = null) : array
    {
        return array(array(), null);
    }
    protected function getHeadersOptionsResolver() : \Symfony\Component\OptionsResolver\OptionsResolver
    {
        $optionsResolver = parent::getHeadersOptionsResolver();
        $optionsResolver->setDefined(array('api_key'));
        $optionsResolver->setRequired(array());
        $optionsResolver->setDefaults(array());
        $optionsResolver->addAllowedTypes('api_key', array('string'));
        return $optionsResolver;
    }
    /**
     * {@inheritdoc}
     *
     * @throws \Asynit\Tests\PetStore\Generated\Exception\DeletePetBadRequestException
     *
     * @return null
     */
    protected function transformResponseBody(\Psr\Http\Message\ResponseInterface $response, \Symfony\Component\Serializer\SerializerInterface $serializer, ?string $contentType = null)
    {
        $status = $response->getStatusCode();
        $body = (string) $response->getBody();
        if (400 === $status) {
            throw new \Asynit\Tests\PetStore\Generated\Exception\DeletePetBadRequestException($response);
        }
    }
    public function getAuthenticationScopes() : array
    {
        return array('petstore_auth');
    }
}