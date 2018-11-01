<?php

namespace Webkul\UVDesk\CoreBundle\Entity;

/**
 * SavedFilters
 */
class SavedFilters
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var string
     */
    private $name;

    /**
     * @var array
     */
    private $filtering;

    /**
     * @var string
     */
    private $route;

    /**
     * @var \DateTime
     */
    private $dateAdded;

    /**
     * @var \DateTime
     */
    private $dateUpdated;

    /**
     * @var \Webkul\UVDesk\CoreBundle\Entity\UserInstance
     */
    private $user;


    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set name
     *
     * @param string $name
     *
     * @return SavedFilters
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set filtering
     *
     * @param array $filtering
     *
     * @return SavedFilters
     */
    public function setFiltering($filtering)
    {
        $this->filtering = $filtering;

        return $this;
    }

    /**
     * Get filtering
     *
     * @return array
     */
    public function getFiltering()
    {
        return $this->filtering;
    }

    /**
     * Set route
     *
     * @param string $route
     *
     * @return SavedFilters
     */
    public function setRoute($route)
    {
        $this->route = $route;

        return $this;
    }

    /**
     * Get route
     *
     * @return string
     */
    public function getRoute()
    {
        return $this->route;
    }

    /**
     * Set dateAdded
     *
     * @param \DateTime $dateAdded
     *
     * @return SavedFilters
     */
    public function setDateAdded($dateAdded)
    {
        $this->dateAdded = $dateAdded;

        return $this;
    }

    /**
     * Get dateAdded
     *
     * @return \DateTime
     */
    public function getDateAdded()
    {
        return $this->dateAdded;
    }

    /**
     * Set dateUpdated
     *
     * @param \DateTime $dateUpdated
     *
     * @return SavedFilters
     */
    public function setDateUpdated($dateUpdated)
    {
        $this->dateUpdated = $dateUpdated;

        return $this;
    }

    /**
     * Get dateUpdated
     *
     * @return \DateTime
     */
    public function getDateUpdated()
    {
        return $this->dateUpdated;
    }

    /**
     * Set user
     *
     * @param \Webkul\UVDesk\CoreBundle\Entity\UserInstance $user
     *
     * @return SavedFilters
     */
    public function setUser(\Webkul\UVDesk\CoreBundle\Entity\UserInstance $user = null)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user
     *
     * @return \Webkul\UVDesk\CoreBundle\Entity\UserInstance
     */
    public function getUser()
    {
        return $this->user;
    }
}

