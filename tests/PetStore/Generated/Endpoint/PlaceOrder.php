<?php

namespace Asynit\Tests\PetStore\Generated\Endpoint;

class PlaceOrder extends \Asynit\Tests\PetStore\Generated\Runtime\Client\BaseEndpoint implements \Asynit\Tests\PetStore\Generated\Runtime\Client\Endpoint
{
    protected $accept;
    /**
     * 
     *
     * @param \Asynit\Tests\PetStore\Generated\Model\Order $requestBody 
     * @param array $accept Accept content header application/xml|application/json
     */
    public function __construct(\Asynit\Tests\PetStore\Generated\Model\Order $requestBody, array $accept = array())
    {
        $this->body = $requestBody;
        $this->accept = $accept;
    }
    use \Asynit\Tests\PetStore\Generated\Runtime\Client\EndpointTrait;
    public function getMethod() : string
    {
        return 'POST';
    }
    public function getUri() : string
    {
        return '/store/order';
    }
    public function getBody(\Symfony\Component\Serializer\SerializerInterface $serializer, $streamFactory = null) : array
    {
        if ($this->body instanceof \Asynit\Tests\PetStore\Generated\Model\Order) {
            return array(array('Content-Type' => array('application/json')), $serializer->serialize($this->body, 'json'));
        }
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
     * @throws \Asynit\Tests\PetStore\Generated\Exception\PlaceOrderBadRequestException
     *
     * @return null|\Asynit\Tests\PetStore\Generated\Model\Order
     */
    protected function transformResponseBody(\Psr\Http\Message\ResponseInterface $response, \Symfony\Component\Serializer\SerializerInterface $serializer, ?string $contentType = null)
    {
        $status = $response->getStatusCode();
        $body = (string) $response->getBody();
        if (is_null($contentType) === false && (200 === $status && mb_strpos($contentType, 'application/json') !== false)) {
            return $serializer->deserialize($body, 'Asynit\\Tests\\PetStore\\Generated\\Model\\Order', 'json');
        }
        if (400 === $status) {
            throw new \Asynit\Tests\PetStore\Generated\Exception\PlaceOrderBadRequestException($response);
        }
    }
    public function getAuthenticationScopes() : array
    {
        return array();
    }
}