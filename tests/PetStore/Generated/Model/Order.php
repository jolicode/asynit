<?php

namespace Asynit\Tests\PetStore\Generated\Model;

class Order extends \ArrayObject
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
     * 
     *
     * @var int
     */
    protected $id;
    /**
     * 
     *
     * @var int
     */
    protected $petId;
    /**
     * 
     *
     * @var int
     */
    protected $quantity;
    /**
     * 
     *
     * @var \DateTime
     */
    protected $shipDate;
    /**
     * Order Status
     *
     * @var string
     */
    protected $status;
    /**
     * 
     *
     * @var bool
     */
    protected $complete = false;
    /**
     * 
     *
     * @return int
     */
    public function getId() : int
    {
        return $this->id;
    }
    /**
     * 
     *
     * @param int $id
     *
     * @return self
     */
    public function setId(int $id) : self
    {
        $this->initialized['id'] = true;
        $this->id = $id;
        return $this;
    }
    /**
     * 
     *
     * @return int
     */
    public function getPetId() : int
    {
        return $this->petId;
    }
    /**
     * 
     *
     * @param int $petId
     *
     * @return self
     */
    public function setPetId(int $petId) : self
    {
        $this->initialized['petId'] = true;
        $this->petId = $petId;
        return $this;
    }
    /**
     * 
     *
     * @return int
     */
    public function getQuantity() : int
    {
        return $this->quantity;
    }
    /**
     * 
     *
     * @param int $quantity
     *
     * @return self
     */
    public function setQuantity(int $quantity) : self
    {
        $this->initialized['quantity'] = true;
        $this->quantity = $quantity;
        return $this;
    }
    /**
     * 
     *
     * @return \DateTime
     */
    public function getShipDate() : \DateTime
    {
        return $this->shipDate;
    }
    /**
     * 
     *
     * @param \DateTime $shipDate
     *
     * @return self
     */
    public function setShipDate(\DateTime $shipDate) : self
    {
        $this->initialized['shipDate'] = true;
        $this->shipDate = $shipDate;
        return $this;
    }
    /**
     * Order Status
     *
     * @return string
     */
    public function getStatus() : string
    {
        return $this->status;
    }
    /**
     * Order Status
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
    /**
     * 
     *
     * @return bool
     */
    public function getComplete() : bool
    {
        return $this->complete;
    }
    /**
     * 
     *
     * @param bool $complete
     *
     * @return self
     */
    public function setComplete(bool $complete) : self
    {
        $this->initialized['complete'] = true;
        $this->complete = $complete;
        return $this;
    }
}