<?php

namespace Asynit\Tests\PetStore\Generated\Endpoint;

class UploadFile extends \Asynit\Tests\PetStore\Generated\Runtime\Client\BaseEndpoint implements \Asynit\Tests\PetStore\Generated\Runtime\Client\Endpoint
{
    protected $petId;
    /**
     * 
     *
     * @param int $petId ID of pet to update
     * @param null|\Asynit\Tests\PetStore\Generated\Model\Body1 $requestBody 
     */
    public function __construct(int $petId, ?\Asynit\Tests\PetStore\Generated\Model\Body1 $requestBody = null)
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
        return str_replace(array('{petId}'), array($this->petId), '/pet/{petId}/uploadImage');
    }
    public function getBody(\Symfony\Component\Serializer\SerializerInterface $serializer, $streamFactory = null) : array
    {
        if ($this->body instanceof \Asynit\Tests\PetStore\Generated\Model\Body1) {
            $bodyBuilder = new \Http\Message\MultipartStream\MultipartStreamBuilder($streamFactory);
            $formParameters = $serializer->normalize($this->body, 'json');
            foreach ($formParameters as $key => $value) {
                $value = is_int($value) ? (string) $value : $value;
                $bodyBuilder->addResource($key, $value);
            }
            return array(array('Content-Type' => array('multipart/form-data; boundary="' . ($bodyBuilder->getBoundary() . '"'))), $bodyBuilder->build());
        }
        return array(array(), null);
    }
    public function getExtraHeaders() : array
    {
        return array('Accept' => array('application/json'));
    }
    /**
     * {@inheritdoc}
     *
     *
     * @return null|\Asynit\Tests\PetStore\Generated\Model\ApiResponse
     */
    protected function transformResponseBody(\Psr\Http\Message\ResponseInterface $response, \Symfony\Component\Serializer\SerializerInterface $serializer, ?string $contentType = null)
    {
        $status = $response->getStatusCode();
        $body = (string) $response->getBody();
        if (is_null($contentType) === false && (200 === $status && mb_strpos($contentType, 'application/json') !== false)) {
            return $serializer->deserialize($body, 'Asynit\\Tests\\PetStore\\Generated\\Model\\ApiResponse', 'json');
        }
    }
    public function getAuthenticationScopes() : array
    {
        return array('petstore_auth');
    }
}