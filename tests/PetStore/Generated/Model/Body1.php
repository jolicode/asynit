<?php

namespace Asynit\Tests\PetStore\Generated\Model;

class Body1 extends \ArrayObject
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
     * Additional data to pass to server
     *
     * @var string
     */
    protected $additionalMetadata;
    /**
     * file to upload
     *
     * @var string
     */
    protected $file;
    /**
     * Additional data to pass to server
     *
     * @return string
     */
    public function getAdditionalMetadata() : string
    {
        return $this->additionalMetadata;
    }
    /**
     * Additional data to pass to server
     *
     * @param string $additionalMetadata
     *
     * @return self
     */
    public function setAdditionalMetadata(string $additionalMetadata) : self
    {
        $this->initialized['additionalMetadata'] = true;
        $this->additionalMetadata = $additionalMetadata;
        return $this;
    }
    /**
     * file to upload
     *
     * @return string
     */
    public function getFile() : string
    {
        return $this->file;
    }
    /**
     * file to upload
     *
     * @param string $file
     *
     * @return self
     */
    public function setFile(string $file) : self
    {
        $this->initialized['file'] = true;
        $this->file = $file;
        return $this;
    }
}