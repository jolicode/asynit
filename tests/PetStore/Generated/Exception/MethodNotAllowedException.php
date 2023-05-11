<?php

namespace Asynit\Tests\PetStore\Generated\Exception;

class MethodNotAllowedException extends \RuntimeException implements ClientException
{
    public function __construct(string $message)
    {
        parent::__construct($message, 405);
    }
}