<?php

namespace Asynit\Tests\PetStore\Generated\Endpoint;

class GetOrderById extends \Asynit\Tests\PetStore\Generated\Runtime\Client\BaseEndpoint implements \Asynit\Tests\PetStore\Generated\Runtime\Client\Endpoint
{
    protected $orderId;
    protected $accept;
    /**
     * For valid response try integer IDs with value <= 5 or > 10. Other values will generated exceptions
     *
     * @param int $orderId ID of pet that needs to be fetched
     * @param array $accept Accept content header application/xml|application/json
     */
    public function __construct(int $orderId, array $accept = array())
    {
        $this->orderId = $orderId;
        $this->accept = $accept;
    }
    use \Asynit\Tests\PetStore\Generated\Runtime\Client\EndpointTrait;
    public function getMethod() : string
    {
        return 'GET';
    }
    public function getUri() : string
    {
        return str_replace(array('{orderId}'), array($this->orderId), '/store/order/{orderId}');
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
     * @throws \Asynit\Tests\PetStore\Generated\Exception\GetOrderByIdBadRequestException
     * @throws \Asynit\Tests\PetStore\Generated\Exception\GetOrderByIdNotFoundException
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
            throw new \Asynit\Tests\PetStore\Generated\Exception\GetOrderByIdBadRequestException($response);
        }
        if (404 === $status) {
            throw new \Asynit\Tests\PetStore\Generated\Exception\GetOrderByIdNotFoundException($response);
        }
    }
    public function getAuthenticationScopes() : array
    {
        return array();
    }
}