<?php

namespace Asynit\Tests\PetStore\Generated;

class Client extends \Asynit\Tests\PetStore\Generated\Runtime\Client\Client
{
    /**
     * 
     *
     * @param \Asynit\Tests\PetStore\Generated\Model\Pet $requestBody 
     * @param string $fetch Fetch mode to use (can be OBJECT or RESPONSE)
     * @throws \Asynit\Tests\PetStore\Generated\Exception\AddPetMethodNotAllowedException
     *
     * @return null|\Psr\Http\Message\ResponseInterface
     */
    public function addPet(\Asynit\Tests\PetStore\Generated\Model\Pet $requestBody, string $fetch = self::FETCH_OBJECT)
    {
        return $this->executeEndpoint(new \Asynit\Tests\PetStore\Generated\Endpoint\AddPet($requestBody), $fetch);
    }
    /**
     * 
     *
     * @param \Asynit\Tests\PetStore\Generated\Model\Pet $requestBody 
     * @param string $fetch Fetch mode to use (can be OBJECT or RESPONSE)
     * @throws \Asynit\Tests\PetStore\Generated\Exception\UpdatePetBadRequestException
     * @throws \Asynit\Tests\PetStore\Generated\Exception\UpdatePetNotFoundException
     * @throws \Asynit\Tests\PetStore\Generated\Exception\UpdatePetMethodNotAllowedException
     *
     * @return null|\Psr\Http\Message\ResponseInterface
     */
    public function updatePet(\Asynit\Tests\PetStore\Generated\Model\Pet $requestBody, string $fetch = self::FETCH_OBJECT)
    {
        return $this->executeEndpoint(new \Asynit\Tests\PetStore\Generated\Endpoint\UpdatePet($requestBody), $fetch);
    }
    /**
     * Multiple status values can be provided with comma separated strings
     *
     * @param array $queryParameters {
     *     @var array $status Status values that need to be considered for filter
     * }
     * @param string $fetch Fetch mode to use (can be OBJECT or RESPONSE)
     * @param array $accept Accept content header application/xml|application/json
     * @throws \Asynit\Tests\PetStore\Generated\Exception\FindPetsByStatusBadRequestException
     *
     * @return null|\Asynit\Tests\PetStore\Generated\Model\Pet[]|\Psr\Http\Message\ResponseInterface
     */
    public function findPetsByStatus(array $queryParameters = array(), string $fetch = self::FETCH_OBJECT, array $accept = array())
    {
        return $this->executeEndpoint(new \Asynit\Tests\PetStore\Generated\Endpoint\FindPetsByStatus($queryParameters, $accept), $fetch);
    }
    /**
     * Multiple tags can be provided with comma separated strings. Use tag1, tag2, tag3 for testing.
     *
     * @param array $queryParameters {
     *     @var array $tags Tags to filter by
     * }
     * @param string $fetch Fetch mode to use (can be OBJECT or RESPONSE)
     * @param array $accept Accept content header application/xml|application/json
     * @throws \Asynit\Tests\PetStore\Generated\Exception\FindPetsByTagsBadRequestException
     *
     * @return null|\Asynit\Tests\PetStore\Generated\Model\Pet[]|\Psr\Http\Message\ResponseInterface
     */
    public function findPetsByTags(array $queryParameters = array(), string $fetch = self::FETCH_OBJECT, array $accept = array())
    {
        return $this->executeEndpoint(new \Asynit\Tests\PetStore\Generated\Endpoint\FindPetsByTags($queryParameters, $accept), $fetch);
    }
    /**
     * 
     *
     * @param int $petId Pet id to delete
     * @param array $headerParameters {
     *     @var string $api_key 
     * }
     * @param string $fetch Fetch mode to use (can be OBJECT or RESPONSE)
     * @throws \Asynit\Tests\PetStore\Generated\Exception\DeletePetBadRequestException
     *
     * @return null|\Psr\Http\Message\ResponseInterface
     */
    public function deletePet(int $petId, array $headerParameters = array(), string $fetch = self::FETCH_OBJECT)
    {
        return $this->executeEndpoint(new \Asynit\Tests\PetStore\Generated\Endpoint\DeletePet($petId, $headerParameters), $fetch);
    }
    /**
     * Returns a single pet
     *
     * @param int $petId ID of pet to return
     * @param string $fetch Fetch mode to use (can be OBJECT or RESPONSE)
     * @param array $accept Accept content header application/xml|application/json
     * @throws \Asynit\Tests\PetStore\Generated\Exception\GetPetByIdBadRequestException
     * @throws \Asynit\Tests\PetStore\Generated\Exception\GetPetByIdNotFoundException
     *
     * @return null|\Asynit\Tests\PetStore\Generated\Model\Pet|\Psr\Http\Message\ResponseInterface
     */
    public function getPetById(int $petId, string $fetch = self::FETCH_OBJECT, array $accept = array())
    {
        return $this->executeEndpoint(new \Asynit\Tests\PetStore\Generated\Endpoint\GetPetById($petId, $accept), $fetch);
    }
    /**
     * 
     *
     * @param int $petId ID of pet that needs to be updated
     * @param null|\Asynit\Tests\PetStore\Generated\Model\Body $requestBody 
     * @param string $fetch Fetch mode to use (can be OBJECT or RESPONSE)
     * @throws \Asynit\Tests\PetStore\Generated\Exception\UpdatePetWithFormMethodNotAllowedException
     *
     * @return null|\Psr\Http\Message\ResponseInterface
     */
    public function updatePetWithForm(int $petId, ?\Asynit\Tests\PetStore\Generated\Model\Body $requestBody = null, string $fetch = self::FETCH_OBJECT)
    {
        return $this->executeEndpoint(new \Asynit\Tests\PetStore\Generated\Endpoint\UpdatePetWithForm($petId, $requestBody), $fetch);
    }
    /**
     * 
     *
     * @param int $petId ID of pet to update
     * @param null|\Asynit\Tests\PetStore\Generated\Model\Body1 $requestBody 
     * @param string $fetch Fetch mode to use (can be OBJECT or RESPONSE)
     *
     * @return null|\Asynit\Tests\PetStore\Generated\Model\ApiResponse|\Psr\Http\Message\ResponseInterface
     */
    public function uploadFile(int $petId, ?\Asynit\Tests\PetStore\Generated\Model\Body1 $requestBody = null, string $fetch = self::FETCH_OBJECT)
    {
        return $this->executeEndpoint(new \Asynit\Tests\PetStore\Generated\Endpoint\UploadFile($petId, $requestBody), $fetch);
    }
    /**
     * @param string $fetch Fetch mode to use (can be OBJECT or RESPONSE)
     *
     * @return null|\Psr\Http\Message\ResponseInterface
     */
    public function getInventory(string $fetch = self::FETCH_OBJECT)
    {
        return $this->executeEndpoint(new \Asynit\Tests\PetStore\Generated\Endpoint\GetInventory(), $fetch);
    }
    /**
     * 
     *
     * @param \Asynit\Tests\PetStore\Generated\Model\Order $requestBody 
     * @param string $fetch Fetch mode to use (can be OBJECT or RESPONSE)
     * @param array $accept Accept content header application/xml|application/json
     * @throws \Asynit\Tests\PetStore\Generated\Exception\PlaceOrderBadRequestException
     *
     * @return null|\Asynit\Tests\PetStore\Generated\Model\Order|\Psr\Http\Message\ResponseInterface
     */
    public function placeOrder(\Asynit\Tests\PetStore\Generated\Model\Order $requestBody, string $fetch = self::FETCH_OBJECT, array $accept = array())
    {
        return $this->executeEndpoint(new \Asynit\Tests\PetStore\Generated\Endpoint\PlaceOrder($requestBody, $accept), $fetch);
    }
    /**
     * For valid response try integer IDs with value < 1000. Anything above 1000 or nonintegers will generate API errors
     *
     * @param string $orderId ID of the order that needs to be deleted
     * @param string $fetch Fetch mode to use (can be OBJECT or RESPONSE)
     * @throws \Asynit\Tests\PetStore\Generated\Exception\DeleteOrderBadRequestException
     * @throws \Asynit\Tests\PetStore\Generated\Exception\DeleteOrderNotFoundException
     *
     * @return null|\Psr\Http\Message\ResponseInterface
     */
    public function deleteOrder(string $orderId, string $fetch = self::FETCH_OBJECT)
    {
        return $this->executeEndpoint(new \Asynit\Tests\PetStore\Generated\Endpoint\DeleteOrder($orderId), $fetch);
    }
    /**
     * For valid response try integer IDs with value <= 5 or > 10. Other values will generated exceptions
     *
     * @param int $orderId ID of pet that needs to be fetched
     * @param string $fetch Fetch mode to use (can be OBJECT or RESPONSE)
     * @param array $accept Accept content header application/xml|application/json
     * @throws \Asynit\Tests\PetStore\Generated\Exception\GetOrderByIdBadRequestException
     * @throws \Asynit\Tests\PetStore\Generated\Exception\GetOrderByIdNotFoundException
     *
     * @return null|\Asynit\Tests\PetStore\Generated\Model\Order|\Psr\Http\Message\ResponseInterface
     */
    public function getOrderById(int $orderId, string $fetch = self::FETCH_OBJECT, array $accept = array())
    {
        return $this->executeEndpoint(new \Asynit\Tests\PetStore\Generated\Endpoint\GetOrderById($orderId, $accept), $fetch);
    }
    /**
     * This can only be done by the logged in user.
     *
     * @param \Asynit\Tests\PetStore\Generated\Model\User $requestBody 
     * @param string $fetch Fetch mode to use (can be OBJECT or RESPONSE)
     *
     * @return null|\Psr\Http\Message\ResponseInterface
     */
    public function createUser(\Asynit\Tests\PetStore\Generated\Model\User $requestBody, string $fetch = self::FETCH_OBJECT)
    {
        return $this->executeEndpoint(new \Asynit\Tests\PetStore\Generated\Endpoint\CreateUser($requestBody), $fetch);
    }
    /**
     * 
     *
     * @param \Asynit\Tests\PetStore\Generated\Model\User[] $requestBody 
     * @param string $fetch Fetch mode to use (can be OBJECT or RESPONSE)
     *
     * @return null|\Psr\Http\Message\ResponseInterface
     */
    public function createUsersWithArrayInput(array $requestBody, string $fetch = self::FETCH_OBJECT)
    {
        return $this->executeEndpoint(new \Asynit\Tests\PetStore\Generated\Endpoint\CreateUsersWithArrayInput($requestBody), $fetch);
    }
    /**
     * 
     *
     * @param \Asynit\Tests\PetStore\Generated\Model\User[] $requestBody 
     * @param string $fetch Fetch mode to use (can be OBJECT or RESPONSE)
     *
     * @return null|\Psr\Http\Message\ResponseInterface
     */
    public function createUsersWithListInput(array $requestBody, string $fetch = self::FETCH_OBJECT)
    {
        return $this->executeEndpoint(new \Asynit\Tests\PetStore\Generated\Endpoint\CreateUsersWithListInput($requestBody), $fetch);
    }
    /**
     * 
     *
     * @param array $queryParameters {
     *     @var string $username The user name for login
     *     @var string $password The password for login in clear text
     * }
     * @param string $fetch Fetch mode to use (can be OBJECT or RESPONSE)
     * @param array $accept Accept content header application/xml|application/json
     * @throws \Asynit\Tests\PetStore\Generated\Exception\LoginUserBadRequestException
     *
     * @return null|\Psr\Http\Message\ResponseInterface
     */
    public function loginUser(array $queryParameters = array(), string $fetch = self::FETCH_OBJECT, array $accept = array())
    {
        return $this->executeEndpoint(new \Asynit\Tests\PetStore\Generated\Endpoint\LoginUser($queryParameters, $accept), $fetch);
    }
    /**
     * @param string $fetch Fetch mode to use (can be OBJECT or RESPONSE)
     *
     * @return null|\Psr\Http\Message\ResponseInterface
     */
    public function logoutUser(string $fetch = self::FETCH_OBJECT)
    {
        return $this->executeEndpoint(new \Asynit\Tests\PetStore\Generated\Endpoint\LogoutUser(), $fetch);
    }
    /**
     * This can only be done by the logged in user.
     *
     * @param string $username The name that needs to be deleted
     * @param string $fetch Fetch mode to use (can be OBJECT or RESPONSE)
     * @throws \Asynit\Tests\PetStore\Generated\Exception\DeleteUserBadRequestException
     * @throws \Asynit\Tests\PetStore\Generated\Exception\DeleteUserNotFoundException
     *
     * @return null|\Psr\Http\Message\ResponseInterface
     */
    public function deleteUser(string $username, string $fetch = self::FETCH_OBJECT)
    {
        return $this->executeEndpoint(new \Asynit\Tests\PetStore\Generated\Endpoint\DeleteUser($username), $fetch);
    }
    /**
     * 
     *
     * @param string $username The name that needs to be fetched. Use user1 for testing.
     * @param string $fetch Fetch mode to use (can be OBJECT or RESPONSE)
     * @param array $accept Accept content header application/xml|application/json
     * @throws \Asynit\Tests\PetStore\Generated\Exception\GetUserByNameBadRequestException
     * @throws \Asynit\Tests\PetStore\Generated\Exception\GetUserByNameNotFoundException
     *
     * @return null|\Asynit\Tests\PetStore\Generated\Model\User|\Psr\Http\Message\ResponseInterface
     */
    public function getUserByName(string $username, string $fetch = self::FETCH_OBJECT, array $accept = array())
    {
        return $this->executeEndpoint(new \Asynit\Tests\PetStore\Generated\Endpoint\GetUserByName($username, $accept), $fetch);
    }
    /**
     * This can only be done by the logged in user.
     *
     * @param string $username name that need to be deleted
     * @param \Asynit\Tests\PetStore\Generated\Model\User $requestBody 
     * @param string $fetch Fetch mode to use (can be OBJECT or RESPONSE)
     * @throws \Asynit\Tests\PetStore\Generated\Exception\UpdateUserBadRequestException
     * @throws \Asynit\Tests\PetStore\Generated\Exception\UpdateUserNotFoundException
     *
     * @return null|\Psr\Http\Message\ResponseInterface
     */
    public function updateUser(string $username, \Asynit\Tests\PetStore\Generated\Model\User $requestBody, string $fetch = self::FETCH_OBJECT)
    {
        return $this->executeEndpoint(new \Asynit\Tests\PetStore\Generated\Endpoint\UpdateUser($username, $requestBody), $fetch);
    }
    public static function create($httpClient = null, array $additionalPlugins = array(), array $additionalNormalizers = array())
    {
        if (null === $httpClient) {
            $httpClient = \Http\Discovery\Psr18ClientDiscovery::find();
            $plugins = array();
            $uri = \Http\Discovery\Psr17FactoryDiscovery::findUrlFactory()->createUri('/v3');
            $plugins[] = new \Http\Client\Common\Plugin\AddPathPlugin($uri);
            if (count($additionalPlugins) > 0) {
                $plugins = array_merge($plugins, $additionalPlugins);
            }
            $httpClient = new \Http\Client\Common\PluginClient($httpClient, $plugins);
        }
        $requestFactory = \Http\Discovery\Psr17FactoryDiscovery::findRequestFactory();
        $streamFactory = \Http\Discovery\Psr17FactoryDiscovery::findStreamFactory();
        $normalizers = array(new \Symfony\Component\Serializer\Normalizer\ArrayDenormalizer(), new \Asynit\Tests\PetStore\Generated\Normalizer\JaneObjectNormalizer());
        if (count($additionalNormalizers) > 0) {
            $normalizers = array_merge($normalizers, $additionalNormalizers);
        }
        $serializer = new \Symfony\Component\Serializer\Serializer($normalizers, array(new \Symfony\Component\Serializer\Encoder\JsonEncoder(new \Symfony\Component\Serializer\Encoder\JsonEncode(), new \Symfony\Component\Serializer\Encoder\JsonDecode(array('json_decode_associative' => true)))));
        return new static($httpClient, $requestFactory, $serializer, $streamFactory);
    }
}