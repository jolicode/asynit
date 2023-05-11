<?php

namespace Asynit\Tests\PetStore\Generated\Endpoint;

class UpdatePetWithForm extends \Asynit\Tests\PetStore\Generated\Runtime\Client\BaseEndpoint implements \Asynit\Tests\PetStore\Generated\Runtime\Client\Endpoint
{
    protected $petId;
    /**
     * 
     *
     * @param int $petId ID of pet that needs to be updated
     * @param null|\Asynit\Tests\PetStore\Generated\Model\Body $requestBody 
     */
    public function __construct(int $petId, ?\Asynit\Tests\PetStore\Generated\Model\Body $requestBody = null)
    {
        $this->petId = $petId;
        $this->body = $requestBody;
    }
    use \Asynit\Tests\PetStore\Generated\Runtime\Client\EndpointTrait;
    public function getMethod() : string
    {
        return 'POST';
    }
    public function getUri() : string
    {
        return str_replace(array('{petId}'), array($this->petId), '/pet/{petId}');
    }
    public function getBody(\Symfony\Component\Serializer\SerializerInterface $serializer, $streamFactory = null) : array
    {
        if ($this->body instanceof \Asynit\Tests\PetStore\Generated\Model\Body) {
            return array(array('Content-Type' => array('application/x-www-form-urlencoded')), http_build_query($serializer->normalize($this->body, 'json')));
        }
        return array(array(), null);
    }
    /**
     * {@inheritdoc}
     *
     * @throws \Asynit\Tests\PetStore\Generated\Exception\UpdatePetWithFormMethodNotAllowedException
     *
     * @return null
     */
    protected function transformResponseBody(\Psr\Http\Message\ResponseInterface $response, \Symfony\Component\Serializer\SerializerInterface $serializer, ?string $contentType = null)
    {
        $status = $response->getStatusCode();
        $body = (string) $response->getBody();
        if (405 === $status) {
            throw new \Asynit\Tests\PetStore\Generated\Exception\UpdatePetWithFormMethodNotAllowedException($response);
        }
    }
    public function getAuthenticationScopes() : array
    {
        return array('petstore_auth');
    }
}