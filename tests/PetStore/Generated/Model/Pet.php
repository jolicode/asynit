<?php

namespace Asynit\Tests\PetStore\Generated\Model;

class Pet extends \ArrayObject
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
     * A category for a pet
     *
     * @var Category
     */
    protected $category;
    /**
     * 
     *
     * @var string
     */
    protected $name;
    /**
     * 
     *
     * @var string[]
     */
    protected $photoUrls;
    /**
     * 
     *
     * @var Tag[]
     */
    protected $tags;
    /**
     * pet status in the store
     *
     * @var string
     */
    protected $status;
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
     * A category for a pet
     *
     * @return Category
     */
    public function getCategory() : Category
    {
        return $this->category;
    }
    /**
     * A category for a pet
     *
     * @param Category $category
     *
     * @return self
     */
    public function setCategory(Category $category) : self
    {
        $this->initialized['category'] = true;
        $this->category = $category;
        return $this;
    }
    /**
     * 
     *
     * @return string
     */
    public function getName() : string
    {
        return $this->name;
    }
    /**
     * 
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
     * 
     *
     * @return string[]
     */
    public function getPhotoUrls() : array
    {
        return $this->photoUrls;
    }
    /**
     * 
     *
     * @param string[] $photoUrls
     *
     * @return self
     */
    public function setPhotoUrls(array $photoUrls) : self
    {
        $this->initialized['photoUrls'] = true;
        $this->photoUrls = $photoUrls;
        return $this;
    }
    /**
     * 
     *
     * @return Tag[]
     */
    public function getTags() : array
    {
        return $this->tags;
    }
    /**
     * 
     *
     * @param Tag[] $tags
     *
     * @return self
     */
    public function setTags(array $tags) : self
    {
        $this->initialized['tags'] = true;
        $this->tags = $tags;
        return $this;
    }
    /**
     * pet status in the store
     *
     * @return string
     */
    public function getStatus() : string
    {
        return $this->status;
    }
    /**
     * pet status in the store
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