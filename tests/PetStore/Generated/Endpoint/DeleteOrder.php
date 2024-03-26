<?php

namespace Asynit\Tests\PetStore\Generated\Endpoint;

class DeleteOrder extends \Asynit\Tests\PetStore\Generated\Runtime\Client\BaseEndpoint implements \Asynit\Tests\PetStore\Generated\Runtime\Client\Endpoint
{
    protected $orderId;
    /**
     * For valid response try integer IDs with value < 1000. Anything above 1000 or nonintegers will generate API errors
     *
     * @param string $orderId ID of the order that needs to be deleted
     */
    public function __construct(string $orderId)
    {
        $this->orderId = $orderId;
    }
    use \Asynit\Tests\PetStore\Generated\Runtime\Client\EndpointTrait;
    public function getMethod() : string
    {
        return 'DELETE';
    }
    public function getUri() : string
    {
        return str_replace(array('{orderId}'), array($this->orderId), '/store/order/{orderId}');
    }
    public function getBody(\Symfony\Component\Serializer\SerializerInterface $serializer, $streamFactory = null) : array
    {
        return array(array(), null);
    }
    /**
     * {@inheritdoc}
     *
     * @throws \Asynit\Tests\PetStore\Generated\Exception\DeleteOrderBadRequestException
     * @throws \Asynit\Tests\PetStore\Generated\Exception\DeleteOrderNotFoundException
     *
     * @return null
     */
    protected function transformResponseBody(\Psr\Http\Message\ResponseInterface $response, \Symfony\Component\Serializer\SerializerInterface $serializer, ?string $contentType = null)
    {
        $status = $response->getStatusCode();
        $body = (string) $response->getBody();
        if (400 === $status) {
            throw new \Asynit\Tests\PetStore\Generated\Exception\DeleteOrderBadRequestException($response);
        }
        if (404 === $status) {
            throw new \Asynit\Tests\PetStore\Generated\Exception\DeleteOrderNotFoundException($response);
        }
    }
    public function getAuthenticationScopes() : array
    {
        return array();
    }
}