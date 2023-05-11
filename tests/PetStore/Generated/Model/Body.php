<?php

namespace Asynit\Tests\PetStore\Generated\Model;

class Body extends \ArrayObject
{
    /**
     * @var array
     */
    protected $initialized = array();
    public function isInitialized($property) : bool
    {
        return array_key_exists($property, $this->initialized);
    }
    /**
     * Updated name of the pet
     *
     * @var string
     */
    protected $name;
    /**
     * Updated status of the pet
     *
     * @var string
     */
    protected $status;
    /**
     * Updated name of the pet
     *
     * @return string
     */
    public function getName() : string
    {
        return $this->name;
    }
    /**
     * Updated name of the pet
     *
     * @param string $name
     *
     * @return self
     */
    public function setName(string $name) : self
    {
        $this->initialized['name'] = true;
        $this->name = $name;
        return $this;
    }
    /**
     * Updated status of the pet
     *
     * @return string
     */
    public function getStatus() : string
    {
        return $this->status;
    }
    /**
     * Updated status of the pet
     *
     * @param string $status
     *
     * @return self
     */
    public function setStatus(string $status) : self
    {
        $this->initialized['status'] = true;
        $this->status = $status;
        return $this;
    }
}