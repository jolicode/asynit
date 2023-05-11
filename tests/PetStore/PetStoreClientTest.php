<?php

namespace Asynit\Tests\PetStore;

use Asynit\Attribute\Depend;
use Asynit\Attribute\Test;
use Asynit\Attribute\TestCase;
use Asynit\HttpClient\JaneClientCaseTrait;
use Asynit\Tests\PetStore\Generated\Client;
use Asynit\Tests\PetStore\Generated\Model\User;
use Asynit\Tests\PetStore\Generated\Normalizer\JaneObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

#[TestCase]
class PetStoreClientTest extends Client
{
    use JaneClientCaseTrait;

    protected function getNormalizer(): NormalizerInterface
    {
        return new JaneObjectNormalizer();
    }

    protected function getBaseUri(): string
    {
        return 'http://127.0.0.1:8082/v3';
    }

    #[Test]
    public function testListPets()
    {
        $user = new User();
        $user->setUsername('test');
        $user->setUsername('password');
        $user->setEmail('test@test.com');

        $this->createUser($user);

        $password = $this->loginUser(['username' => 'test', 'password' => 'password'], self::FETCH_RESPONSE);

        var_dump($password);
    }
}