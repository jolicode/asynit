<?php

namespace Asynit\Tests\PetStore\Generated\Endpoint;

class GetPetById extends \Asynit\Tests\PetStore\Generated\Runtime\Client\BaseEndpoint implements \Asynit\Tests\PetStore\Generated\Runtime\Client\Endpoint
{
    protected $petId;
    protected $accept;
    /**
     * Returns a single pet
     *
     * @param int $petId ID of pet to return
     * @param array $accept Accept content header application/xml|application/json
     */
    public function __construct(int $petId, array $accept = array())
    {
        $this->petId = $petId;
        $this->accept = $accept;
    }
    use \Asynit\Tests\PetStore\Generated\Runtime\Client\EndpointTrait;
    public function getMethod() : string
    {
        return 'GET';
    }
    public function getUri() : string
    {
        return str_replace(array('{petId}'), array($this->petId), '/pet/{petId}');
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
     * @throws \Asynit\Tests\PetStore\Generated\Exception\GetPetByIdBadRequestException
     * @throws \Asynit\Tests\PetStore\Generated\Exception\GetPetByIdNotFoundException
     *
     * @return null|\Asynit\Tests\PetStore\Generated\Model\Pet
     */
    protected function transformResponseBody(\Psr\Http\Message\ResponseInterface $response, \Symfony\Component\Serializer\SerializerInterface $serializer, ?string $contentType = null)
    {
        $status = $response->getStatusCode();
        $body = (string) $response->getBody();
        if (is_null($contentType) === false && (200 === $status && mb_strpos($contentType, 'application/json') !== false)) {
            return $serializer->deserialize($body, 'Asynit\\Tests\\PetStore\\Generated\\Model\\Pet', 'json');
        }
        if (400 === $status) {
            throw new \Asynit\Tests\PetStore\Generated\Exception\GetPetByIdBadRequestException($response);
        }
        if (404 === $status) {
            throw new \Asynit\Tests\PetStore\Generated\Exception\GetPetByIdNotFoundException($response);
        }
    }
    public function getAuthenticationScopes() : array
    {
        return array('api_key');
    }
}